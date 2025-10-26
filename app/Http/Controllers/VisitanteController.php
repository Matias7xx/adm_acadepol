<?php

namespace App\Http\Controllers;

use App\Models\Visitante;
use App\Models\Dormitorio;
use App\Models\Ocupacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Validation\ValidationException;
use App\Mail\ReservaVisitanteAprovada;
use App\Mail\ReservaVisitanteRejeitada;
use App\Services\DataSanitizerService;
use App\Services\AuditLoggerService;

class VisitanteController extends Controller
{
  protected $sanitizer;
  protected $auditLogger;

  public function __construct(
    DataSanitizerService $sanitizer = null,
    AuditLoggerService $auditLogger = null,
  ) {
    $this->sanitizer = $sanitizer;
    $this->auditLogger = $auditLogger;
  }

  /**
   * Buscar visitante por CPF para pré-preenchimento
   */
  public function buscarPorCpf(Request $request)
  {
    $request->validate([
      'cpf' => 'required|string',
    ]);

    $cpf = preg_replace('/[^0-9]/', '', $request->cpf);

    if (strlen($cpf) !== 11) {
      return response()->json(
        [
          'success' => false,
          'message' => 'CPF inválido',
        ],
        400,
      );
    }

    // Buscar visitante mais recente
    $visitante = Visitante::findByCpf($cpf);

    if (!$visitante) {
      return response()->json(
        [
          'success' => false,
          'message' => 'CPF não encontrado',
        ],
        404,
      );
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
      ],
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
      'outro' => 'Outro',
    ];

    $condicoes = [
      'curso' => 'Participação em Curso',
      'trabalho' => 'Trabalho/Reunião',
      'visita_tecnica' => 'Visita Técnica',
      'evento' => 'Evento/Capacitação',
      'outro' => 'Outro',
    ];

