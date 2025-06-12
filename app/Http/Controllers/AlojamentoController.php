<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\Alojamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\NovaReservaAlojamento;
use App\Mail\ReservaAlojamentoAprovada;
use App\Mail\ReservaAlojamentoRejeitada;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;

class AlojamentoController extends Controller
{
    /**
     * Constructor para aplicar middleware de autenticação
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Exibe o formulário de pré-reserva de alojamento
     */
    public function reservaForm()
    {
         // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            // Armazenar a intenção
            session(['intended_route' => 'alojamento.reserva.form']);
            session(['intended_acao' => 'reserva_alojamento']);
            
            // Redirecionar para login com parâmetros
            return redirect()->route('login', [
                'intended_route' => 'alojamento.reserva.form',
                'acao' => 'reserva_alojamento'
            ])->withErrors([
                'unauthenticated' => 'Você precisa estar logado para solicitar alojamento.'
            ]);
        }

        return Inertia::render('Components/FormularioAlojamento', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Processa o formulário de pré-reserva de alojamento
     */
    public function store(Request $request)
    {
        // Verificar autenticação
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Validar os dados do formulário
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cargo' => 'required|string|max:255',
            'matricula' => 'required|string|max:255',
            'orgao' => 'required|string|max:255',
            'cpf' => 'required|string|max:20',
            'data_nascimento' => 'nullable|date',
            'rg' => 'nullable|string|max:20',
            'orgao_expedidor' => 'nullable|string|max:20',
            'sexo' => 'nullable|string|in:masculino,feminino',
            'uf' => 'nullable|string|max:2',
            'motivo' => 'required|string',
            'condicao' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefone' => 'required|string|max:20',
            'endereco' => 'required|array',
            'endereco.rua' => 'nullable|string|max:255',
            'endereco.numero' => 'nullable|string|max:10',
            'endereco.bairro' => 'required|string|max:100',
            'endereco.cidade' => 'required|string|max:100',
            'endereco.cep' => 'nullable|string|max:10',
            'data_inicial' => 'required|date|after_or_equal:today',
            'data_final' => 'required|date|after_or_equal:data_inicial',
            'aceita_termos' => 'required|boolean|accepted',
            'documento_comprobatorio' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Criar a pré-reserva no banco de dados
        $alojamento = new Alojamento();
        $alojamento->user_id = Auth::id();
        $alojamento->nome = $request->nome;
        $alojamento->cargo = $request->cargo;
        $alojamento->matricula = $request->matricula;
        $alojamento->orgao = $request->orgao;
        $alojamento->cpf = $request->cpf;
        $alojamento->data_nascimento = $request->data_nascimento;
        $alojamento->rg = $request->rg;
        $alojamento->orgao_expedidor = $request->orgao_expedidor;
        $alojamento->sexo = $request->sexo;
        $alojamento->uf = $request->uf;
        $alojamento->motivo = $request->motivo;
        $alojamento->condicao = $request->condicao;
        $alojamento->email = $request->email;
        $alojamento->telefone = $request->telefone;
        $alojamento->endereco = json_encode($request->endereco);
        $alojamento->data_inicial = $request->data_inicial;
        $alojamento->data_final = $request->data_final;
        $alojamento->status = 'pendente';

        // Processar upload de documento se fornecido
        if ($request->hasFile('documento_comprobatorio')) {
            $path = $request->file('documento_comprobatorio')->store('documentos_alojamento', 'public');
            $alojamento->documento_comprobatorio = $path;
        }
        
        $alojamento->save();

        // Enviar email para o administrador
        $administradorEmail = config('alojamento.admin_email', 'matiasnobrega7@gmail.com');
        Mail::to($administradorEmail)->send(new NovaReservaAlojamento($alojamento));

        // Enviar cópia para o email institucional
        $emailInstitucional = config('alojamento.institutional_email', 'nobregamatias7@gmail.com');
        if ($emailInstitucional !== $administradorEmail) {
            Mail::to($emailInstitucional)->send(new NovaReservaAlojamento($alojamento));
        }

        // Armazene detalhes importantes da reserva na sessão
        session(['detalhes_reserva' => [
            'nome' => $alojamento->nome,
            'data_inicial' => $alojamento->data_inicial->format('d/m/Y'),
            'data_final' => $alojamento->data_final->format('d/m/Y'),
            'id' => $alojamento->id,
            'created_at' => $alojamento->created_at->format('d/m/Y H:i')
        ]]);

        return redirect()->route('alojamento.confirmacao')
            ->with('message', 'Sua solicitação de pré-reserva foi enviada com sucesso e será analisada em breve.');
    }

    /**
     * Exibe a página de confirmação após o envio da solicitação
     */
    public function confirmacao(Request $request)
    {
        // Verificar autenticação
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        return Inertia::render('Components/Confirmacao', [
            'user' => Auth::user(),
            'mensagem' => 'Sua solicitação de pré-reserva foi enviada com sucesso e será analisada em breve.',
            'detalhes' => session('detalhes_reserva')
        ]);
    }

    /**
     * Exibe o painel de administração de reservas (apenas para administradores)
     */
    public function index(Request $request)
    {
        $this->authorize('adminViewAny', Alojamento::class);

        // Buscar reservas de usuários
        $reservasUsuarios = Alojamento::with('usuario')
            ->when($request->search, function($query, $search) {
                return $query->where('nome', 'like', "%{$search}%")
                    ->orWhere('matricula', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('usuario', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('matricula', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->get()
            ->map(function($reserva) {
                return [
                    'id' => $reserva->id,
                    'tipo' => 'usuario',
                    'nome' => $reserva->nome,
                    'email' => $reserva->email,
                    'telefone' => $reserva->telefone,
                    'orgao' => $reserva->orgao,
                    'cargo' => $reserva->cargo,
                    'matricula' => $reserva->matricula,
                    'cpf' => $reserva->cpf,
                    'data_inicial' => $reserva->data_inicial,
                    'data_final' => $reserva->data_final,
                    'motivo' => $reserva->motivo,
                    'condicao' => $reserva->condicao,
                    'status' => $reserva->status,
                    'motivo_rejeicao' => $reserva->motivo_rejeicao,
                    'created_at' => $reserva->created_at,
                    'updated_at' => $reserva->updated_at,
                    'duracao' => $reserva->duracao,
                    'endereco_formatado' => $reserva->endereco_formatado,
                    'documento_url' => $reserva->documento_url,
                    'usuario_sistema' => $reserva->usuario ? [
                        'id' => $reserva->usuario->id,
                        'name' => $reserva->usuario->name,
                        'matricula' => $reserva->usuario->matricula,
                        'email' => $reserva->usuario->email,
                    ] : null,
                ];
            });

        // Buscar reservas de visitantes
        $reservasVisitantes = \App\Models\Visitante::query()
            ->when($request->search, function($query, $search) {
                return $query->where('nome', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('orgao_trabalho', 'like', "%{$search}%");
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->get()
            ->map(function($visitante) {
                return [
                    'id' => $visitante->id,
                    'tipo' => 'visitante',
                    'nome' => $visitante->nome,
                    'email' => $visitante->email,
                    'telefone' => $visitante->telefone,
                    'orgao' => $visitante->orgao_trabalho,
                    'cargo' => $visitante->cargo,
                    'matricula' => $visitante->matricula_funcional,
                    'cpf' => $visitante->cpf,
                    'data_inicial' => $visitante->data_inicial,
                    'data_final' => $visitante->data_final,
                    'motivo' => $visitante->motivo,
                    'condicao' => $visitante->condicao,
                    'status' => $visitante->status,
                    'motivo_rejeicao' => $visitante->motivo_rejeicao,
                    'created_at' => $visitante->created_at,
                    'updated_at' => $visitante->updated_at,
                    'duracao' => $visitante->duracao,
                    'endereco_formatado' => $visitante->endereco_formatado,
                    'documento_url' => $visitante->documento_identidade_url,
                    'tipo_orgao' => $visitante->tipo_orgao,
                    'rg' => $visitante->rg,
                    'orgao_expedidor_rg' => $visitante->orgao_expedidor_rg,
                    'data_nascimento' => $visitante->data_nascimento,
                    'sexo' => $visitante->sexo,
                    'documento_funcional_url' => $visitante->documento_funcional_url,
                    'documento_comprobatorio_url' => $visitante->documento_comprobatorio_url,
                ];
            });

        // Combinar as duas coleções
        $todasReservas = $reservasUsuarios->concat($reservasVisitantes);

        // Ordenar por data de criação (mais recentes primeiro)
        $todasReservas = $todasReservas->sortByDesc('created_at');

        // paginação manual
        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $total = $todasReservas->count();
        $items = $todasReservas->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Criar objeto de paginação manual
        $paginatedReservas = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
        
        $paginatedReservas->appends($request->all());

        return Inertia::render('Admin/Alojamento/Index', [
            'reservas' => $paginatedReservas,
            'filters' => $request->only(['search', 'status']),
            'estatisticas' => [
                'total_usuarios' => $reservasUsuarios->count(),
                'total_visitantes' => $reservasVisitantes->count(),
                'total_pendentes' => $todasReservas->where('status', 'pendente')->count(),
                'total_aprovadas' => $todasReservas->where('status', 'aprovada')->count(),
                'total_rejeitadas' => $todasReservas->where('status', 'rejeitada')->count(),
            ]
        ]);
    }

    /**
     * Exibe os detalhes de uma reserva (para administradores)
     */
    public function show(Alojamento $alojamento)
    {
        $this->authorize('adminView', $alojamento);
        
        // Adicionar URL do documento para o frontend
        $alojamento->documento_url = $alojamento->documento_comprobatorio 
            ? asset('storage/' . $alojamento->documento_comprobatorio) 
            : null;
            
        // Adicionar tipo para diferenciação no frontend
        $alojamento->tipo_reserva = 'usuario';
        
        return Inertia::render('Admin/Alojamento/Show', [
            'reserva' => $alojamento
        ]);
    }

    public function showReserva($tipo, $id)
    {
        if ($tipo === 'usuario') {
            $reserva = Alojamento::findOrFail($id);
            $this->authorize('adminView', $reserva);
            
            // Adicionar informações específicas para usuários
            $reserva->documento_url = $reserva->documento_comprobatorio 
                ? asset('storage/' . $reserva->documento_comprobatorio) 
                : null;
                
            $reserva->tipo_reserva = 'usuario';
            
            return Inertia::render('Admin/Alojamento/Show', [
                'reserva' => $reserva
            ]);
            
        } elseif ($tipo === 'visitante') {
            $reserva = \App\Models\Visitante::findOrFail($id);
            
             $this->authorize('adminView', $reserva);
            
            // Adicionar URLs dos documentos específicos de visitantes
            $reserva->documento_url = $reserva->documento_identidade 
                ? asset('storage/' . $reserva->documento_identidade) 
                : null;
            $reserva->documento_identidade_url = $reserva->documento_identidade_url;
            $reserva->documento_funcional_url = $reserva->documento_funcional_url;
            $reserva->documento_comprobatorio_url = $reserva->documento_comprobatorio_url;
            
            // Adicionar tipo para diferenciação no frontend
            $reserva->tipo_reserva = 'visitante';
            
            // Padronizar campos para compatibilidade com o template
            $reserva->orgao = $reserva->orgao_trabalho;
            $reserva->matricula = $reserva->matricula_funcional;
            
            return Inertia::render('Admin/Alojamento/Show', [
                'reserva' => $reserva
            ]);
        }
        
        abort(404, 'Tipo de reserva inválido');
    }

    /**
     * Aprovar uma solicitação de reserva
     */
    public function aprovar(Alojamento $alojamento)
    {
        $this->authorize('adminUpdate', $alojamento);

        if ($alojamento->status !== 'pendente') {
            return redirect()->back()->with('error', 'Esta solicitação já foi processada.');
        }

        $alojamento->status = 'aprovada';
        $alojamento->save();

        // Enviar email de confirmação para o usuário
        Mail::to($alojamento->email)->send(new ReservaAlojamentoAprovada($alojamento));

        return redirect()->back()->with('message', 'Reserva aprovada com sucesso.');
    }

    /**
     * Rejeitar uma solicitação de reserva
     */
    public function rejeitar(Request $request, Alojamento $alojamento)
    {
        $this->authorize('adminUpdate', $alojamento);

        if ($alojamento->status !== 'pendente') {
            return redirect()->back()->with('error', 'Esta solicitação já foi processada.');
        }

        $request->validate([
            'motivo_rejeicao' => 'required|string'
        ]);

        $alojamento->status = 'rejeitada';
        $alojamento->motivo_rejeicao = $request->motivo_rejeicao;
        $alojamento->save();

        // Enviar email de rejeição para o usuário
        Mail::to($alojamento->email)->send(new ReservaAlojamentoRejeitada($alojamento));

        return redirect()->back()->with('message', 'Reserva rejeitada com sucesso.');
    }

    /**
     * Alterar o status de uma reserva
     */
    public function alterarStatus(Request $request, Alojamento $alojamento)
    {
        $this->authorize('adminUpdate', $alojamento);
        
        $request->validate([
            'status' => ['required', Rule::in(['pendente', 'aprovada', 'rejeitada'])],
        ]);
        
        $novoStatus = $request->status;
        
        // Verificar se o status é diferente do atual
        if ($alojamento->status === $novoStatus) {
            return redirect()->back()->with('message', 'O status já está definido como ' . $novoStatus);
        }
        
        // Para rejeição, precisamos de um motivo
        if ($novoStatus === 'rejeitada' && !$request->has('motivo_rejeicao')) {
            return redirect()->back()->with('error', 'É necessário informar um motivo para rejeitar a reserva');
        }
        
        // Atualizar campos conforme o status
        $dados = ['status' => $novoStatus];
        
        // Adicionar motivo de rejeição se for o caso
        if ($novoStatus === 'rejeitada' && $request->has('motivo_rejeicao')) {
            $dados['motivo_rejeicao'] = $request->motivo_rejeicao;
        }
        
        $alojamento->update($dados);
        
        // Enviar notificação por email conforme o status
        if ($novoStatus === 'aprovada') {
            Mail::to($alojamento->email)->send(new ReservaAlojamentoAprovada($alojamento));
        } elseif ($novoStatus === 'rejeitada') {
            Mail::to($alojamento->email)->send(new ReservaAlojamentoRejeitada($alojamento));
        }
        
        return redirect()->back()->with('message', 'Status da reserva alterado com sucesso para ' . $novoStatus);
    }

    /**
     * Gerar ficha de hospedagem para uma reserva aprovada
     */
    public function gerarFichaHospedagem(Alojamento $alojamento)
    {
        try {
            $this->authorize('adminView', $alojamento);

            // Verificar se a reserva está aprovada
            if ($alojamento->status !== 'aprovada') {
                return response()->json([
                    'error' => 'Apenas reservas aprovadas podem gerar ficha de hospedagem'
                ], 400);
            }

            // Formatar dados do endereço
            $endereco = json_decode($alojamento->endereco, true) ?? [];
            $enderecoFormatado = '';
            $bairro = '';
            $cidade = '';
            $cep = '';
            $uf = $alojamento->uf ?? '';

            if (!empty($endereco)) {
                $enderecoFormatado = $endereco['rua'] ?? ''; // SEM o número
                $bairro = $endereco['bairro'] ?? '';
                $cidade = $endereco['cidade'] ?? '';
                $cep = $endereco['cep'] ?? '';
            }

            // Formatar datas
            $dataInicial = $alojamento->data_inicial ? $alojamento->data_inicial->format('d/m/Y') : '';
            $dataFinal = $alojamento->data_final ? $alojamento->data_final->format('d/m/Y') : '';

            // Preparar os dados para o template
            $dados = [
                'nome' => $alojamento->nome,
                'rg' => $alojamento->rg ?? '',
                'orgao_expedidor' => $alojamento->orgao_expedidor ?? '',
                'cpf' => $alojamento->cpf,
                'data_nascimento' => $alojamento->data_nascimento ? $alojamento->data_nascimento->format('d/m/Y') : '',
                'matricula' => $alojamento->matricula,
                'sexo' => $alojamento->sexo === 'masculino' ? 'M' : ($alojamento->sexo === 'feminino' ? 'F' : ''),
                'cargo' => $alojamento->cargo,
                'telefone' => $alojamento->telefone,
                'email' => $alojamento->email,
                'endereco' => $enderecoFormatado,
                'numero' => $endereco['numero'] ?? '',
                'bairro' => $bairro,
                'cidade' => $cidade,
                'cep' => $cep,
                'uf' => $uf,
                'motivo' => $alojamento->motivo,
                'orgao_instituicao' => $alojamento->orgao,
                'condicao' => $alojamento->condicao,
                'data_inicial' => $dataInicial,
                'data_final' => $dataFinal,
                'apartamento' => '', // Será preenchido manualmente
                'check_in_data' => '', // Será preenchido na chegada
                'check_in_hora' => '', // Será preenchido na chegada
                'check_out_data' => '', // Será preenchido na saída
                'check_out_hora' => '', // Será preenchido na saída
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
                ->header('Content-Disposition', 'attachment; filename="ficha_hospedagem_'.$alojamento->id.'.pdf"');
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
    public function visualizarFichaHospedagem(Alojamento $alojamento)
    {
        try {
            $this->authorize('adminView', $alojamento);

            // Verificar se a reserva está aprovada
            if ($alojamento->status !== 'aprovada') {
                return redirect()->back()->with('error', 'Apenas reservas aprovadas podem gerar ficha de hospedagem');
            }

            // Formatar dados do endereço
            $endereco = json_decode($alojamento->endereco, true) ?? [];
            $enderecoFormatado = '';
            $bairro = '';
            $cidade = '';
            $cep = '';
            $uf = $alojamento->uf ?? '';

            if (!empty($endereco)) {
                $enderecoFormatado = $endereco['rua'] ?? ''; // SEM o número
                $bairro = $endereco['bairro'] ?? '';
                $cidade = $endereco['cidade'] ?? '';
                $cep = $endereco['cep'] ?? '';
            }

            // Formatar datas
            $dataInicial = $alojamento->data_inicial ? $alojamento->data_inicial->format('d/m/Y') : '';
            $dataFinal = $alojamento->data_final ? $alojamento->data_final->format('d/m/Y') : '';

            // Preparar os dados para o template
            $dados = [
                'nome' => $alojamento->nome,
                'rg' => $alojamento->rg ?? '',
                'orgao_expedidor' => $alojamento->orgao_expedidor ?? '',
                'cpf' => $alojamento->cpf,
                'data_nascimento' => $alojamento->data_nascimento ? $alojamento->data_nascimento->format('d/m/Y') : '',
                'matricula' => $alojamento->matricula,
                'sexo' => $alojamento->sexo === 'masculino' ? 'M' : ($alojamento->sexo === 'feminino' ? 'F' : ''),
                'cargo' => $alojamento->cargo,
                'telefone' => $alojamento->telefone,
                'email' => $alojamento->email,
                'endereco' => $enderecoFormatado,
                'numero' => $endereco['numero'] ?? '',
                'bairro' => $bairro,
                'cidade' => $cidade,
                'cep' => $cep,
                'uf' => $uf,
                'motivo' => $alojamento->motivo,
                'orgao_instituicao' => $alojamento->orgao,
                'condicao' => $alojamento->condicao,
                'data_inicial' => $dataInicial,
                'data_final' => $dataFinal,
                'apartamento' => '',
                'check_in_data' => '',
                'check_in_hora' => '',
                'check_out_data' => '',
                'check_out_hora' => '',
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

            return $dompdf->stream('ficha_hospedagem_' . $alojamento->id . '.pdf', [
                'Attachment' => false
            ]);
        } catch (\Exception $e) {
            // Em caso de erro, exibir uma mensagem de erro
            return response()->view('errors.custom', [
                'message' => 'Erro ao gerar ficha de hospedagem: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}