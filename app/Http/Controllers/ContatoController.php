<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\NovoContato;
use App\Mail\RespostaContato;
use App\Services\DataSanitizerService;
use App\Services\AuditLoggerService;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ContatoController extends Controller
{
  protected $sanitizer;
  protected $auditLogger;

  public function __construct(
    DataSanitizerService $sanitizer,
    AuditLoggerService $auditLogger,
  ) {
    $this->sanitizer = $sanitizer;
    $this->auditLogger = $auditLogger;
  }

  /**
   * Exibe o formulário de contato
   */
  public function create()
  {
    $user = Auth::user();

    // Lista de assuntos
    $assuntos = [
      'Informações sobre cursos',
      'Dúvidas sobre matrícula',
      'Problemas no sistema',
      'Alojamento',
      'Certificados',
      'Outros assuntos',
    ];

    return Inertia::render('Components/FormularioContato', [
      'user' => $user,
      'assuntos' => $assuntos,
    ]);
  }

  /**
   * Processa o envio do formulário de contato
   */
  public function store(Request $request)
  {
    // Validação dos dados
    $validator = Validator::make($request->all(), [
      'nome' => 'required|string|max:255',
      'email' => 'required|email|max:255',
      'telefone' => 'nullable|string|max:20',
      'assunto' => 'required|string|max:255',
      'mensagem' => 'required|string|min:10',
    ]);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator)->withInput();
    }

    // Sanitização de dados
    $dados = $this->sanitizer->sanitizeForm($request->all(), [
      'nome' => 'string',
      'email' => 'email',
      'telefone' => 'string',
      'assunto' => 'string',
      'mensagem' => 'string',
    ]);

    // Adicionar informações adicionais
    $dados['ip'] = $request->ip();
    $dados['user_agent'] = $request->userAgent();
    $dados['user_id'] = Auth::id(); // será null se não estiver logado

    // Salvar no banco de dados
    $contato = Contato::create($dados);

    // Registrar na auditoria
    $this->auditLogger->registrarAcao(
      'Envio de mensagem de contato',
      'contato',
      [
        'contato_id' => $contato->id,
        'assunto' => $contato->assunto,
      ],
    );

    // Enviar e-mail para o administrador
    /* $administradorEmail = config(
      'contato.admin_email',
      'matiasnobrega7@gmail.com',
    );
    Mail::to($administradorEmail)->send(new NovoContato($contato)); */

    // Enviar cópia para o e-mail institucional
    $emailInstitucional = config(
      'contato.institutional_email',
      'acadepol@gmail.com',
    );
    Mail::to($emailInstitucional)->send(new NovoContato($contato));

    // Armazenar detalhes na sessão para a página de confirmação
    session([
      'detalhes_contato' => [
        'nome' => $contato->nome,
        'email' => $contato->email,
        'assunto' => $contato->assunto,
        'id' => $contato->id,
        'created_at' => $contato->created_at->format('d/m/Y H:i'),
      ],
    ]);

    return redirect()
      ->route('contato.confirmacao')
      ->with(
        'message',
        'Sua mensagem foi enviada com sucesso! Entraremos em contato em breve.',
      );
  }

  /**
   * Exibe a página de confirmação após o envio da mensagem
   */
  public function confirmacao()
  {
    return Inertia::render('Components/Confirmacao', [
      'user' => Auth::user(),
      'mensagem' => 'Sua mensagem foi recebida e será respondida em breve.',
      'detalhes' => session('detalhes_contato'),
      'tipo' => 'contato',
    ]);
  }

  /**
   * Área administrativa - Lista todas as mensagens de contato
   */
  public function index(Request $request)
  {
    $this->authorize('adminViewAny', Contato::class);

    $contatos = Contato::with(['usuario', 'respondente'])
      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('nome', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('assunto', 'like', "%{$search}%")
            ->orWhere('mensagem', 'like', "%{$search}%");
        });
      })
      ->when($request->status, function ($query, $status) {
        $query->where('status', $status);
      })
      ->orderBy('created_at', 'desc')
      ->paginate(10)
      ->withQueryString();

    return Inertia::render('Admin/Contato/Index', [
      'contatos' => $contatos,
      'filters' => $request->only(['search', 'status']),
      'can' => [
        'responder' => Auth::user()->can('admin user'),
        'delete' => Auth::user()->can('admin user'),
      ],
    ]);
  }

  /**
   * Área administrativa - Exibe uma mensagem específica
   */
  public function show(Contato $contato)
  {
    $this->authorize('adminView', $contato);

    $contato->load(['usuario', 'respondente']);

    return Inertia::render('Admin/Contato/Show', [
      'contato' => $contato,
      'can' => [
        'responder' => Auth::user()->can('responder', $contato),
        'delete' => Auth::user()->can('adminDelete', $contato),
      ],
    ]);
  }

  /**
   * Área administrativa - Responde a uma mensagem
   */
  public function responder(Request $request, Contato $contato)
  {
    $this->authorize('responder', $contato);

    $validator = Validator::make($request->all(), [
      'resposta' => 'required|string|min:10',
    ]);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator)->withInput();
    }

    // Atualizar o contato com a resposta
    $contato->update([
      'resposta' => $this->sanitizer->sanitizeHtml($request->resposta),
      'status' => 'respondido',
      'respondido_por' => Auth::id(),
      'data_resposta' => now(),
    ]);

    // Registrar na auditoria
    $this->auditLogger->registrarAcao(
      'Resposta enviada para mensagem de contato',
      'contato',
      [
        'contato_id' => $contato->id,
        'assunto' => $contato->assunto,
      ],
    );

    // Enviar e-mail com a resposta
    Mail::to($contato->email)->send(new RespostaContato($contato));

    return redirect()
      ->route('admin.contato.index')
      ->with('message', 'Resposta enviada com sucesso!');
  }

  /**
   * Área administrativa - Arquiva uma mensagem
   */
  public function arquivar(Contato $contato)
  {
    $this->authorize('adminUpdate', $contato);

    $contato->update([
      'status' => 'arquivado',
    ]);

    // Registrar na auditoria
    $this->auditLogger->registrarAcao(
      'Arquivamento de mensagem de contato',
      'contato',
      [
        'contato_id' => $contato->id,
        'assunto' => $contato->assunto,
      ],
    );

    return redirect()
      ->route('admin.contato.index')
      ->with('message', 'Mensagem arquivada com sucesso!');
  }

  public function alterarStatus(Request $request, Contato $contato)
  {
    $this->authorize('adminUpdate', $contato);

    $validator = Validator::make($request->all(), [
      'status' => [
        'required',
        Rule::in(['pendente', 'respondido', 'arquivado']),
      ],
      'resposta' => 'required_if:status,respondido|nullable|string|min:5',
    ]);

    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator)->withInput();
    }

    $novoStatus = $request->status;

    // Caso seja resposta, precisa ter o texto da resposta
    if ($novoStatus === 'respondido' && !$request->has('resposta')) {
      return redirect()
        ->back()
        ->with('error', 'É necessário fornecer uma resposta');
    }

    $dados = ['status' => $novoStatus];

    // Se for resposta, incluir os dados da resposta
    if ($novoStatus === 'respondido' && $request->has('resposta')) {
      $dados['resposta'] = $this->sanitizer->sanitizeHtml($request->resposta);
      $dados['respondido_por'] = Auth::id();
      $dados['data_resposta'] = now();

      // Enviar e-mail com a resposta
      Mail::to($contato->email)->send(new RespostaContato($contato));
    }

    // Atualizar o contato
    $contato->update($dados);

    // Registrar na auditoria
    $this->auditLogger->registrarAcao(
      "Alteração de status de mensagem de contato para '{$novoStatus}'",
      'contato',
      [
        'contato_id' => $contato->id,
        'status_anterior' => $contato->getOriginal('status'),
        'novo_status' => $novoStatus,
      ],
    );

    return redirect()
      ->back()
      ->with(
        'message',
        "Status da mensagem alterado para {$novoStatus} com sucesso!",
      );
  }

  public function retornarParaPendente(Contato $contato)
  {
    $this->authorize('adminUpdate', $contato);

    $contato->update([
      'status' => 'pendente',
    ]);

    // Registrar na auditoria
    $this->auditLogger->registrarAcao(
      'Mensagem retornada para pendente',
      'contato',
      [
        'contato_id' => $contato->id,
        'assunto' => $contato->assunto,
      ],
    );

    return redirect()
      ->back()
      ->with('message', 'Mensagem retornada para pendente com sucesso!');
  }

  /**
   * Área administrativa - Remove uma mensagem
   */
  public function destroy(Contato $contato)
  {
    $this->authorize('adminDelete', $contato);

    // Registrar na auditoria antes de excluir
    $this->auditLogger->registrarAcao(
      'Exclusão de mensagem de contato',
      'contato',
      [
        'contato_id' => $contato->id,
        'assunto' => $contato->assunto,
        'email' => $contato->email,
      ],
    );

    $contato->delete();

    return redirect()
      ->route('admin.contato.index')
      ->with('message', 'Mensagem excluída com sucesso!');
  }
}
