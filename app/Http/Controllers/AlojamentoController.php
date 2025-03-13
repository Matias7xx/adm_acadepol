<?php

namespace App\Http\Controllers;

use App\Models\Alojamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\NovaReservaAlojamento;
use App\Mail\ReservaAlojamentoAprovada;
use App\Mail\ReservaAlojamentoRejeitada;
use Inertia\Inertia;

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
            'motivo' => 'required|string',
            'condicao' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefone' => 'required|string|max:20',
            'endereco' => 'array',
            'data_inicial' => 'required|date|after_or_equal:today',
            'data_final' => 'required|date|after_or_equal:data_inicial',
            'aceita_termos' => 'required|boolean|accepted',
        ]);

        // Criar a pré-reserva no banco de dados
        $alojamento = new Alojamento();
        $alojamento->user_id = Auth::id();
        $alojamento->nome = $request->nome;
        $alojamento->cargo = $request->cargo;
        $alojamento->matricula = $request->matricula;
        $alojamento->orgao = $request->orgao;
        $alojamento->cpf = $request->cpf;
        $alojamento->motivo = $request->motivo;
        $alojamento->condicao = $request->condicao;
        $alojamento->email = $request->email;
        $alojamento->telefone = $request->telefone;
        $alojamento->endereco = json_encode($request->endereco);
        $alojamento->data_inicial = $request->data_inicial;
        $alojamento->data_final = $request->data_final;
        $alojamento->status = 'pendente';
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

        // Retornar resposta de sucesso
        /* return redirect()->back()->with('message', 'Sua solicitação de pré-reserva foi enviada com sucesso e será analisada em breve.'); */
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
    public function index()
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $reservas = Alojamento::orderBy('created_at', 'desc')
            ->when(request('search'), function($query, $search) {
                return $query->where('nome', 'like', "%{$search}%")
                    ->orWhere('matricula', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when(request('status'), function($query, $status) {
                return $query->where('status', $status);
            })
            ->paginate(10)
            ->appends(request()->all());

        return Inertia::render('Admin/Alojamento/Index', [
            'reservas' => $reservas,
            'filters' => request()->only(['search', 'status'])
        ]);
    }

    /**
     * Exibe os detalhes de uma reserva (para administradores)
     */
    public function show(Alojamento $alojamento)
    {
        $this->authorize('adminView', $alojamento);

        return Inertia::render('Admin/Alojamento/Show', [
            'reserva' => $alojamento
        ]);
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
     * Listar minhas reservas (para usuários comuns)
     */
    public function minhasReservas()
    {
        $reservas = Alojamento::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Alojamento/MinhasReservas', [
            'reservas' => $reservas
        ]);
    }
}