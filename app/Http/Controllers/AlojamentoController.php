<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\Alojamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\NovaReservaAlojamento;
use App\Mail\ReservaAlojamentoAprovada;
use App\Mail\ReservaAlojamentoRejeitada;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use App\Models\Dormitorio;
use App\Models\Ocupacao;

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
      return redirect()
        ->route('login', [
          'intended_route' => 'alojamento.reserva.form',
          'acao' => 'reserva_alojamento',
        ])
        ->withErrors([
          'unauthenticated' =>
            'Você precisa estar logado para solicitar alojamento.',
        ]);
    }

    return Inertia::render('Components/FormularioAlojamento', [
      'user' => Auth::user(),
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
      'data_inicial' => 'required|date|after_or_equal:today',
      'data_final' => 'required|date|after_or_equal:data_inicial',
      'motivo' => 'required|string',
      'condicao' => 'required|string|max:255',
      'aceita_termos' => 'required|boolean|accepted',
      'documento_comprobatorio' => 'nullable|file|mimes:pdf|max:10240',
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
      'email' => 'required|email|max:255',
      'telefone' => 'required|string|max:20',
      'endereco' => 'required|array',
      'endereco.rua' => 'nullable|string|max:255',
      'endereco.numero' => 'nullable|string|max:10',
      'endereco.bairro' => 'required|string|max:100',
      'endereco.cidade' => 'required|string|max:100',
      'endereco.cep' => 'nullable|string|max:10',
    ]);

    $user = Auth::user();
    $userId = $user->id;

    //VERIFICAR RESERVAS APROVADAS NO PERÍODO
    $reservaAprovadaExistente = Alojamento::where('user_id', $userId)
      ->where('status', 'aprovada')
      ->where(function ($query) use ($request) {
        $query
          ->whereBetween('data_inicial', [
            $request->data_inicial,
            $request->data_final,
          ])
          ->orWhereBetween('data_final', [
            $request->data_inicial,
            $request->data_final,
          ])
          ->orWhere(function ($query) use ($request) {
            $query
              ->where('data_inicial', '<=', $request->data_inicial)
              ->where('data_final', '>=', $request->data_final);
          });
      })
      ->exists();

    if ($reservaAprovadaExistente) {
      throw ValidationException::withMessages([
        'message' => 'Você já possui uma reserva aprovada para este período.',
      ]);
    }

    //VERIFICAR RESERVAS PENDENTES
    $reservaPendenteExistente = Alojamento::where('user_id', $userId)
      ->where('status', 'pendente')
      ->exists();

    if ($reservaPendenteExistente) {
      $reservaPendente = Alojamento::where('user_id', $userId)
        ->where('status', 'pendente')
        ->latest('created_at')
        ->first();

      $mensagemErro =
        'Você já possui uma reserva pendente de análise criada em ' .
        $reservaPendente->created_at->format('d/m/Y H:i') .
        ' para o período de ' .
        $reservaPendente->data_inicial->format('d/m/Y') .
        ' a ' .
        $reservaPendente->data_final->format('d/m/Y') .
        '. Aguarde a análise ou entre em contato com a administração.';

      throw ValidationException::withMessages([
        'message' => $mensagemErro,
      ]);
    }

    try {
      DB::beginTransaction();

      //ATUALIZAR PERFIL DO USUÁRIO com dados do formulário
      $user->update([
        'name' => $request->nome,
        'cargo' => $request->cargo,
        'matricula' => $request->matricula,
        'orgao' => $request->orgao,
        'cpf' => preg_replace('/[^0-9]/', '', $request->cpf),
        'data_nascimento' => $request->data_nascimento,
        'rg' => $request->rg,
        'orgao_expedidor' => $request->orgao_expedidor,
        'sexo' => $request->sexo,
        'uf' => $request->uf,
        'email' => $request->email,
        'telefone' => $request->telefone,
        'endereco' => $request->endereco,
      ]);

      //CRIAR RESERVA (sem duplicar dados pessoais)
      $alojamento = new Alojamento();
      $alojamento->user_id = $userId;

      // Dados básicos da reserva referenciando o usuário
      $alojamento->nome = $user->name;
      $alojamento->cargo = $user->cargo;
      $alojamento->matricula = $user->matricula;
      $alojamento->orgao = $user->orgao;
      $alojamento->cpf = $user->cpf;
      $alojamento->data_nascimento = $user->data_nascimento;
      $alojamento->rg = $user->rg;
      $alojamento->orgao_expedidor = $user->orgao_expedidor;
      $alojamento->sexo = $user->sexo;
      $alojamento->uf = $user->uf;
      $alojamento->email = $user->email;
      $alojamento->telefone = $user->telefone;
      $alojamento->endereco = json_encode($user->endereco);

      // Dados específicos da reserva
      $alojamento->motivo = $request->motivo;
      $alojamento->condicao = $request->condicao;
      $alojamento->data_inicial = $request->data_inicial;
      $alojamento->data_final = $request->data_final;
      $alojamento->status = 'pendente';

      // Processar upload de documento se fornecido
      if ($request->hasFile('documento_comprobatorio')) {
        $path = $request
          ->file('documento_comprobatorio')
          ->store('documentos_alojamento', 'public');
        $alojamento->documento_comprobatorio = $path;
      }

      $alojamento->save();

      // Logs
      \Log::info('Nova reserva de alojamento criada e perfil atualizado', [
        'reserva_id' => $alojamento->id,
        'user_id' => $userId,
        'nome' => $user->name,
      ]);

      // Enviar emails
      $administradorEmail = config(
        'alojamento.admin_email',
        'matiasnobrega7@gmail.com',
      );
      Mail::to($administradorEmail)->send(
        new NovaReservaAlojamento($alojamento),
      );

      $emailInstitucional = config(
        'alojamento.institutional_email',
        'nobregamatias7@gmail.com',
      );
      if ($emailInstitucional !== $administradorEmail) {
        Mail::to($emailInstitucional)->send(
          new NovaReservaAlojamento($alojamento),
        );
      }

      // Session
      session([
        'detalhes_reserva' => [
          'nome' => $alojamento->nome,
          'data_inicial' => $alojamento->data_inicial->format('d/m/Y'),
          'data_final' => $alojamento->data_final->format('d/m/Y'),
          'id' => $alojamento->id,
          'created_at' => $alojamento->created_at->format('d/m/Y H:i'),
        ],
      ]);

      DB::commit();

      return redirect()
        ->route('alojamento.confirmacao')
        ->with(
          'message',
          'Sua solicitação de pré-reserva foi enviada com sucesso e será analisada em breve.',
        );
    } catch (\Exception $e) {
      DB::rollBack();

      \Log::error('Erro ao criar reserva de alojamento: ' . $e->getMessage(), [
        'user_id' => $userId,
        'trace' => $e->getTraceAsString(),
      ]);

      throw ValidationException::withMessages([
        'message' =>
          'Ocorreu um erro ao processar sua solicitação. Tente novamente.',
      ]);
    }
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
      'mensagem' =>
        'Sua solicitação de pré-reserva foi enviada com sucesso e será analisada em breve.',
      'detalhes' => session('detalhes_reserva'),
    ]);
  }

  /**
   * Exibe o painel de administração de reservas (apenas para administradores)
   */
  public function index(Request $request)
  {
    $this->authorize('adminViewAny', Alojamento::class);

    // Buscar reservas de usuários COM informações de ocupação ATUAL E HISTÓRICO
    $reservasUsuarios = Alojamento::with([
      'usuario',
      'ocupacaoAtual.dormitorio',
      'ocupacoes',
    ])
      ->when($request->search, function ($query, $search) {
        return $query->where(function ($q) use ($search) {
          // Busca APENAS por CPF
          $cpfLimpo = preg_replace('/[^0-9]/', '', $search);
          $q->where('cpf', 'like', "%{$search}%")->orWhere(
            'cpf',
            'like',
            "%{$cpfLimpo}%",
          );
        });
      })
      ->when($request->status, function ($query, $status) {
        return $query->where('status', $status);
      })
      ->when($request->ocupacao, function ($query, $ocupacao) {
        switch ($ocupacao) {
          case 'com_checkin':
            return $query->whereHas('ocupacaoAtual');
          case 'sem_checkin':
            return $query
              ->where('status', 'aprovada')
              ->whereDoesntHave('ocupacaoAtual')
              ->whereDoesntHave('ocupacoes', function ($q) {
                $q->where('status', 'liberado');
              });
          case 'checkout_realizado':
            return $query
              ->where('status', 'aprovada')
              ->whereDoesntHave('ocupacaoAtual')
              ->whereHas('ocupacoes', function ($q) {
                $q->where('status', 'liberado');
              });
          case 'disponivel':
            return $query
              ->where('status', '!=', 'aprovada')
              ->orWhereDoesntHave('ocupacaoAtual');
        }
      });

    $reservasUsuarios = $reservasUsuarios->get();

    $reservasUsuarios = $reservasUsuarios->map(function ($reserva) {
      $ocupacaoAtual = $reserva->ocupacaoAtual;

      // Verificar se já teve checkout
      $teveCheckout = $reserva
        ->ocupacoes()
        ->where('status', 'liberado')
        ->exists();

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
        'usuario_sistema' => $reserva->usuario
          ? [
            'id' => $reserva->usuario->id,
            'name' => $reserva->usuario->name,
            'matricula' => $reserva->usuario->matricula,
            'email' => $reserva->usuario->email,
          ]
          : null,
        // Informações de ocupação
        'tem_ocupacao_ativa' => $ocupacaoAtual ? true : false,
        'teve_ocupacao_anterior' => $teveCheckout,
        'checkout_realizado' => $teveCheckout && !$ocupacaoAtual,
        'ocupacao_info' => $ocupacaoAtual
          ? [
            'dormitorio_numero' => $ocupacaoAtual->dormitorio->numero,
            'dormitorio_nome' => $ocupacaoAtual->dormitorio->nome,
            'numero_vaga' => $ocupacaoAtual->numero_vaga,
            'checkin_at' => $ocupacaoAtual->checkin_at->format('d/m/Y H:i'),
            'duracao_estadia' => $ocupacaoAtual->getDuracaoEstadia(),
          ]
          : null,
      ];
    });

    // Buscar reservas de visitantes COM informações de ocupação ATUAL E HISTÓRICO
    $reservasVisitantes = \App\Models\Visitante::with([
      'ocupacaoAtual.dormitorio',
      'ocupacoes',
    ])
      ->when($request->search, function ($query, $search) {
        return $query->where(function ($q) use ($search) {
          // Busca APENAS por CPF
          $cpfLimpo = preg_replace('/[^0-9]/', '', $search);
          $q->where('cpf', 'like', "%{$search}%")->orWhere(
            'cpf',
            'like',
            "%{$cpfLimpo}%",
          );
        });
      })
      ->when($request->status, function ($query, $status) {
        return $query->where('status', $status);
      })
      ->when($request->ocupacao, function ($query, $ocupacao) {
        switch ($ocupacao) {
          case 'com_checkin':
            return $query->whereHas('ocupacaoAtual');
          case 'sem_checkin':
            return $query
              ->where('status', 'aprovada')
              ->whereDoesntHave('ocupacaoAtual')
              ->whereDoesntHave('ocupacoes', function ($q) {
                $q->where('status', 'liberado');
              });
          case 'checkout_realizado':
            return $query
              ->where('status', 'aprovada')
              ->whereDoesntHave('ocupacaoAtual')
              ->whereHas('ocupacoes', function ($q) {
                $q->where('status', 'liberado');
              });
          case 'disponivel':
            return $query
              ->where('status', '!=', 'aprovada')
              ->orWhereDoesntHave('ocupacaoAtual');
        }
      });

    $reservasVisitantes = $reservasVisitantes->get();

    $reservasVisitantes = $reservasVisitantes->map(function ($visitante) {
      $ocupacaoAtual = $visitante->ocupacaoAtual;

      // Verificar se já teve checkout
      $teveCheckout = $visitante
        ->ocupacoes()
        ->where('status', 'liberado')
        ->exists();

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
        'documento_comprobatorio_url' =>
          $visitante->documento_comprobatorio_url,
        // Informações de ocupação
        'tem_ocupacao_ativa' => $ocupacaoAtual ? true : false,
        'teve_ocupacao_anterior' => $teveCheckout,
        'checkout_realizado' => $teveCheckout && !$ocupacaoAtual,
        'ocupacao_info' => $ocupacaoAtual
          ? [
            'dormitorio_numero' => $ocupacaoAtual->dormitorio->numero,
            'dormitorio_nome' => $ocupacaoAtual->dormitorio->nome,
            'numero_vaga' => $ocupacaoAtual->numero_vaga,
            'checkin_at' => $ocupacaoAtual->checkin_at->format('d/m/Y H:i'),
            'duracao_estadia' => $ocupacaoAtual->getDuracaoEstadia(),
          ]
          : null,
      ];
    });

    // Combinar as duas coleções
    $todasReservas = $reservasUsuarios->concat($reservasVisitantes);

    // Ordenar por data de criação (mais recentes primeiro)
    $todasReservas = $todasReservas->sortByDesc('created_at');

    // Paginação manual
    $perPage = 10;
    $currentPage = $request->get('page', 1);
    $total = $todasReservas->count();
    $items = $todasReservas
      ->slice(($currentPage - 1) * $perPage, $perPage)
      ->values();

    // Criar objeto de paginação manual
    $paginatedReservas = new \Illuminate\Pagination\LengthAwarePaginator(
      $items,
      $total,
      $perPage,
      $currentPage,
      [
        'path' => $request->url(),
        'pageName' => 'page',
      ],
    );

    $paginatedReservas->appends($request->all());

    // Estatísticas melhoradas com checkout
    $estatisticas = [
      'total_usuarios' => $reservasUsuarios->count(),
      'total_visitantes' => $reservasVisitantes->count(),
      'total_pendentes' => $todasReservas->where('status', 'pendente')->count(),
      'total_aprovadas' => $todasReservas->where('status', 'aprovada')->count(),
      'total_rejeitadas' => $todasReservas
        ->where('status', 'rejeitada')
        ->count(),
      'total_com_checkin' => $todasReservas
        ->where('tem_ocupacao_ativa', true)
        ->count(),
      'total_sem_checkin' => $todasReservas
        ->where('status', 'aprovada')
        ->where('tem_ocupacao_ativa', false)
        ->where('checkout_realizado', false)
        ->count(),
      'total_checkout_realizado' => $todasReservas
        ->where('checkout_realizado', true)
        ->count(),
    ];

    return Inertia::render('Admin/Alojamento/Index', [
      'reservas' => $paginatedReservas,
      'filters' => $request->only(['search', 'status', 'ocupacao']),
      'estatisticas' => $estatisticas,
    ]);
  }

  /**
   * Exibe os detalhes de uma reserva (para administradores)
   */
  public function show(Alojamento $alojamento)
  {
    $this->authorize('adminView', $alojamento);

    // Carregar ocupação atual se existir
    $alojamento->load('ocupacaoAtual.dormitorio');

    // Adicionar URL do documento para o frontend
    $alojamento->documento_url = $alojamento->documento_comprobatorio
      ? asset('storage/' . $alojamento->documento_comprobatorio)
      : null;

    // Adicionar tipo para diferenciação no frontend
    $alojamento->tipo_reserva = 'usuario';

    // Adicionar informações de ocupação
    $ocupacao = $alojamento->ocupacaoAtual;
    if (!$ocupacao) {
      $ocupacao = $alojamento
        ->ocupacoes()
        ->with('dormitorio')
        ->latest('created_at')
        ->first();
    }

    $alojamento->ocupacao_info = $ocupacao
      ? [
        'dormitorio_numero' => $ocupacao->dormitorio->numero,
        'dormitorio_nome' => $ocupacao->dormitorio->nome,
        'numero_vaga' => $ocupacao->numero_vaga,
        'checkin_at' => $ocupacao->checkin_at->format('d/m/Y H:i'),
        'duracao_estadia' => $ocupacao->getDuracaoEstadia(),
        'observacoes' => $ocupacao->observacoes,
      ]
      : null;

    // Buscar APENAS dormitórios disponíveis para check-in (EXCLUINDO reservados)
    $dormitoriosDisponiveis = [];
    if ($alojamento->podeCheckin()) {
      $dormitoriosDisponiveis = Dormitorio::disponiveis()
        ->orderBy('numero')
        ->get()
        ->map(function ($dormitorio) {
          return [
            'id' => $dormitorio->id,
            'numero' => $dormitorio->numero,
            'nome' => $dormitorio->nome,
            'capacidade_maxima' => $dormitorio->capacidade_maxima,
            'vagas_disponiveis' => $dormitorio->vagas_disponiveis,
            'vagas_livres' => $dormitorio->getVagasLivres(),
            'observacoes' => $dormitorio->observacoes,
          ];
        });
    }

    return Inertia::render('Admin/Alojamento/Show', [
      'reserva' => $alojamento,
      'dormitorios_disponiveis' => $dormitoriosDisponiveis,
      'pode_checkin' => $alojamento->podeCheckin(),
      'pode_checkout' => $alojamento->podeCheckout(),
    ]);
  }

  /**
   * Aprovar uma solicitação de reserva
   */
  public function aprovar(Alojamento $alojamento)
  {
    $this->authorize('adminUpdate', $alojamento);

    if ($alojamento->status !== 'pendente') {
      return redirect()
        ->back()
        ->with('error', 'Esta solicitação já foi processada.');
    }

    $alojamento->status = 'aprovada';
    $alojamento->save();

    // Enviar email de confirmação para o usuário
    Mail::to($alojamento->email)->send(
      new ReservaAlojamentoAprovada($alojamento),
    );

    return redirect()->back()->with('message', 'Reserva aprovada com sucesso.');
  }

  /**
   * Rejeitar uma solicitação de reserva
   */
  public function rejeitar(Request $request, Alojamento $alojamento)
  {
    $this->authorize('adminUpdate', $alojamento);

    if ($alojamento->status !== 'pendente') {
      return redirect()
        ->back()
        ->with('error', 'Esta solicitação já foi processada.');
    }

    $request->validate([
      'motivo_rejeicao' => 'required|string',
    ]);

    $alojamento->status = 'rejeitada';
    $alojamento->motivo_rejeicao = $request->motivo_rejeicao;
    $alojamento->save();

    // Enviar email de rejeição para o usuário
    Mail::to($alojamento->email)->send(
      new ReservaAlojamentoRejeitada($alojamento),
    );

    return redirect()
      ->back()
      ->with('message', 'Reserva rejeitada com sucesso.');
  }

  /**
   * Alterar o status de uma reserva
   */
  public function alterarStatus(Request $request, Alojamento $alojamento)
  {
    $this->authorize('adminUpdate', $alojamento);

    $request->validate([
      'status' => ['required', Rule::in(['pendente', 'aprovada', 'rejeitada'])],
      'dormitorio_id' => 'nullable|exists:dormitorios,id',
      'numero_vaga' => 'nullable|integer|min:1',
    ]);

    $novoStatus = $request->status;

    // Verificar se o status é diferente do atual
    if ($alojamento->status === $novoStatus) {
      return redirect()
        ->back()
        ->with('message', 'O status já está definido como ' . $novoStatus);
    }

    try {
      DB::beginTransaction();

      // Para rejeição, precisamos de um motivo
      if ($novoStatus === 'rejeitada' && !$request->has('motivo_rejeicao')) {
        return redirect()
          ->back()
          ->with(
            'error',
            'É necessário informar um motivo para rejeitar a reserva',
          );
      }

      // Atualizar campos conforme o status
      $dados = ['status' => $novoStatus];

      // Adicionar motivo de rejeição se for o caso
      if ($novoStatus === 'rejeitada' && $request->has('motivo_rejeicao')) {
        $dados['motivo_rejeicao'] = $request->motivo_rejeicao;
      }

      $alojamento->update($dados);

      // Se estiver aprovando e foi fornecido dormitório, fazer check-in automático
      if (
        $novoStatus === 'aprovada' &&
        $request->dormitorio_id &&
        $request->numero_vaga
      ) {
        $dormitorio = Dormitorio::findOrFail($request->dormitorio_id);

        // Verificar se o dormitório não é reservado para plantão
        if ($dormitorio->isReservadoPlantao()) {
          throw new \Exception(
            'Este dormitório está reservado para o plantão da ACADEPOL e não pode receber check-ins externos.',
          );
        }

        // Verificar se o dormitório está disponível
        if (!$dormitorio->disponivelParaCheckin()) {
          throw new \Exception(
            'Este dormitório não está disponível para check-ins.',
          );
        }

        // Validar número da vaga conforme capacidade do dormitório
        if ($request->numero_vaga > $dormitorio->capacidade_maxima) {
          throw new \Exception(
            "A vaga {$request->numero_vaga} não existe neste dormitório. Capacidade máxima: {$dormitorio->capacidade_maxima} vagas.",
          );
        }

        // Verificar se a vaga está disponível
        $vagaOcupada = Ocupacao::where('dormitorio_id', $dormitorio->id)
          ->where('numero_vaga', $request->numero_vaga)
          ->where('status', 'ocupado')
          ->exists();

        if (!$vagaOcupada) {
          // Criar ocupação
          $ocupacao = Ocupacao::create([
            'dormitorio_id' => $dormitorio->id,
            'reservavel_type' => Alojamento::class,
            'reservavel_id' => $alojamento->id,
            'numero_vaga' => $request->numero_vaga,
            'checkin_por' => Auth::id(),
            'observacoes' => 'Check-in automático na aprovação da reserva',
          ]);

          // Realizar check-in
          $ocupacao->realizarCheckin(Auth::id());

          \Log::info('Check-in automático realizado para alojamento', [
            'alojamento_id' => $alojamento->id,
            'dormitorio_id' => $dormitorio->id,
            'dormitorio_numero' => $dormitorio->numero,
            'capacidade' => $dormitorio->capacidade_maxima,
            'vaga' => $request->numero_vaga,
          ]);
        }
      }

      // Enviar notificação por email conforme o status
      if ($novoStatus === 'aprovada') {
        Mail::to($alojamento->email)->send(
          new ReservaAlojamentoAprovada($alojamento),
        );
      } elseif ($novoStatus === 'rejeitada') {
        Mail::to($alojamento->email)->send(
          new ReservaAlojamentoRejeitada($alojamento),
        );
      }

      DB::commit();

      return redirect()
        ->back()
        ->with(
          'message',
          'Status da reserva alterado com sucesso para ' . $novoStatus,
        );
    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()
        ->back()
        ->with('error', 'Erro ao alterar status: ' . $e->getMessage());
    }
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
        return response()->json(
          [
            'error' =>
              'Apenas reservas aprovadas podem gerar ficha de hospedagem',
          ],
          400,
        );
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
      $dataInicial = $alojamento->data_inicial
        ? $alojamento->data_inicial->format('d/m/Y')
        : '';
      $dataFinal = $alojamento->data_final
        ? $alojamento->data_final->format('d/m/Y')
        : '';

      // Preparar os dados para o template
      $dados = [
        'nome' => $alojamento->nome,
        'rg' => $alojamento->rg ?? '',
        'orgao_expedidor' => $alojamento->orgao_expedidor ?? '',
        'cpf' => $alojamento->cpf,
        'data_nascimento' => $alojamento->data_nascimento
          ? $alojamento->data_nascimento->format('d/m/Y')
          : '',
        'matricula' => $alojamento->matricula,
        'sexo' =>
          $alojamento->sexo === 'masculino'
            ? 'M'
            : ($alojamento->sexo === 'feminino'
              ? 'F'
              : ''),
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
        ->header(
          'Content-Disposition',
          'attachment; filename="ficha_hospedagem_' . $alojamento->id . '.pdf"',
        );
    } catch (\Exception $e) {
      return response()->json(
        [
          'success' => false,
          'error' => 'Erro ao gerar ficha de hospedagem: ' . $e->getMessage(),
          'trace' => $e->getTraceAsString(),
        ],
        500,
      );
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
        return redirect()
          ->back()
          ->with(
            'error',
            'Apenas reservas aprovadas podem gerar ficha de hospedagem',
          );
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
      $dataInicial = $alojamento->data_inicial
        ? $alojamento->data_inicial->format('d/m/Y')
        : '';
      $dataFinal = $alojamento->data_final
        ? $alojamento->data_final->format('d/m/Y')
        : '';

      // Preparar os dados para o template
      $dados = [
        'nome' => $alojamento->nome,
        'rg' => $alojamento->rg ?? '',
        'orgao_expedidor' => $alojamento->orgao_expedidor ?? '',
        'cpf' => $alojamento->cpf,
        'data_nascimento' => $alojamento->data_nascimento
          ? $alojamento->data_nascimento->format('d/m/Y')
          : '',
        'matricula' => $alojamento->matricula,
        'sexo' =>
          $alojamento->sexo === 'masculino'
            ? 'M'
            : ($alojamento->sexo === 'feminino'
              ? 'F'
              : ''),
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
        'Attachment' => false,
      ]);
    } catch (\Exception $e) {
      // Em caso de erro, exibir uma mensagem de erro
      return response()->view(
        'errors.custom',
        [
          'message' => 'Erro ao gerar ficha de hospedagem: ' . $e->getMessage(),
          'trace' => $e->getTraceAsString(),
        ],
        500,
      );
    }
  }

  /**
   * Fazer check-in manual (caso não tenha sido feito na aprovação)
   */
  public function checkin(Request $request, Alojamento $alojamento)
  {
    $this->authorize('adminUpdate', $alojamento);

    $request->validate([
      'dormitorio_id' => 'required|exists:dormitorios,id',
      'numero_vaga' => 'required|integer|min:1',
      'observacoes' => 'nullable|string|max:500',
    ]);

    try {
      DB::beginTransaction();

      // Verificar se pode fazer check-in
      if (!$alojamento->podeCheckin()) {
        throw new \Exception(
          'Esta reserva não pode fazer check-in no momento.',
        );
      }

      // Buscar dormitório
      $dormitorio = Dormitorio::findOrFail($request->dormitorio_id);

      // Verificar se o dormitório não é reservado para plantão
      if ($dormitorio->isReservadoPlantao()) {
        throw new \Exception(
          'Este dormitório está reservado para o plantão da ACADEPOL e não pode receber check-ins externos.',
        );
      }

      // Verificar se o dormitório está disponível
      if (!$dormitorio->disponivelParaCheckin()) {
        throw new \Exception(
          'Este dormitório não está disponível para check-ins.',
        );
      }

      // Validar número da vaga conforme capacidade do dormitório
      if ($request->numero_vaga > $dormitorio->capacidade_maxima) {
        throw new \Exception(
          "A vaga {$request->numero_vaga} não existe neste dormitório. Capacidade máxima: {$dormitorio->capacidade_maxima} vagas.",
        );
      }

      // Verificar se a vaga está disponível
      $vagaOcupada = Ocupacao::where('dormitorio_id', $dormitorio->id)
        ->where('numero_vaga', $request->numero_vaga)
        ->where('status', 'ocupado')
        ->exists();

      if ($vagaOcupada) {
        throw new \Exception('Esta vaga já está ocupada.');
      }

      // Criar ocupação
      $ocupacao = Ocupacao::create([
        'dormitorio_id' => $dormitorio->id,
        'reservavel_type' => Alojamento::class,
        'reservavel_id' => $alojamento->id,
        'numero_vaga' => $request->numero_vaga,
        'checkin_por' => Auth::id(),
        'observacoes' => $request->observacoes,
      ]);

      // Realizar check-in
      $ocupacao->realizarCheckin(Auth::id());

      DB::commit();

      \Log::info('Check-in manual realizado para alojamento', [
        'alojamento_id' => $alojamento->id,
        'dormitorio_id' => $dormitorio->id,
        'dormitorio_numero' => $dormitorio->numero,
        'capacidade' => $dormitorio->capacidade_maxima,
        'vaga' => $request->numero_vaga,
      ]);

      return redirect()
        ->back()
        ->with('message', 'Check-in realizado com sucesso!');
    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()->back()->with('error', $e->getMessage());
    }
  }

  /**
   * Fazer check-out
   */
  public function checkout(Request $request, Alojamento $alojamento)
  {
    $this->authorize('adminUpdate', $alojamento);

    $request->validate([
      'observacoes' => 'nullable|string|max:500',
    ]);

    try {
      DB::beginTransaction();

      $ocupacao = $alojamento->ocupacaoAtual;

      if (!$ocupacao || !$ocupacao->isAtiva()) {
        throw new \Exception('Esta reserva não possui ocupação ativa.');
      }

      // Adicionar observações se fornecidas
      if ($request->observacoes) {
        $ocupacao->observacoes =
          ($ocupacao->observacoes ? $ocupacao->observacoes . "\n\n" : '') .
          'Check-out: ' .
          $request->observacoes;
      }

      // Realizar check-out
      $ocupacao->realizarCheckout(Auth::id());

      DB::commit();

      return redirect()
        ->back()
        ->with('message', 'Check-out realizado com sucesso!');
    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()->back()->with('error', $e->getMessage());
    }
  }
}
