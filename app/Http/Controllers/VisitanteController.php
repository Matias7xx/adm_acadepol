<?php

namespace App\Http\Controllers;

use App\Models\Visitante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Mail\NovaReservaAlojamento;
use App\Mail\ReservaVisitanteAprovada;
use App\Mail\ReservaVisitanteRejeitada;
use App\Services\DataSanitizerService;
use App\Services\AuditLoggerService;

class VisitanteController extends Controller
{
    protected $sanitizer;
    protected $auditLogger;

    public function __construct(DataSanitizerService $sanitizer = null, AuditLoggerService $auditLogger = null)
    {
        $this->sanitizer = $sanitizer;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Buscar visitante por CPF para pré-preenchimento
     */
    public function buscarPorCpf(Request $request)
    {
        $request->validate([
            'cpf' => 'required|string'
        ]);

        $cpf = preg_replace('/[^0-9]/', '', $request->cpf);
        
        if (strlen($cpf) !== 11) {
            return response()->json([
                'success' => false,
                'message' => 'CPF inválido'
            ], 400);
        }

        $visitante = Visitante::findByCpf($cpf);

        if (!$visitante) {
            return response()->json([
                'success' => false,
                'message' => 'CPF não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'visitante' => [
                'nome' => $visitante->nome,
                'rg' => $visitante->rg,
                'orgao_expedidor_rg' => $visitante->orgao_expedidor_rg,
                'data_nascimento' => $visitante->data_nascimento->format('Y-m-d'),
                'sexo' => $visitante->sexo,
                'telefone' => $visitante->telefone,
                'email' => $visitante->email,
                'endereco' => $visitante->endereco,
                'orgao_trabalho' => $visitante->orgao_trabalho,
                'cargo' => $visitante->cargo,
                'matricula_funcional' => $visitante->matricula_funcional,
                'tipo_orgao' => $visitante->tipo_orgao,
            ]
        ]);
    }

    /**
     * Exibe o formulário de reserva para visitantes
     */
    public function formularioReserva()
    {
        $tiposOrgao = [
            'policia_civil' => 'Polícia Civil',
            'policia_militar' => 'Polícia Militar',
            'bombeiros' => 'Corpo de Bombeiros',
            'policia_federal' => 'Polícia Federal',
            'policia_rodoviaria' => 'Polícia Rodoviária Federal',
            'guarda_municipal' => 'Guarda Municipal',
            'poder_judiciario' => 'Poder Judiciário',
            'ministerio_publico' => 'Ministério Público',
            'defensoria_publica' => 'Defensoria Pública',
            'outro' => 'Outro'
        ];

        $condicoes = [
            'curso' => 'Participação em Curso',
            'trabalho' => 'Trabalho/Reunião',
            'visita_tecnica' => 'Visita Técnica',
            'evento' => 'Evento/Capacitação',
            'outro' => 'Outro'
        ];

        return Inertia::render('Components/FormularioVisitante', [
            'tiposOrgao' => $tiposOrgao,
            'condicoes' => $condicoes
        ]);
    }

    /**
     * Processa o formulário de reserva de visitante
     */
    public function store(Request $request)
    {
        // Validar os dados
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|max:20',
            'rg' => 'required|string|max:20',
            'orgao_expedidor_rg' => 'required|string|max:20',
            'data_nascimento' => 'required|date|before:today',
            'sexo' => 'required|in:masculino,feminino',
            'telefone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'endereco' => 'required|array',
            'endereco.rua' => 'required|string|max:255',
            'endereco.numero' => 'nullable|string|max:10',
            'endereco.bairro' => 'required|string|max:100',
            'endereco.cidade' => 'required|string|max:100',
            'endereco.uf' => 'required|string|size:2',
            'endereco.cep' => 'nullable|string|max:10',
            'orgao_trabalho' => 'required|string|max:255',
            'cargo' => 'required|string|max:255',
            'matricula_funcional' => 'nullable|string|max:50',
            'tipo_orgao' => 'required|string',
            'data_inicial' => 'required|date|after_or_equal:today',
            'data_final' => 'required|date|after_or_equal:data_inicial',
            'motivo' => 'required|string',
            'condicao' => 'required|string',
            'documento_identidade' => 'required|file|mimes:pdf|max:5120',
            'documento_funcional' => 'nullable|file|mimes:pdf|max:5120',
            'documento_comprobatorio' => 'nullable|file|mimes:pdf|max:5120',
            'aceita_termos' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verificar se já existe reserva no período
        $cpf = preg_replace('/[^0-9]/', '', $request->cpf);
        
        $reservaExistente = Visitante::where('cpf', $cpf)
            ->where('status', 'aprovada')
            ->where(function($query) use ($request) {
                $query->whereBetween('data_inicial', [$request->data_inicial, $request->data_final])
                    ->orWhereBetween('data_final', [$request->data_inicial, $request->data_final])
                    ->orWhere(function($query) use ($request) {
                        $query->where('data_inicial', '<=', $request->data_inicial)
                            ->where('data_final', '>=', $request->data_final);
                    });
            })
            ->exists();

        if ($reservaExistente) {
            return redirect()->back()
                ->with('error', 'Já existe uma reserva aprovada para este CPF no período solicitado.')
                ->withInput();
        }

        try {
            // Criar a reserva
            $visitante = new Visitante();
            
            // Campos básicos
            $visitante->nome = $request->nome;
            $visitante->cpf = $cpf;
            $visitante->rg = $request->rg;
            $visitante->orgao_expedidor_rg = $request->orgao_expedidor_rg;
            $visitante->data_nascimento = $request->data_nascimento;
            $visitante->sexo = $request->sexo;
            $visitante->telefone = $request->telefone;
            $visitante->email = $request->email;
            $visitante->endereco = $request->endereco;
            $visitante->orgao_trabalho = $request->orgao_trabalho;
            $visitante->cargo = $request->cargo;
            $visitante->matricula_funcional = $request->matricula_funcional;
            $visitante->tipo_orgao = $request->tipo_orgao;
            $visitante->data_inicial = $request->data_inicial;
            $visitante->data_final = $request->data_final;
            $visitante->motivo = $request->motivo;
            $visitante->condicao = $request->condicao;
            $visitante->ip = $request->ip();
            $visitante->user_agent = $request->userAgent();
            $visitante->status = 'pendente';

            // Processar uploads
            if ($request->hasFile('documento_identidade')) {
                $path = $request->file('documento_identidade')->store('documentos_visitantes/identidade', 'public');
                $visitante->documento_identidade = $path;
            }

            if ($request->hasFile('documento_funcional')) {
                $path = $request->file('documento_funcional')->store('documentos_visitantes/funcional', 'public');
                $visitante->documento_funcional = $path;
            }

            if ($request->hasFile('documento_comprobatorio')) {
                $path = $request->file('documento_comprobatorio')->store('documentos_visitantes/comprobatorio', 'public');
                $visitante->documento_comprobatorio = $path;
            }

            $visitante->save();

            // Enviar email para administrador
            $administradorEmail = config('alojamento.admin_email', 'matiasnobrega7@gmail.com');
            
            // Registrar na auditoria (se o serviço estiver disponível)
            if ($this->auditLogger) {
                $this->auditLogger->registrarAcao(
                    'Nova reserva de visitante criada',
                    'visitante',
                    [
                        'visitante_id' => $visitante->id,
                        'cpf' => $visitante->formatted_cpf,
                        'nome' => $visitante->nome,
                        'orgao' => $visitante->orgao_trabalho
                    ]
                );
            }

            // Armazenar detalhes na sessão
            session(['detalhes_reserva_visitante' => [
                'nome' => $visitante->nome,
                'cpf' => $visitante->formatted_cpf,
                'data_inicial' => $visitante->data_inicial->format('d/m/Y'),
                'data_final' => $visitante->data_final->format('d/m/Y'),
                'id' => $visitante->id,
                'created_at' => $visitante->created_at->format('d/m/Y H:i')
            ]]);

            return redirect()->route('visitante.confirmacao')
                ->with('message', 'Sua solicitação de reserva foi enviada com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro ao criar reserva de visitante: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Ocorreu um erro ao processar sua solicitação. Tente novamente.')
                ->withInput();
        }
    }

    /**
     * Página de confirmação
     */
    public function confirmacao()
    {
        return Inertia::render('Components/Confirmacao', [
            'user' => null,
            'mensagem' => 'Sua solicitação de reserva foi enviada com sucesso e será analisada em breve.',
            'detalhes' => session('detalhes_reserva_visitante'),
            'tipo' => 'visitante'
        ]);
    }

    /**
     * Lista de visitantes para administradores
     */
    public function index(Request $request)
    {
         $this->authorize('adminViewAny', Visitante::class);

        $visitantes = Visitante::query()
            ->when($request->search, function($query, $search) {
                return $query->where('nome', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('orgao_trabalho', 'like', "%{$search}%");
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->tipo_orgao, function($query, $tipo) {
                return $query->where('tipo_orgao', $tipo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->all());

        return Inertia::render('Admin/Visitantes/Index', [
            'visitantes' => $visitantes,
            'filters' => $request->only(['search', 'status', 'tipo_orgao'])
        ]);
    }

    /**
     * Exibir detalhes de um visitante para administradores
     */
    public function show(Visitante $visitante)
    {
         $this->authorize('adminView', $visitante);
        
        // Adicionar URLs dos documentos para o frontend
        $visitante->documento_url = $visitante->documento_identidade 
            ? asset('storage/' . $visitante->documento_identidade) 
            : null;
        $visitante->documento_identidade_url = $visitante->documento_identidade_url;
        $visitante->documento_funcional_url = $visitante->documento_funcional_url;
        $visitante->documento_comprobatorio_url = $visitante->documento_comprobatorio_url;
        
        // Adicionar tipo para diferenciação no frontend
        $visitante->tipo_reserva = 'visitante';
        
        // Padronizar campos para compatibilidade com o template
        $visitante->orgao = $visitante->orgao_trabalho;
        $visitante->matricula = $visitante->matricula_funcional;
        
        return Inertia::render('Admin/Alojamento/Show', [
            'reserva' => $visitante
        ]);
    }

    /**
     * Alterar o status de uma reserva de visitante
     */
    public function alterarStatus(Request $request, Visitante $visitante)
    {
         $this->authorize('adminUpdate', $visitante);
        
        $request->validate([
            'status' => ['required', Rule::in(['pendente', 'aprovada', 'rejeitada'])],
        ]);
        
        $novoStatus = $request->status;
        
        // Verificar se o status é diferente do atual
        if ($visitante->status === $novoStatus) {
            return redirect()->back()->with('message', 'O status já está definido como ' . $novoStatus);
        }
        
        // Motivo para rejeição
        if ($novoStatus === 'rejeitada' && !$request->has('motivo_rejeicao')) {
            return redirect()->back()->with('error', 'É necessário informar um motivo para rejeitar a reserva');
        }
        
        // Atualizar campos
        $dados = ['status' => $novoStatus];
        
        // Adicionar motivo de rejeição
        if ($novoStatus === 'rejeitada' && $request->has('motivo_rejeicao')) {
            $dados['motivo_rejeicao'] = $request->motivo_rejeicao;
        }
        
        $visitante->update($dados);
        
        // Enviar notificação por email
        try {
            if ($novoStatus === 'aprovada') {
                 Mail::to($visitante->email)->send(new ReservaVisitanteAprovada($visitante));
            } elseif ($novoStatus === 'rejeitada') {
                 Mail::to($visitante->email)->send(new ReservaVisitanteRejeitada($visitante));
            }
        } catch (\Exception $e) {
            // Log do erro mas não interrompe o processo
            \Log::error('Erro ao enviar email para visitante: ' . $e->getMessage());
        }
        
        if ($this->auditLogger) {
            $this->auditLogger->registrarAcao(
                "Status de reserva de visitante alterado para '{$novoStatus}'",
                'visitante',
                [
                    'visitante_id' => $visitante->id,
                    'status_anterior' => $visitante->getOriginal('status'),
                    'novo_status' => $novoStatus
                ]
            );
        }
        
        return redirect()->back()->with('message', 'Status da reserva alterado com sucesso para ' . $novoStatus);
    }

    /**
     * Aprovar reserva de visitante
     */
    public function aprovar(Visitante $visitante)
    {
         $this->authorize('adminUpdate', $visitante);

        if ($visitante->status !== 'pendente') {
            return redirect()->back()->with('error', 'Esta solicitação já foi processada.');
        }

        $visitante->update(['status' => 'aprovada']);

         Mail::to($visitante->email)->send(new ReservaVisitanteAprovada($visitante));

        if ($this->auditLogger) {
            $this->auditLogger->registrarAcao(
                'Reserva de visitante aprovada',
                'visitante',
                ['visitante_id' => $visitante->id]
            );
        }

        return redirect()->back()->with('message', 'Reserva aprovada com sucesso.');
    }

    /**
     * Rejeitar reserva de visitante
     */
    public function rejeitar(Request $request, Visitante $visitante)
    {
         $this->authorize('adminUpdate', $visitante);

        if ($visitante->status !== 'pendente') {
            return redirect()->back()->with('error', 'Esta solicitação já foi processada.');
        }

        $request->validate([
            'motivo_rejeicao' => 'required|string'
        ]);

        $visitante->update([
            'status' => 'rejeitada',
            'motivo_rejeicao' => $request->motivo_rejeicao
        ]);

         Mail::to($visitante->email)->send(new ReservaVisitanteRejeitada($visitante));

        if ($this->auditLogger) {
            $this->auditLogger->registrarAcao(
                'Reserva de visitante rejeitada',
                'visitante',
                [
                    'visitante_id' => $visitante->id,
                    'motivo' => $request->motivo_rejeicao
                ]
            );
        }

        return redirect()->back()->with('message', 'Reserva rejeitada com sucesso.');
    }

    /**
     * Gerar ficha de hospedagem para uma reserva de visitante aprovada
     */
    public function gerarFichaHospedagem(Visitante $visitante)
    {
        try {
             $this->authorize('adminView', $visitante);

            // Verificar se a reserva está aprovada
            if ($visitante->status !== 'aprovada') {
                return response()->json([
                    'error' => 'Apenas reservas aprovadas podem gerar ficha de hospedagem'
                ], 400);
            }

            // Formatar dados do endereço
            $endereco = $visitante->endereco ?? [];
            $enderecoFormatado = '';
            $bairro = '';
            $cidade = '';
            $uf = '';

            if (is_array($endereco) && !empty($endereco)) {
                $enderecoFormatado = ($endereco['rua'] ?? '') . 
                    (isset($endereco['numero']) && !empty($endereco['numero']) ? ', ' . $endereco['numero'] : '');
                $bairro = $endereco['bairro'] ?? '';
                $cidade = $endereco['cidade'] ?? '';
                $uf = $endereco['uf'] ?? '';
            }

            // Formatar datas
            $dataInicial = $visitante->data_inicial ? $visitante->data_inicial->format('d/m/Y') : '';
            $dataFinal = $visitante->data_final ? $visitante->data_final->format('d/m/Y') : '';

            $dados = [
                'nome' => $visitante->nome,
                'rg' => $visitante->rg ?? '',
                'orgao_expedidor' => $visitante->orgao_expedidor_rg ?? '',
                'cpf' => $visitante->cpf,
                'data_nascimento' => $visitante->data_nascimento ? $visitante->data_nascimento->format('d/m/Y') : '',
                'matricula' => $visitante->matricula_funcional,
                'sexo' => $visitante->sexo === 'masculino' ? 'M' : ($visitante->sexo === 'feminino' ? 'F' : ''),
                'cargo' => $visitante->cargo,
                'telefone' => $visitante->telefone,
                'email' => $visitante->email,
                'endereco' => $enderecoFormatado,
                'numero' => $endereco['numero'] ?? '',
                'bairro' => $bairro,
                'cidade' => $cidade,
                'uf' => $uf,
                'motivo' => $visitante->motivo,
                'orgao_instituicao' => $visitante->orgao_trabalho,
                'condicao' => $visitante->condicao,
                'data_inicial' => $dataInicial,
                'data_final' => $dataFinal,
                'apartamento' => '',
                'check_in_data' => '',
                'check_in_hora' => '',
                'check_out_data' => '',
                'check_out_hora' => '',
                'tipo_hospede' => 'Visitante',
            ];

            // Gerar HTML da ficha
            $html = view('ficha.hospedagem', $dados)->render();

            // Configurar DOMPDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Retornar o PDF para download
            return response($dompdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="ficha_hospedagem_visitante_'.$visitante->id.'.pdf"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao gerar ficha de hospedagem: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Visualizar ficha de hospedagem
     */
    public function visualizarFichaHospedagem(Visitante $visitante)
    {
        try {
             $this->authorize('adminView', $visitante);

            // Verificar se a reserva está aprovada
            if ($visitante->status !== 'aprovada') {
                return redirect()->back()->with('error', 'Apenas reservas aprovadas podem gerar ficha de hospedagem');
            }
            
            return $dompdf->stream('ficha_hospedagem_visitante_' . $visitante->id . '.pdf', [
                'Attachment' => false
            ]);
        } catch (\Exception $e) {
            return response()->view('errors.custom', [
                'message' => 'Erro ao gerar ficha de hospedagem: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}