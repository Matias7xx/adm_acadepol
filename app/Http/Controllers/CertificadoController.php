<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Matricula;
use App\Models\Curso;
use App\Helpers\CertificadoHelper;
use App\Helpers\StorageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CertificadoController extends Controller
{
    /**
     * Gerar certificado para um aluno específico (via upload)
     */
    public function gerar(Request $request, Matricula $matricula)
    {
        try {
            // Verificar permissão - apenas administradores
            $this->authorize('viewAny', Matricula::class);
            
            // Validar o arquivo enviado
            $validator = Validator::make($request->all(), [
                'certificado_pdf' => [
                    'required',
                    'file',
                    'mimes:pdf',
                    'max:10240' // 10MB máximo
                ]
            ], [
                'certificado_pdf.required' => 'O arquivo PDF do certificado é obrigatório.',
                'certificado_pdf.mimes' => 'O arquivo deve ser um PDF.',
                'certificado_pdf.max' => 'O arquivo não pode ser maior que 10MB.'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->with('error', 'Erro na validação do arquivo: ' . implode(', ', $validator->errors()->all()));
            }
            
            // Verificar se a matrícula está aprovada
            if ($matricula->status !== 'aprovada') {
                return redirect()->back()->with('error', 'Certificado só pode ser gerado para matrículas aprovadas');
            }
            
            // Verificar se o curso tem certificação habilitada
            if (!$matricula->curso->certificacao) {
                return redirect()->back()->with('error', 'Este curso não emite certificado');
            }
            
            // Verificar se o curso foi concluído
            if ($matricula->curso->status !== 'concluído' && $matricula->curso->data_fim > now()) {
                return redirect()->back()->with('error', 'Certificado só pode ser gerado após a conclusão do curso');
            }
            
            // Verificar se já existe certificado para esta matrícula
            $certificadoExistente = Certificado::where('matricula_id', $matricula->id)->first();
            if ($certificadoExistente) {
                return redirect()->back()->with('error', 'Certificado já foi gerado para este aluno');
            }
            
            // Processar o upload e criar o certificado
            $certificado = $this->criarCertificadoComUpload($matricula, $request->file('certificado_pdf'));
            
            Log::info('Certificado criado com sucesso via upload', [
                'certificado_id' => $certificado->id,
                'matricula_id' => $matricula->id,
                'admin_id' => Auth::id(),
                'arquivo_original' => $request->file('certificado_pdf')->getClientOriginalName(),
                'caminho_final' => $certificado->arquivo_path
            ]);
            
            return redirect()->back()->with('success', 'Certificado criado com sucesso!');
            
        } catch (\Exception $e) {
            Log::error('Erro ao criar certificado via upload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'matricula_id' => $matricula->id,
                'admin_id' => Auth::id()
            ]);
            
            return redirect()->back()->with('error', 'Erro ao processar certificado: ' . $e->getMessage());
        }
    }
    
    /**
     * Criar e salvar certificado com upload de PDF usando StorageHelper para MinIO
     */
    private function criarCertificadoComUpload(Matricula $matricula, $arquivoPdf)
    {
        // Gerar número único do certificado
        $numeroCertificado = Certificado::gerarNumeroCertificado();
        
        // Gerar caminho estruturado para o MinIO
        $nomeUsuario = CertificadoHelper::sanitizeFolderName($matricula->aluno->name);
        $nomeCurso = CertificadoHelper::sanitizeFolderName($matricula->curso->nome);
        
        // USAR UUID PARA EVITAR CONFLITOS NO MINIO
        $nomeArquivo = Str::uuid() . '.pdf';
        
        // CAMINHO VIRTUAL NO MINIO (estrutura de pastas)
        $caminhoVirtual = "certificados/{$nomeUsuario}/{$nomeCurso}/{$nomeArquivo}";
        
        try {
            // SALVAR NO BUCKET 'certificados' VIA STORAGEHELPER
            $sucesso = StorageHelper::certificados()->putFileAs(
                "certificados/{$nomeUsuario}/{$nomeCurso}",
                $arquivoPdf,
                $nomeArquivo
            );
            
            if (!$sucesso) {
                throw new \Exception('Falha ao salvar o arquivo do certificado no MinIO');
            }
            
            // Salvar registro no banco
            $certificado = Certificado::create([
                'matricula_id' => $matricula->id,
                'user_id' => $matricula->aluno->id,
                'curso_id' => $matricula->curso->id,
                'numero_certificado' => $numeroCertificado,
                'arquivo_path' => $caminhoVirtual,
                'data_emissao' => now(),
                'data_conclusao_curso' => $matricula->curso->data_fim,
                'carga_horaria' => $matricula->curso->carga_horaria,
                'nome_aluno' => $matricula->aluno->name,
                'cpf_aluno' => $matricula->aluno->cpf,
                'nome_curso' => $matricula->curso->nome,
                'tipo_origem' => Certificado::TIPO_MATRICULA,
                'ativo' => true
            ]);
            
            Log::info('Certificado salvo no MinIO via StorageHelper', [
                'arquivo_nome' => $nomeArquivo,
                'caminho_virtual' => $caminhoVirtual,
                'bucket' => 'certificados',
                'numero_certificado' => $numeroCertificado
            ]);
            
            return $certificado;
            
        } catch (\Exception $e) {
            Log::error('Erro ao salvar certificado no MinIO', [
                'error' => $e->getMessage(),
                'caminho_virtual' => $caminhoVirtual,
                'matricula_id' => $matricula->id
            ]);
            throw $e;
        }
    }
    
    /**
     * Download do certificado - POLICY
     */
    public function download(Certificado $certificado)
    {
        try {
            // Usar a policy de certificados
            $this->authorize('download', $certificado);
            
            //VERIFICAR SE ARQUIVO EXISTE NO MINIO
            if (!$certificado->arquivoExiste()) {
                Log::error('Arquivo de certificado não encontrado no MinIO', [
                    'certificado_id' => $certificado->id,
                    'arquivo_path' => $certificado->arquivo_path
                ]);
                return back()->with('error', 'Arquivo do certificado não encontrado');
            }
            
            // Nome para download usando helper
            $nomeDownload = 'Certificado_' . $certificado->numero_certificado . '_' . 
                          CertificadoHelper::sanitizeFolderName($certificado->nome_aluno) . '.pdf';
            
            // BUSCAR CONTEÚDO DO ARQUIVO NO MINIO
            $conteudo = StorageHelper::certificados()->get($certificado->arquivo_path);
            
            Log::info('Download de certificado realizado via MinIO', [
                'certificado_id' => $certificado->id,
                'user_id' => Auth::id(),
                'nome_download' => $nomeDownload,
                'tamanho' => strlen($conteudo)
            ]);
            
            //RETORNAR CONTEÚDO DIRETAMENTE
            return response($conteudo, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $nomeDownload . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao fazer download do certificado via MinIO', [
                'error' => $e->getMessage(),
                'certificado_id' => $certificado->id,
                'user_id' => Auth::id()
            ]);
            
            return back()->with('error', 'Erro ao baixar certificado: ' . $e->getMessage());
        }
    }
    
    /**
     * Visualizar certificado no navegador
     */
    public function view(Certificado $certificado)
    {
        try {
            // Usar a policy de certificados
            $this->authorize('download', $certificado);
            
            // VERIFICAR SE ARQUIVO EXISTE NO MINIO
            if (!$certificado->arquivoExiste()) {
                Log::error('Arquivo de certificado não encontrado no MinIO para visualização', [
                    'certificado_id' => $certificado->id,
                    'arquivo_path' => $certificado->arquivo_path
                ]);
                return back()->with('error', 'Arquivo do certificado não encontrado');
            }
            
            //BUSCAR CONTEÚDO DO ARQUIVO NO MINIO
            $conteudo = StorageHelper::certificados()->get($certificado->arquivo_path);
            
            Log::info('Visualização de certificado via MinIO', [
                'certificado_id' => $certificado->id,
                'user_id' => Auth::id(),
                'tamanho' => strlen($conteudo)
            ]);
            
            //RETORNAR PARA VISUALIZAÇÃO INLINE
            return response($conteudo, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="certificado_' . $certificado->numero_certificado . '.pdf"',
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao visualizar certificado via MinIO', [
                'error' => $e->getMessage(),
                'certificado_id' => $certificado->id,
                'user_id' => Auth::id()
            ]);
            
            return back()->with('error', 'Erro ao visualizar certificado: ' . $e->getMessage());
        }
    }
    
    /**
     * Excluir certificado (para administradores) - MinIO
     */
    public function excluir(Certificado $certificado)
    {
        try {
            // Verificar permissão usando policy
            $this->authorize('delete', $certificado);
            
            // REMOVER ARQUIVO DO MINIO
            if ($certificado->arquivoExiste()) {
                StorageHelper::certificados()->delete($certificado->arquivo_path);
                Log::info('Arquivo removido do MinIO', [
                    'arquivo_path' => $certificado->arquivo_path,
                    'certificado_id' => $certificado->id
                ]);
            }
            
            // Remover registro do banco
            $certificado->delete();
            
            Log::info('Certificado excluído com sucesso', [
                'certificado_id' => $certificado->id,
                'admin_id' => Auth::id(),
                'arquivo_path' => $certificado->arquivo_path
            ]);
            
            return redirect()->back()->with('success', 'Certificado excluído com sucesso!');
            
        } catch (\Exception $e) {
            Log::error('Erro ao excluir certificado do MinIO', [
                'error' => $e->getMessage(),
                'certificado_id' => $certificado->id
            ]);
            
            return back()->with('error', 'Erro ao excluir certificado');
        }
    }
    
    /**
     * Listar certificados do usuário logado
     */
    public function meusCertificados()
    {
        $certificados = Certificado::where('user_id', Auth::id())
            ->with(['curso'])
            ->orderBy('data_emissao', 'desc')
            ->paginate(10);
            
        return Inertia::render('MeusCertificados', [
            'certificados' => $certificados
        ]);
    }
    
    /**
     * Criar certificado para curso externo
     */
    /**
     * CRIAR CERTIFICADO PARA CURSO EXTERNO
     */
    public function criarCertificadoExterno(Request $request)
    {
        try {
            // Verificar permissão
            $this->authorize('viewAny', Certificado::class);
            
            // Validar dados
            $validator = Validator::make($request->all(), [
                'nome_aluno' => 'required|string|max:255',
                'cpf_aluno' => 'required|string|size:14', // Formato: 000.000.000-00
                'nome_curso' => 'required|string|max:255',
                'carga_horaria' => 'required|integer|min:1',
                'data_conclusao_curso' => 'required|date',
                'certificado_pdf' => [
                    'required',
                    'file',
                    'mimes:pdf',
                    'max:10240'
                ]
            ], [
                'nome_aluno.required' => 'O nome do aluno é obrigatório.',
                'cpf_aluno.required' => 'O CPF do aluno é obrigatório.',
                'cpf_aluno.size' => 'O CPF deve estar no formato 000.000.000-00.',
                'nome_curso.required' => 'O nome do curso é obrigatório.',
                'carga_horaria.required' => 'A carga horária é obrigatória.',
                'carga_horaria.integer' => 'A carga horária deve ser um número.',
                'carga_horaria.min' => 'A carga horária deve ser no mínimo 1 hora.',
                'data_conclusao_curso.required' => 'A data de conclusão é obrigatória.',
                'data_conclusao_curso.date' => 'Data de conclusão inválida.',
                'certificado_pdf.required' => 'O arquivo PDF do certificado é obrigatório.',
                'certificado_pdf.mimes' => 'O arquivo deve ser um PDF.',
                'certificado_pdf.max' => 'O arquivo não pode ser maior que 10MB.'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            
            // Limpar e validar CPF
            $cpfLimpo = preg_replace('/[^0-9]/', '', $request->cpf_aluno);
            
            if (strlen($cpfLimpo) !== 11) {
                return redirect()->back()
                    ->withErrors(['cpf_aluno' => 'CPF deve conter 11 dígitos'])
                    ->withInput();
            }
            
            // Verificar se já existe certificado para este CPF/curso
            $certificadoExistente = Certificado::where('cpf_aluno', $cpfLimpo)
                ->where('nome_curso', $request->nome_curso)
                ->where('tipo_origem', Certificado::TIPO_CURSO_EXTERNO)
                ->first();
                
            if ($certificadoExistente) {
                return redirect()->back()
                    ->withErrors(['cpf_aluno' => 'Já existe certificado para este CPF neste curso externo'])
                    ->withInput();
            }
            
            // Criar certificado usando método auxiliar
            $certificado = $this->processarCertificadoExterno($request, $cpfLimpo);
            
            Log::info('Certificado externo criado via MinIO', [
                'certificado_id' => $certificado->id,
                'numero_certificado' => $certificado->numero_certificado,
                'nome_aluno' => $certificado->nome_aluno,
                'cpf_aluno' => $cpfLimpo,
                'nome_curso' => $certificado->nome_curso,
                'admin_id' => Auth::id(),
                'arquivo_original' => $request->file('certificado_pdf')->getClientOriginalName()
            ]);
            
            return redirect()->back()->with('success', 'Certificado de curso externo criado com sucesso!');
            
        } catch (\Exception $e) {
            Log::error('Erro ao criar certificado externo via MinIO', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => Auth::id(),
                'request_data' => $request->except(['certificado_pdf']) // Não logar o arquivo
            ]);
            
            return redirect()->back()->with('error', 'Erro ao criar certificado: ' . $e->getMessage());
        }
    }
    
    /**
     * PROCESSAR CERTIFICADO EXTERNO NO MINIO
     */
    private function processarCertificadoExterno(Request $request, string $cpfLimpo)
    {
        // Gerar número único do certificado
        $numeroCertificado = Certificado::gerarNumeroCertificado();
        
        // Sanitizar nomes para estrutura de pastas
        $nomeAluno = CertificadoHelper::sanitizeFolderName($request->nome_aluno);
        $nomeCurso = CertificadoHelper::sanitizeFolderName($request->nome_curso);
        
        // Gerar nome único do arquivo
        $nomeArquivo = Str::uuid() . '.pdf';
        
        // Caminho virtual no MinIO para certificados externos
        $caminhoVirtual = "certificados-externos/{$nomeAluno}/{$nomeCurso}/{$nomeArquivo}";
        $diretorioUpload = "certificados-externos/{$nomeAluno}/{$nomeCurso}";
        
        try {
            // SALVAR NO BUCKET 'certificados' VIA STORAGEHELPER
            $sucesso = StorageHelper::certificados()->putFileAs(
                $diretorioUpload,
                $request->file('certificado_pdf'),
                $nomeArquivo
            );
            
            if (!$sucesso) {
                throw new \Exception('Falha ao salvar certificado externo no MinIO');
            }
            
            // Criar registro no banco de dados
            $certificado = Certificado::create([
                'matricula_id' => null, // Curso externo não tem matrícula
                'user_id' => null, // Curso externo pode não ter usuário no sistema
                'curso_id' => null, // Curso externo não tem registro na tabela cursos
                'numero_certificado' => $numeroCertificado,
                'arquivo_path' => $caminhoVirtual,
                'data_emissao' => now(),
                'data_conclusao_curso' => $request->data_conclusao_curso,
                'carga_horaria' => $request->carga_horaria,
                'nome_aluno' => $request->nome_aluno,
                'cpf_aluno' => $cpfLimpo,
                'nome_curso' => $request->nome_curso,
                'tipo_origem' => Certificado::TIPO_CURSO_EXTERNO,
                'ativo' => true
            ]);
            
            Log::info('Certificado externo salvo no MinIO', [
                'certificado_id' => $certificado->id,
                'numero_certificado' => $numeroCertificado,
                'caminho_virtual' => $caminhoVirtual,
                'bucket' => 'certificados',
                'diretorio' => $diretorioUpload
            ]);
            
            return $certificado;
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar certificado externo no MinIO', [
                'error' => $e->getMessage(),
                'caminho_virtual' => $caminhoVirtual,
                'nome_aluno' => $request->nome_aluno,
                'nome_curso' => $request->nome_curso
            ]);
            throw $e;
        }
    }
}