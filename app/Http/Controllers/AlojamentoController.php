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
            'endereco' => 'array',
            'data_inicial' => 'required|date|after_or_equal:today',
            'data_final' => 'required|date|after_or_equal:data_inicial',
            'aceita_termos' => 'required|boolean|accepted',
            'documento_comprobatorio' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
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

        // Adicionar URL do documento para o frontend
        $alojamento->documento_url = $alojamento->documento_comprobatorio 
            ? asset('storage/' . $alojamento->documento_comprobatorio) 
            : null;

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
        $uf = $alojamento->uf ?? '';

        if (!empty($endereco)) {
            $enderecoFormatado = ($endereco['rua'] ?? '') . 
                (isset($endereco['numero']) && !empty($endereco['numero']) ? ', ' . $endereco['numero'] : '');
            $bairro = $endereco['bairro'] ?? '';
            $cidade = $endereco['cidade'] ?? '';
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

        // Retornar o PDF diretamente para download
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
        $uf = $alojamento->uf ?? '';

        if (!empty($endereco)) {
            $enderecoFormatado = ($endereco['rua'] ?? '') . 
                (isset($endereco['numero']) && !empty($endereco['numero']) ? ', ' . $endereco['numero'] : '');
            $bairro = $endereco['bairro'] ?? '';
            $cidade = $endereco['cidade'] ?? '';
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