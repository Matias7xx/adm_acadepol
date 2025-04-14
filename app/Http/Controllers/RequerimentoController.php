<?php

namespace App\Http\Controllers;

use App\Models\Requerimento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Mail\NovoRequerimento;
use App\Mail\RequerimentoDeferido;
use App\Mail\RequerimentoIndeferido;
use App\Services\DataSanitizerService;
use App\Services\AuditLoggerService;
use Illuminate\Validation\Rule;

class RequerimentoController extends Controller
{
    protected $sanitizer;
    protected $auditLogger;

    public function __construct(DataSanitizerService $sanitizer, AuditLoggerService $auditLogger)
    {
        $this->sanitizer = $sanitizer;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Exibe o formulário de requerimento
     */
    public function create()
    {
        // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            // Armazenar a intenção
            session(['intended_route' => 'requerimentos.create']);
            session(['intended_acao' => 'criar_requerimento']);
            
            // Redirecionar para login com parâmetros
            return redirect()->route('login', [
                'intended_route' => 'requerimentos.create',
                'acao' => 'criar_requerimento'
            ])->withErrors([
                'unauthenticated' => 'Você precisa estar logado para fazer um requerimento.'
            ]);
        }

        $user = Auth::user();
        
        // Lista de tipos de requerimentos disponíveis
        $tiposRequerimento = [
            ['id' => 'segunda_via_certificado', 'nome' => '2ª Via de Certificado'],
            ['id' => 'declaracao_participacao', 'nome' => 'Declaração de Participação em Curso'],
            ['id' => 'outros', 'nome' => 'Outros']
        ];
        
        return Inertia::render('Components/FormularioRequerimento', [
            'user' => $user,
            'tiposRequerimento' => $tiposRequerimento
        ]);
    }

