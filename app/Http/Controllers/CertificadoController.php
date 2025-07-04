<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Matricula;
use App\Models\Curso;
use App\Helpers\CertificadoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
     * Criar e salvar certificado com upload de PDF usando helper
     */
    private function criarCertificadoComUpload(Matricula $matricula, $arquivoPdf)
    {
        // Gerar número único do certificado
        $numeroCertificado = Certificado::gerarNumeroCertificado();
        
        // Usar helper para gerar caminho estruturado com número do certificado
        $caminhoCompleto = CertificadoHelper::gerarCaminhoUnico($matricula, $numeroCertificado);
        
        // Garantir que o diretório existe
        if (!CertificadoHelper::criarEstruturaDiretorios($caminhoCompleto)) {
            throw new \Exception('Não foi possível criar a estrutura de diretórios');
        }
        
        // Extrair diretório e nome do arquivo
        $diretorio = dirname($caminhoCompleto);
        $nomeArquivo = basename($caminhoCompleto);
        
        // Salvar arquivo no storage usando storeAs
        $arquivoSalvo = $arquivoPdf->storeAs($diretorio, $nomeArquivo, 'local');
        
        if (!$arquivoSalvo) {
            throw new \Exception('Falha ao salvar o arquivo do certificado');
        }
        
        // Salvar registro no banco
        $certificado = Certificado::create([
            'matricula_id' => $matricula->id,
            'user_id' => $matricula->aluno->id,
            'curso_id' => $matricula->curso->id,
            'numero_certificado' => $numeroCertificado,
            'arquivo_path' => $caminhoCompleto,
            'data_emissao' => now(),
            'data_conclusao_curso' => $matricula->curso->data_fim,
            'carga_horaria' => $matricula->curso->carga_horaria,
            'nome_aluno' => $matricula->aluno->name,
            'cpf_aluno' => $matricula->aluno->cpf,
            'nome_curso' => $matricula->curso->nome,
        ]);
        
        Log::info('Estrutura de pasta criada para certificado', [
            'arquivo_salvo' => $arquivoSalvo,
            'caminho_final' => $caminhoCompleto,
            'nome_arquivo_final' => $nomeArquivo
        ]);
        
        return $certificado;
    }
    
    /**
     * Download do certificado - POLICY
     */
    public function download(Certificado $certificado)
    {
        try {
            // Usar a policy de certificados
            $this->authorize('download', $certificado);
            
            // Verificar se arquivo existe
            if (!$certificado->arquivoExiste()) {
                Log::error('Arquivo de certificado não encontrado', [
                    'certificado_id' => $certificado->id,
                    'arquivo_path' => $certificado->arquivo_path
                ]);
                return back()->with('error', 'Arquivo do certificado não encontrado');
            }
            
            // Nome para download usando helper
            $nomeDownload = 'Certificado_' . $certificado->numero_certificado . '_' . 
                          CertificadoHelper::sanitizeFolderName($certificado->nome_aluno) . '.pdf';
            
            Log::info('Download de certificado realizado', [
                'certificado_id' => $certificado->id,
                'user_id' => Auth::id(),
                'nome_download' => $nomeDownload
            ]);
            
            return Storage::disk('local')->download($certificado->arquivo_path, $nomeDownload);
            
        } catch (\Exception $e) {
            Log::error('Erro ao fazer download do certificado', [
                'error' => $e->getMessage(),
                'certificado_id' => $certificado->id,
                'user_id' => Auth::id()
            ]);
            
            return back()->with('error', 'Erro ao baixar certificado: ' . $e->getMessage());
        }
    }
    
    /**
     * Excluir certificado (para administradores)
     */
    public function excluir(Certificado $certificado)
    {
        try {
            // Verificar permissão usando policy
            $this->authorize('delete', $certificado);
            
            // Remover arquivo do storage
            if ($certificado->arquivoExiste()) {
                Storage::disk('local')->delete($certificado->arquivo_path);
                
                // Limpar diretórios vazios usando helper
                CertificadoHelper::limparDiretoriosVazios($certificado->arquivo_path);
            }
            
            // Remover registro do banco
            $certificado->delete();
            
            Log::info('Certificado excluído', [
                'certificado_id' => $certificado->id,
                'admin_id' => Auth::id(),
                'arquivo_path' => $certificado->arquivo_path
            ]);
            
            return redirect()->back()->with('success', 'Certificado excluído com sucesso!');
            
        } catch (\Exception $e) {
            Log::error('Erro ao excluir certificado', [
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
            
        // CORREÇÃO: Caminho correto do arquivo Vue
        return Inertia::render('MeusCertificados', [
            'certificados' => $certificados
        ]);
    }
    
    /**
     * Listar estrutura de certificados (para administradores)
     */
    public function listarEstrutura()
    {
        try {
            // Verificar permissão usando policy
            $this->authorize('viewAny', Certificado::class);
            
            $estrutura = CertificadoHelper::listarEstruturaCertificados();
            
            return Inertia::render('Admin/Certificados/Estrutura', [
                'estrutura' => $estrutura
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao listar estrutura de certificados', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);
            
            return back()->with('error', 'Erro ao carregar estrutura de certificados');
        }
    }
}