    return Inertia::render('Components/FormularioVisitante', [
      'tiposOrgao' => $tiposOrgao,
      'condicoes' => $condicoes,
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
      'endereco.cep' => 'required|string|max:10',
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
      return redirect()->back()->withErrors($validator)->withInput();
    }

    // Verificar se já existe reserva no período
    $cpf = preg_replace('/[^0-9]/', '', $request->cpf);

    //VERIFICAR RESERVAS APROVADAS
    $reservaAprovadaExistente = Visitante::where('cpf', $cpf)
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
        'message' =>
          'Já existe uma reserva aprovada para este CPF no período solicitado.',
      ]);
    }

    //VERIFICAR RESERVAS PENDENTES
    $reservaPendenteExistente = Visitante::where('cpf', $cpf)
      ->where('status', 'pendente')
      ->exists();

    if ($reservaPendenteExistente) {
      $reservaPendente = Visitante::where('cpf', $cpf)
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

      // VERIFICAR se CPF já existe (primeiro uso ou retornando)
      $visitanteExistente = Visitante::findByCpf($cpf);

      if ($visitanteExistente) {
        // ATUALIZAR dados pessoais na reserva mais recente
        $visitanteExistente->update([
          'nome' => $request->nome,
          'rg' => $request->rg,
          'orgao_expedidor_rg' => $request->orgao_expedidor_rg,
          'data_nascimento' => $request->data_nascimento,
          'sexo' => $request->sexo,
          'telefone' => $request->telefone,
          'email' => $request->email,
          'endereco' => $request->endereco,
          'orgao_trabalho' => $request->orgao_trabalho,
          'cargo' => $request->cargo,
          'matricula_funcional' => $request->matricula_funcional,
          'tipo_orgao' => $request->tipo_orgao,
        ]);

        \Log::info('Dados pessoais atualizados para CPF: ' . $cpf, [
          'visitante_id' => $visitanteExistente->id,
          'nome' => $request->nome,
        ]);
      }

      // SEMPRE CRIAR nova reserva (independente se é primeira vez ou não)
      $novaReserva = new Visitante();

      // Dados pessoais (atualizados ou novos)
      $novaReserva->nome = $request->nome;
      $novaReserva->cpf = $cpf;
      $novaReserva->rg = $request->rg;
      $novaReserva->orgao_expedidor_rg = $request->orgao_expedidor_rg;
      $novaReserva->data_nascimento = $request->data_nascimento;
      $novaReserva->sexo = $request->sexo;
      $novaReserva->telefone = $request->telefone;
      $novaReserva->email = $request->email;
      $novaReserva->endereco = $request->endereco;
      $novaReserva->orgao_trabalho = $request->orgao_trabalho;
      $novaReserva->cargo = $request->cargo;
      $novaReserva->matricula_funcional = $request->matricula_funcional;
      $novaReserva->tipo_orgao = $request->tipo_orgao;

      // Dados específicos da reserva (sempre novos)
      $novaReserva->data_inicial = $request->data_inicial;
      $novaReserva->data_final = $request->data_final;
      $novaReserva->motivo = $request->motivo;
      $novaReserva->condicao = $request->condicao;
      $novaReserva->ip = $request->ip();
      $novaReserva->user_agent = $request->userAgent();
      $novaReserva->status = 'pendente';

      // Processar uploads da nova reserva
      if ($request->hasFile('documento_identidade')) {
        $path = $request
          ->file('documento_identidade')
          ->store('documentos_visitantes/identidade', 'public');
        $novaReserva->documento_identidade = $path;
      }

      if ($request->hasFile('documento_funcional')) {
        $path = $request
          ->file('documento_funcional')
          ->store('documentos_visitantes/funcional', 'public');
        $novaReserva->documento_funcional = $path;
      }

      if ($request->hasFile('documento_comprobatorio')) {
        $path = $request
          ->file('documento_comprobatorio')
          ->store('documentos_visitantes/comprobatorio', 'public');
        $novaReserva->documento_comprobatorio = $path;
      }

      $novaReserva->save();

      // ✅ Log de sucesso
      \Log::info(
        $visitanteExistente
          ? 'Nova reserva criada para visitante existente'
          : 'Primeira reserva criada',
        [
          'nova_reserva_id' => $novaReserva->id,
          'cpf' => $cpf,
          'nome' => $request->nome,
          'eh_primeira_reserva' => !$visitanteExistente,
        ],
      );

      // Enviar email para administrador
      $administradorEmail = config(
        'alojamento.admin_email',
        'matiasnobrega7@gmail.com',
      );

      // Registrar na auditoria (se o serviço estiver disponível)
      if ($this->auditLogger) {
        $this->auditLogger->registrarAcao(
          $visitanteExistente
            ? 'Nova reserva de visitante recorrente criada'
            : 'Primeira reserva de visitante criada',
          'visitante',
          [
            'nova_reserva_id' => $novaReserva->id,
            'cpf' => $novaReserva->formatted_cpf,
            'nome' => $novaReserva->nome,
            'orgao' => $novaReserva->orgao_trabalho,
            'eh_primeira_reserva' => !$visitanteExistente,
          ],
        );
      }

      // Armazenar detalhes na sessão
      session([
        'detalhes_reserva_visitante' => [
          'nome' => $novaReserva->nome,
          'cpf' => $novaReserva->formatted_cpf,
          'data_inicial' => $novaReserva->data_inicial->format('d/m/Y'),
          'data_final' => $novaReserva->data_final->format('d/m/Y'),
          'id' => $novaReserva->id,
          'created_at' => $novaReserva->created_at->format('d/m/Y H:i'),
          'eh_primeira_reserva' => !$visitanteExistente,
        ],
      ]);

      DB::commit();

      return redirect()
        ->route('visitante.confirmacao')
        ->with(
          'message',
          'Sua solicitação de reserva foi enviada com sucesso!',
        );
    } catch (\Exception $e) {
      DB::rollBack();

      \Log::error('Erro ao criar reserva de visitante: ' . $e->getMessage(), [
        'cpf' => $cpf,
        'nome' => $request->nome,
        'trace' => $e->getTraceAsString(),
      ]);

      throw ValidationException::withMessages([
        'message' =>
          'Ocorreu um erro ao processar sua solicitação. Tente novamente.',
      ]);
    }
  }

  /**
   * Página de confirmação
   */
  public function confirmacao()
  {
    return Inertia::render('Components/Confirmacao', [
      'user' => null,
      'mensagem' =>
        'Sua solicitação de reserva foi enviada com sucesso e será analisada em breve.',
      'detalhes' => session('detalhes_reserva_visitante'),
      'tipo' => 'visitante',
    ]);
  }

  /**
   * Lista de visitantes para administradores
   */
  public function index(Request $request)
  {
    $this->authorize('adminViewAny', Visitante::class);

    $visitantes = Visitante::with(['ocupacaoAtual.dormitorio', 'ocupacoes']) // ATUALIZADO: incluir todas as ocupações
      ->when($request->search, function ($query, $search) {
        return $query->where(function ($q) use ($search) {
          // Busca por nome, CPF, email ou órgão
          $q->where('nome', 'like', "%{$search}%")
            ->orWhere('cpf', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('orgao_trabalho', 'like', "%{$search}%")
            ->orWhere('matricula_funcional', 'like', "%{$search}%")
            // Busca por CPF sem formatação
            ->orWhere(
              'cpf',
              'like',
              '%' . preg_replace('/[^0-9]/', '', $search) . '%',
            );
        });
      })
      ->when($request->status, function ($query, $status) {
        return $query->where('status', $status);
      })
      ->when($request->tipo_orgao, function ($query, $tipo) {
        return $query->where('tipo_orgao', $tipo);
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
      })
      ->orderBy('created_at', 'desc')
      ->paginate(10)
      ->through(function ($visitante) {
        $ocupacaoAtual = $visitante->ocupacaoAtual;

        // Verificar se já teve checkout
        $teveCheckout = $visitante
          ->ocupacoes()
          ->where('status', 'liberado')
          ->exists();

        // Adicionar informações de ocupação
        $visitante->tem_ocupacao_ativa = $ocupacaoAtual ? true : false;
        $visitante->teve_ocupacao_anterior = $teveCheckout; // NOVO
        $visitante->checkout_realizado = $teveCheckout && !$ocupacaoAtual; // NOVO
        $visitante->ocupacao_info = $ocupacaoAtual
          ? [
            'dormitorio_numero' => $ocupacaoAtual->dormitorio->numero,
            'dormitorio_nome' => $ocupacaoAtual->dormitorio->nome,
            'numero_vaga' => $ocupacaoAtual->numero_vaga,
            'checkin_at' => $ocupacaoAtual->checkin_at->format('d/m/Y H:i'),
            'duracao_estadia' => $ocupacaoAtual->getDuracaoEstadia(),
          ]
          : null;

        return $visitante;
      })
      ->appends($request->all());

    return Inertia::render('Admin/Visitantes/Index', [
      'visitantes' => $visitantes,
      'filters' => $request->only([
        'search',
        'status',
        'tipo_orgao',
        'ocupacao',
      ]),
    ]);
  }

  /**
   * Exibir detalhes de um visitante para administradores
   */
  public function show(Visitante $visitante)
  {
    $this->authorize('adminView', $visitante);

    // Carregar ocupação atual se existir
    $visitante->load('ocupacaoAtual.dormitorio');

    // Adicionar URLs dos documentos para o frontend
    $visitante->documento_url = $visitante->documento_identidade
      ? asset('storage/' . $visitante->documento_identidade)
      : null;
    $visitante->documento_identidade_url = $visitante->documento_identidade_url;
    $visitante->documento_funcional_url = $visitante->documento_funcional_url;
    $visitante->documento_comprobatorio_url =
      $visitante->documento_comprobatorio_url;

    // Adicionar tipo para diferenciação no frontend
    $visitante->tipo_reserva = 'visitante';

    // Padronizar campos para compatibilidade com o template
    $visitante->orgao = $visitante->orgao_trabalho;
    $visitante->matricula = $visitante->matricula_funcional;

    // Adicionar informações de ocupação
    $ocupacao = $visitante->ocupacaoAtual;
    if (!$ocupacao) {
      $ocupacao = $visitante
        ->ocupacoes()
        ->with('dormitorio')
        ->latest('created_at')
        ->first();
    }

    $visitante->ocupacao_info = $ocupacao
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
    if ($visitante->podeCheckin()) {
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
      'reserva' => $visitante,
      'dormitorios_disponiveis' => $dormitoriosDisponiveis,
      'pode_checkin' => $visitante->podeCheckin(),
      'pode_checkout' => $visitante->podeCheckout(),
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
      'dormitorio_id' => 'nullable|exists:dormitorios,id',
      'numero_vaga' => 'nullable|integer|min:1',
      'motivo_rejeicao' => 'nullable|string|max:1000',
    ]);

    $novoStatus = $request->status;

    // Verificar se o status é diferente do atual
    if ($visitante->status === $novoStatus) {
      return redirect()
        ->back()
        ->with('message', 'O status já está definido como ' . $novoStatus);
    }

    try {
      DB::beginTransaction();

      // Para rejeição, validar motivo
      if ($novoStatus === 'rejeitada') {
        if (
          !$request->has('motivo_rejeicao') ||
          empty(trim($request->motivo_rejeicao))
        ) {
          return response()->json(
            [
              'message' =>
                'É necessário informar um motivo para rejeitar a reserva',
              'errors' => [
                'motivo_rejeicao' => ['O motivo da rejeição é obrigatório'],
              ],
            ],
            422,
          );
        }
      }

      // Preparar dados para atualização
      $dados = ['status' => $novoStatus];

      // Adicionar motivo de rejeição se for o caso
      if ($novoStatus === 'rejeitada' && $request->has('motivo_rejeicao')) {
        $dados['motivo_rejeicao'] = trim($request->motivo_rejeicao);
      }

      // Atualizar visitante
      $visitante->update($dados);

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
            'reservavel_type' => Visitante::class,
            'reservavel_id' => $visitante->id,
            'numero_vaga' => $request->numero_vaga,
            'checkin_por' => Auth::id(),
            'observacoes' =>
              $request->observacoes ??
              'Check-in automático na aprovação da reserva',
          ]);

          // Realizar check-in
          $ocupacao->realizarCheckin(Auth::id());

          \Log::info('Check-in automático realizado para visitante', [
            'visitante_id' => $visitante->id,
            'dormitorio_id' => $dormitorio->id,
            'dormitorio_numero' => $dormitorio->numero,
            'capacidade' => $dormitorio->capacidade_maxima,
            'vaga' => $request->numero_vaga,
          ]);
        }
      }

      // Enviar notificação por email conforme o status
      try {
        if ($novoStatus === 'aprovada') {
          Mail::to($visitante->email)->send(
            new ReservaVisitanteAprovada($visitante),
          );
        } elseif ($novoStatus === 'rejeitada') {
          Mail::to($visitante->email)->send(
            new ReservaVisitanteRejeitada($visitante),
          );
        }
      } catch (\Exception $mailException) {
        // Log do erro de email, mas não falha a operação
        \Log::warning('Erro ao enviar email de notificação', [
          'visitante_id' => $visitante->id,
          'status' => $novoStatus,
          'error' => $mailException->getMessage(),
        ]);
      }

      // Registrar auditoria se disponível
      if ($this->auditLogger) {
        $this->auditLogger->registrarAcao(
          "Status de reserva de visitante alterado para '{$novoStatus}'",
          'visitante',
          [
            'visitante_id' => $visitante->id,
            'status_anterior' => $visitante->getOriginal('status'),
            'novo_status' => $novoStatus,
            'dormitorio_id' => $request->dormitorio_id ?? null,
            'numero_vaga' => $request->numero_vaga ?? null,
          ],
        );
      }

      DB::commit();

      // Resposta para requests AJAX vs navegador
      if ($request->expectsJson()) {
        return response()->json([
          'message' => "Status da reserva alterado com sucesso para {$novoStatus}",
          'status' => $novoStatus,
          'reserva' => $visitante->fresh(), // Retornar dados atualizados
        ]);
      }

      return redirect()
        ->back()
        ->with(
          'message',
          "Status da reserva alterado com sucesso para {$novoStatus}",
        );
    } catch (\Exception $e) {
      DB::rollBack();

      \Log::error('Erro ao alterar status de visitante', [
        'visitante_id' => $visitante->id,
        'novo_status' => $novoStatus,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      if ($request->expectsJson()) {
        return response()->json(
          [
            'message' => 'Erro ao alterar status: ' . $e->getMessage(),
            'errors' => ['status' => ['Erro interno do servidor']],
          ],
          500,
        );
      }

      return redirect()
        ->back()
        ->with('error', 'Erro ao alterar status: ' . $e->getMessage());
    }
  }

  /**
   * Fazer check-in manual (caso não tenha sido feito na aprovação)
   */
  public function checkin(Request $request, Visitante $visitante)
  {
    $this->authorize('adminUpdate', $visitante);

    $request->validate([
      'dormitorio_id' => 'required|exists:dormitorios,id',
      'numero_vaga' => 'required|integer|min:1',
      'observacoes' => 'nullable|string|max:500',
    ]);

    try {
      DB::beginTransaction();

      // Verificar se pode fazer check-in
      if (!$visitante->podeCheckin()) {
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
        'reservavel_type' => Visitante::class,
        'reservavel_id' => $visitante->id,
        'numero_vaga' => $request->numero_vaga,
        'checkin_por' => Auth::id(),
        'observacoes' => $request->observacoes,
      ]);

      // Realizar check-in
      $ocupacao->realizarCheckin(Auth::id());

      DB::commit();

      \Log::info('Check-in manual realizado para visitante', [
        'visitante_id' => $visitante->id,
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
  public function checkout(Request $request, Visitante $visitante)
  {
    $this->authorize('adminUpdate', $visitante);

    $request->validate([
      'observacoes' => 'nullable|string|max:500',
    ]);

    try {
      DB::beginTransaction();

      $ocupacao = $visitante->ocupacaoAtual;

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

  /**
   * Aprovar reserva de visitante
   */
  public function aprovar(Visitante $visitante)
  {
    $this->authorize('adminUpdate', $visitante);

    if ($visitante->status !== 'pendente') {
      return redirect()
        ->back()
        ->with('error', 'Esta solicitação já foi processada.');
    }

    $visitante->update(['status' => 'aprovada']);

    Mail::to($visitante->email)->send(new ReservaVisitanteAprovada($visitante));

    if ($this->auditLogger) {
      $this->auditLogger->registrarAcao(
        'Reserva de visitante aprovada',
        'visitante',
        ['visitante_id' => $visitante->id],
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
      return redirect()
        ->back()
        ->with('error', 'Esta solicitação já foi processada.');
    }

    $request->validate([
      'motivo_rejeicao' => 'required|string',
    ]);

    $visitante->update([
      'status' => 'rejeitada',
      'motivo_rejeicao' => $request->motivo_rejeicao,
    ]);

    Mail::to($visitante->email)->send(
      new ReservaVisitanteRejeitada($visitante),
    );

    if ($this->auditLogger) {
      $this->auditLogger->registrarAcao(
        'Reserva de visitante rejeitada',
        'visitante',
        [
          'visitante_id' => $visitante->id,
          'motivo' => $request->motivo_rejeicao,
        ],
      );
    }

    return redirect()
      ->back()
      ->with('message', 'Reserva rejeitada com sucesso.');
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
        return response()->json(
          [
            'error' =>
              'Apenas reservas aprovadas podem gerar ficha de hospedagem',
          ],
          400,
        );
      }

      // Formatar dados do endereço
      $endereco = $visitante->endereco ?? [];
      $enderecoFormatado = '';
      $bairro = '';
      $cidade = '';
      $cep = '';
      $uf = '';

      if (is_array($endereco) && !empty($endereco)) {
        $enderecoFormatado = $endereco['rua'] ?? ''; // SEM o número
        $bairro = $endereco['bairro'] ?? '';
        $cidade = $endereco['cidade'] ?? '';
        $cep = $endereco['cep'] ?? '';
        $uf = $endereco['uf'] ?? '';
      }

      // Formatar datas
      $dataInicial = $visitante->data_inicial
        ? $visitante->data_inicial->format('d/m/Y')
        : '';
      $dataFinal = $visitante->data_final
        ? $visitante->data_final->format('d/m/Y')
        : '';

      $dados = [
        'nome' => $visitante->nome,
        'rg' => $visitante->rg ?? '',
        'orgao_expedidor' => $visitante->orgao_expedidor_rg ?? '',
        'cpf' => $visitante->cpf,
        'data_nascimento' => $visitante->data_nascimento
          ? $visitante->data_nascimento->format('d/m/Y')
          : '',
        'matricula' => $visitante->matricula_funcional,
        'sexo' =>
          $visitante->sexo === 'masculino'
            ? 'M'
            : ($visitante->sexo === 'feminino'
              ? 'F'
              : ''),
        'cargo' => $visitante->cargo,
        'telefone' => $visitante->telefone,
        'email' => $visitante->email,
        'endereco' => $enderecoFormatado,
        'numero' => $endereco['numero'] ?? '',
        'bairro' => $bairro,
        'cidade' => $cidade,
        'cep' => $cep,
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
        ->header(
          'Content-Disposition',
          'attachment; filename="ficha_hospedagem_visitante_' .
            $visitante->id .
            '.pdf"',
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
  public function visualizarFichaHospedagem(Visitante $visitante)
  {
    try {
      $this->authorize('adminView', $visitante);

      // Verificar se a reserva está aprovada
      if ($visitante->status !== 'aprovada') {
        return redirect()
          ->back()
          ->with(
            'error',
            'Apenas reservas aprovadas podem gerar ficha de hospedagem',
          );
      }

      // Formatar dados do endereço
      $endereco = $visitante->endereco ?? [];
      $enderecoFormatado = '';
      $bairro = '';
      $cidade = '';
      $cep = '';
      $uf = '';

      if (is_array($endereco) && !empty($endereco)) {
        $enderecoFormatado = $endereco['rua'] ?? ''; // SEM o número
        $bairro = $endereco['bairro'] ?? '';
        $cidade = $endereco['cidade'] ?? '';
        $cep = $endereco['cep'] ?? '';
        $uf = $endereco['uf'] ?? '';
      }

      // Formatar datas
      $dataInicial = $visitante->data_inicial
        ? $visitante->data_inicial->format('d/m/Y')
        : '';
      $dataFinal = $visitante->data_final
        ? $visitante->data_final->format('d/m/Y')
        : '';

      // Preparar os dados para o template
      $dados = [
        'nome' => $visitante->nome,
        'rg' => $visitante->rg ?? '',
        'orgao_expedidor' => $visitante->orgao_expedidor_rg ?? '',
        'cpf' => $visitante->cpf,
        'data_nascimento' => $visitante->data_nascimento
          ? $visitante->data_nascimento->format('d/m/Y')
          : '',
        'matricula' => $visitante->matricula_funcional,
        'sexo' =>
          $visitante->sexo === 'masculino'
            ? 'M'
            : ($visitante->sexo === 'feminino'
              ? 'F'
              : ''),
        'cargo' => $visitante->cargo,
        'telefone' => $visitante->telefone,
        'email' => $visitante->email,
        'endereco' => $enderecoFormatado,
        'numero' => $endereco['numero'] ?? '',
        'bairro' => $bairro,
        'cidade' => $cidade,
        'cep' => $cep,
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

      return $dompdf->stream(
        'ficha_hospedagem_visitante_' . $visitante->id . '.pdf',
        [
          'Attachment' => false,
        ],
      );
    } catch (\Exception $e) {
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
}