    /**
     * Processa o formulário de requerimento
     */
    public function store(Request $request)
    {
        // Verificar autenticação
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Validar os dados do formulário
        $validated = $request->validate([
            'tipo' => 'required|string|max:255',
            'nome' => 'required|string|max:255',
            'matricula' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefone' => 'required|string|max:20',
            'cpf' => 'nullable|string|max:20',
            'cargo' => 'nullable|string|max:255',
            'orgao' => 'nullable|string|max:255',
            'conteudo' => 'required|string',
            'dados_adicionais' => 'nullable|array',
            'documento' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        // Criar o requerimento no banco de dados
        $requerimento = new Requerimento();
        $requerimento->user_id = Auth::id();
        $requerimento->tipo = $request->tipo;
        $requerimento->nome = $request->nome;
        $requerimento->matricula = $request->matricula;
        $requerimento->email = $request->email;
        $requerimento->telefone = $request->telefone;
        $requerimento->cpf = $request->cpf;
        $requerimento->cargo = $request->cargo;
        $requerimento->orgao = $request->orgao;
        $requerimento->conteudo = $request->conteudo;
        $requerimento->dados_adicionais = $request->dados_adicionais ? json_encode($request->dados_adicionais) : null;
        $requerimento->status = 'pendente';

        // Processar upload de documento se fornecido
        if ($request->hasFile('documento')) {
            $path = $request->file('documento')->store('documentos_requerimentos', 'public');
            $requerimento->documento = $path;
        }
        
        $requerimento->save();

        // Enviar email para o administrador
        $administradorEmail = config('requerimento.admin_email', 'matiasnobrega7@gmail.com');
        Mail::to($administradorEmail)->send(new NovoRequerimento($requerimento));

        // Enviar cópia para o email institucional
        $emailInstitucional = config('requerimento.institutional_email', 'nobregamatias7@gmail.com');
        if ($emailInstitucional !== $administradorEmail) {
            Mail::to($emailInstitucional)->send(new NovoRequerimento($requerimento));
        }

        // Armazene detalhes importantes do requerimento na sessão
        session(['detalhes_requerimento' => [
            'nome' => $requerimento->nome,
            'tipo' => $this->getTipoRequerimentoFormatado($requerimento->tipo),
            'id' => $requerimento->id,
            'created_at' => $requerimento->created_at->format('d/m/Y H:i')
        ]]);

        return redirect()->route('requerimentos.confirmacao')
            ->with('message', 'Seu requerimento foi enviado com sucesso e será analisado em breve.');
    }

    /**
     * Exibe a página de confirmação após o envio do requerimento
     */
    public function confirmacao()
    {
        // Verificar se há dados da sessão
        if (!session()->has('detalhes_requerimento')) {
            return redirect()->route('home')
                ->with('error', 'Página de confirmação expirada ou inválida');
        }
        return Inertia::render('Components/Confirmacao', [
            'user' => Auth::user(),
            'mensagem' => 'Seu requerimento foi enviado com sucesso e será analisado em breve.',
            'detalhes' => session('detalhes_requerimento'),
            'tipo' => 'requerimento'
        ]);
    }

    /**
     * Exibe o painel de administração de requerimentos (apenas para administradores)
     */
    public function index(Request $request)
    {
        $this->authorize('adminViewAny', Requerimento::class);

        $requerimentos = Requerimento::orderBy('created_at', 'desc')
            ->when($request->search, function($query, $search) {
                return $query->where('nome', 'like', "%{$search}%")
                    ->orWhere('matricula', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('tipo', 'like', "%{$search}%");
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->tipo, function($query, $tipo) {
                return $query->where('tipo', $tipo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->all());

        // Formatar os tipos de requerimento para exibição
        $requerimentos->getCollection()->transform(function($item) {
            $item->tipo_formatado = $this->getTipoRequerimentoFormatado($item->tipo);
            return $item;
        });

        // Lista de tipos de requerimentos para filtro
        $tipos = [
            'segunda_via_certificado' => '2ª Via de Certificado',
            'declaracao_participacao' => 'Declaração de Participação em Curso',
            'outros' => 'Outros'
        ];

        return Inertia::render('Admin/Requerimentos/Index', [
            'requerimentos' => $requerimentos,
            'filters' => $request->only(['search', 'status', 'tipo']),
            'tipos' => $tipos
        ]);
    }

    /**
     * Exibe os detalhes de um requerimento (para administradores)
     */
    public function show(Requerimento $requerimento)
    {
        $this->authorize('adminView', $requerimento);

        // Adicionar informações formatadas e URL do documento para o frontend
        $requerimento->tipo_formatado = $this->getTipoRequerimentoFormatado($requerimento->tipo);
        $requerimento->documento_url = $requerimento->documento 
            ? asset('storage/' . $requerimento->documento) 
            : null;

        return Inertia::render('Admin/Requerimentos/Show', [
            'requerimento' => $requerimento
        ]);
    }

    /**
     * Deferir um requerimento
     */
        public function deferir(Request $request, Requerimento $requerimento)
    {
        $this->authorize('adminUpdate', $requerimento);

        if ($requerimento->status !== 'pendente') {
            return redirect()->back()->with('error', 'Este requerimento já foi processado.');
        }

        $request->validate([
            'resposta' => 'required|string',
            'documento_resposta' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $requerimento->status = 'deferido';
        $requerimento->resposta = $request->resposta;
        $requerimento->data_resposta = now();
        
        // Processar upload de documento de resposta se fornecido
        if ($request->hasFile('documento_resposta')) {
            $path = $request->file('documento_resposta')->store('documentos_respostas', 'public');
            $requerimento->documento_resposta = $path;
        }
        
        $requerimento->save();

        // Enviar email de deferimento para o usuário com documento anexado
        Mail::to($requerimento->email)->send(new RequerimentoDeferido($requerimento));

        // Registrar na auditoria
        $this->auditLogger->registrarAcao(
            'Requerimento deferido', 
            'requerimento', 
            [
                'requerimento_id' => $requerimento->id,
                'tipo' => $requerimento->tipo,
                'admin_id' => Auth::id()
            ]
        );

        return redirect()->back()->with('message', 'Requerimento deferido com sucesso.');
    }

    /**
     * Indeferir um requerimento
     */
    public function indeferir(Request $request, Requerimento $requerimento)
    {
        $this->authorize('adminUpdate', $requerimento);

        if ($requerimento->status !== 'pendente') {
            return redirect()->back()->with('error', 'Este requerimento já foi processado.');
        }

        $request->validate([
            'motivo_indeferimento' => 'required|string'
        ]);

        $requerimento->status = 'indeferido';
        $requerimento->motivo_indeferimento = $request->motivo_indeferimento;
        $requerimento->data_resposta = now();
        $requerimento->save();

        // Enviar email de indeferimento para o usuário
        Mail::to($requerimento->email)->send(new RequerimentoIndeferido($requerimento));

        // Registrar na auditoria
        $this->auditLogger->registrarAcao(
            'Requerimento indeferido', 
            'requerimento', 
            [
                'requerimento_id' => $requerimento->id,
                'tipo' => $requerimento->tipo,
                'admin_id' => Auth::id(),
                'motivo' => $requerimento->motivo_indeferimento
            ]
        );

        return redirect()->back()->with('message', 'Requerimento indeferido com sucesso.');
    }

    /**
     * Alterar o status de um requerimento
     */
    public function alterarStatus(Request $request, Requerimento $requerimento)
    {
        $this->authorize('adminUpdate', $requerimento);
        
        $request->validate([
            'status' => ['required', Rule::in(['pendente', 'deferido', 'indeferido'])],
            'resposta' => 'required_if:status,deferido|nullable|string',
            'motivo_indeferimento' => 'required_if:status,indeferido|nullable|string',
        ]);
        
        $novoStatus = $request->status;
        
        // Verificar se o status é diferente do atual
        if ($requerimento->status === $novoStatus) {
            return redirect()->back()->with('message', 'O status já está definido como ' . $novoStatus);
        }
        
        // Preparar dados para atualização
        $dados = [
            'status' => $novoStatus,
            'data_resposta' => now()
        ];
        
        // Dependendo do status, adicionar campos específicos
        if ($novoStatus === 'deferido' && $request->has('resposta')) {
            $dados['resposta'] = $request->resposta;
        } elseif ($novoStatus === 'indeferido' && $request->has('motivo_indeferimento')) {
            $dados['motivo_indeferimento'] = $request->motivo_indeferimento;
        }
        
        $requerimento->update($dados);
        
        // Enviar notificação por email conforme o status
        if ($novoStatus === 'deferido') {
            Mail::to($requerimento->email)->send(new RequerimentoDeferido($requerimento));
        } elseif ($novoStatus === 'indeferido') {
            Mail::to($requerimento->email)->send(new RequerimentoIndeferido($requerimento));
        }
        
        // Registrar na auditoria
        $this->auditLogger->registrarAcao(
            'Status de requerimento alterado para ' . $novoStatus, 
            'requerimento', 
            [
                'requerimento_id' => $requerimento->id,
                'tipo' => $requerimento->tipo,
                'status_anterior' => $requerimento->getOriginal('status'),
                'novo_status' => $novoStatus,
                'admin_id' => Auth::id()
            ]
        );
        
        return redirect()->back()->with('message', 'Status do requerimento alterado com sucesso para ' . $novoStatus);
    }

    /**
     * Listar meus requerimentos (para usuários comuns no futuro)
     */
    public function meusRequerimentos()
    {
        // Verificar autenticação
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $requerimentos = Requerimento::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Formatar os tipos de requerimento para exibição
        $requerimentos->getCollection()->transform(function($item) {
            $item->tipo_formatado = $this->getTipoRequerimentoFormatado($item->tipo);
            return $item;
        });

        return Inertia::render('Requerimentos/MeusRequerimentos', [
            'requerimentos' => $requerimentos
        ]);
    }

    /**
     * Obter o nome formatado do tipo de requerimento
     */
    private function getTipoRequerimentoFormatado($tipo)
    {
        $tipos = [
            'segunda_via_certificado' => '2ª Via de Certificado',
            'declaracao_participacao' => 'Declaração de Participação em Curso',
            'outros' => 'Outros'
        ];

        return $tipos[$tipo] ?? $tipo;
    }
}