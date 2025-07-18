<?php

namespace App\Http\Controllers;

use App\Models\Dormitorio;
use App\Models\Ocupacao;
use App\Models\Alojamento;
use App\Models\Visitante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OcupacaoController extends Controller
{
    /**
     * Exibe o painel de controle de ocupação
     */
    public function index(Request $request)
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $dormitorios = Dormitorio::with(['ocupacoesAtivas.reservavel'])
            ->orderBy('numero')
            ->get()
            ->map(function ($dormitorio) {
                $vagas = [];
                
                // Criar array de vagas com informações (capacidade dinâmica)
                for ($i = 1; $i <= $dormitorio->capacidade_maxima; $i++) {
                    $ocupacao = $dormitorio->ocupacoesAtivas()
                        ->where('numero_vaga', $i)
                        ->with('reservavel')
                        ->first();
                    
                    $vagas[] = [
                        'numero' => $i,
                        'ocupada' => $ocupacao ? true : false,
                        'ocupacao' => $ocupacao ? [
                        'id' => $ocupacao->id,
                        'hospede_nome' => $ocupacao->reservavel->nome,
                        'hospede_tipo' => $ocupacao->tipo_reserva,
                        'hospede_documento' => $ocupacao->reservavel->cpf ?? 'Não informado',
                        'hospede_telefone' => $ocupacao->reservavel->telefone ?? 'Não informado',
                        'checkin_at' => $ocupacao->checkin_at->format('d/m/Y H:i'),
                        'checkout_previsto' => $ocupacao->reservavel->data_final ? 
                            \Carbon\Carbon::parse($ocupacao->reservavel->data_final)->format('d/m/Y') : 
                            'Não informado',
                        'duracao' => $ocupacao->getDuracaoEstadia(),
                        'reserva_id' => $ocupacao->reservavel_id,
                        'reserva_type' => $ocupacao->reservavel_type,
                        'dormitorio_numero' => $dormitorio->numero,
                        'observacoes' => $ocupacao->observacoes,
                    ] : null
                    ];
                }
                
                return [
                    'id' => $dormitorio->id,
                    'numero' => $dormitorio->numero,
                    'nome' => $dormitorio->nome,
                    'capacidade_maxima' => $dormitorio->capacidade_maxima,
                    'vagas_ocupadas' => $dormitorio->vagas_ocupadas,
                    'vagas_disponiveis' => $dormitorio->vagas_disponiveis,
                    'percentual_ocupacao' => $dormitorio->percentual_ocupacao,
                    'classe_status' => $dormitorio->classe_status,
                    'status' => $dormitorio->status,
                    'status_texto' => $dormitorio->status_texto,
                    'reservado_plantao' => $dormitorio->isReservadoPlantao(),
                    'disponivel_checkin' => $dormitorio->disponivelParaCheckin(),
                    'observacoes' => $dormitorio->observacoes,
                    'vagas' => $vagas
                ];
            });

        // Estatísticas gerais (incluindo dormitórios reservados)
        $totalDormitorios = $dormitorios->count();
        $dormitoriosAtivos = $dormitorios->where('status', 'ativo')->count();
        $dormitoriosReservados = $dormitorios->where('reservado_plantao', true)->count();
        $totalVagas = $dormitorios->sum('capacidade_maxima');
        $vagasOcupadas = $dormitorios->sum('vagas_ocupadas');
        $vagasDisponiveis = $totalVagas - $vagasOcupadas;
        $percentualOcupacaoGeral = $totalVagas > 0 ? round(($vagasOcupadas / $totalVagas) * 100, 1) : 0;

        $estatisticas = [
            'total_dormitorios' => $totalDormitorios,
            'dormitorios_ativos' => $dormitoriosAtivos,
            'dormitorios_reservados' => $dormitoriosReservados,
            'total_vagas' => $totalVagas,
            'vagas_ocupadas' => $vagasOcupadas,
            'vagas_disponiveis' => $vagasDisponiveis,
            'percentual_ocupacao' => $percentualOcupacaoGeral,
            'dormitorios_lotados' => $dormitorios->where('vagas_disponiveis', 0)->count(),
            'dormitorios_disponiveis' => $dormitorios->where('disponivel_checkin', true)->count(),
        ];

        // Se for requisição AJAX, retorna JSON
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'props' => [
                    'dormitorios' => $dormitorios,
                    'estatisticas' => $estatisticas
                ]
            ]);
        }

        // Caso contrário, retorna a view normal
        return Inertia::render('Admin/Alojamento/Index', [
            'dormitorios' => $dormitorios,
            'estatisticas' => $estatisticas
        ]);
    }

    /**
     * Exibe o modal para selecionar dormitório e fazer check-in
     */
    public function modalCheckin(Request $request)
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $tipo = $request->tipo; // 'usuario' ou 'visitante'
        $id = $request->id;

        // Buscar a reserva
        if ($tipo === 'usuario') {
            $reserva = Alojamento::findOrFail($id);
        } else {
            $reserva = Visitante::findOrFail($id);
        }

        // Verificar se pode fazer check-in
        if (!$reserva->podeCheckin()) {
            return response()->json([
                'error' => 'Esta reserva não pode fazer check-in no momento.'
            ], 400);
        }

        // Buscar APENAS dormitórios disponíveis para check-in (EXCLUINDO reservados)
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
                    'proxima_vaga' => $dormitorio->getProximaVagaDisponivel(),
                    'observacoes' => $dormitorio->observacoes,
                ];
            });

        return response()->json([
            'reserva' => [
                'id' => $reserva->id,
                'nome' => $reserva->nome,
                'tipo' => $tipo,
                'data_inicial' => $reserva->data_inicial->format('d/m/Y'),
                'data_final' => $reserva->data_final->format('d/m/Y'),
            ],
            'dormitorios' => $dormitoriosDisponiveis
        ]);
    }

    /**
     * Realizar check-in
     */
    public function checkin(Request $request)
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $request->validate([
            'tipo' => 'required|in:usuario,visitante',
            'reserva_id' => 'required|integer',
            'dormitorio_id' => 'required|exists:dormitorios,id',
            'numero_vaga' => 'required|integer|min:1',
            'observacoes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Buscar a reserva
            if ($request->tipo === 'usuario') {
                $reserva = Alojamento::findOrFail($request->reserva_id);
                $reservaType = Alojamento::class;
            } else {
                $reserva = Visitante::findOrFail($request->reserva_id);
                $reservaType = Visitante::class;
            }

            // Verificar se pode fazer check-in
            if (!$reserva->podeCheckin()) {
                throw new \Exception('Esta reserva não pode fazer check-in no momento.');
            }

            // Buscar dormitório
            $dormitorio = Dormitorio::findOrFail($request->dormitorio_id);

            // NOVA VALIDAÇÃO: Verificar se o dormitório não é reservado para plantão
            if ($dormitorio->isReservadoPlantao()) {
                throw new \Exception('Este dormitório está reservado para o plantão da ACADEPOL e não pode receber check-ins externos.');
            }

            // Verificar se o dormitório está ativo e disponível
            if (!$dormitorio->disponivelParaCheckin()) {
                throw new \Exception('Este dormitório não está disponível para check-ins.');
            }

            // Validar número da vaga conforme capacidade do dormitório
            if ($request->numero_vaga > $dormitorio->capacidade_maxima) {
                throw new \Exception("A vaga {$request->numero_vaga} não existe neste dormitório. Capacidade máxima: {$dormitorio->capacidade_maxima} vagas.");
            }

            // Verificar se a vaga específica está disponível
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
                'reservavel_type' => $reservaType,
                'reservavel_id' => $reserva->id,
                'numero_vaga' => $request->numero_vaga,
                'checkin_por' => Auth::id(),
                'observacoes' => $request->observacoes
            ]);

            // Realizar check-in
            $ocupacao->realizarCheckin(Auth::id());

            DB::commit();

            \Log::info('Check-in realizado com sucesso', [
                'ocupacao_id' => $ocupacao->id,
                'dormitorio' => $dormitorio->numero,
                'capacidade' => $dormitorio->capacidade_maxima,
                'vaga' => $ocupacao->numero_vaga,
                'hospede' => $reserva->nome,
                'tipo_reserva' => $request->tipo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in realizado com sucesso!',
                'ocupacao' => [
                    'id' => $ocupacao->id,
                    'dormitorio' => $dormitorio->numero,
                    'dormitorio_nome' => $dormitorio->nome,
                    'capacidade_dormitorio' => $dormitorio->capacidade_maxima,
                    'vaga' => $ocupacao->numero_vaga,
                    'hospede' => $reserva->nome,
                    'checkin_at' => $ocupacao->checkin_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erro ao realizar check-in: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'dados_checkin' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Realizar check-out
     */
    public function checkout(Request $request, Ocupacao $ocupacao)
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $request->validate([
            'observacoes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Verificar se a ocupação está ativa
            if (!$ocupacao->isAtiva()) {
                throw new \Exception('Esta ocupação não está ativa.');
            }

            // Adicionar observações se fornecidas
            if ($request->observacoes) {
                $ocupacao->observacoes = ($ocupacao->observacoes ? $ocupacao->observacoes . "\n\n" : '') . 
                    "Check-out: " . $request->observacoes;
            }

            // Realizar check-out
            $ocupacao->realizarCheckout(Auth::id());

            DB::commit();

            \Log::info('Check-out realizado com sucesso', [
                'ocupacao_id' => $ocupacao->id,
                'dormitorio' => $ocupacao->dormitorio->numero,
                'vaga' => $ocupacao->numero_vaga,
                'hospede' => $ocupacao->reservavel->nome
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-out realizado com sucesso!',
                'ocupacao' => [
                    'id' => $ocupacao->id,
                    'dormitorio' => $ocupacao->dormitorio->numero,
                    'vaga' => $ocupacao->numero_vaga,
                    'hospede' => $ocupacao->reservavel->nome,
                    'checkout_at' => $ocupacao->checkout_at->format('d/m/Y H:i'),
                    'duracao_estadia' => $ocupacao->getDuracaoEstadia()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erro ao realizar check-out: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'ocupacao_id' => $ocupacao->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Buscar dormitórios disponíveis
     */
    public function dormitoriosDisponiveis()
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $dormitorios = Dormitorio::disponiveis()
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
                    'vagas_ocupadas' => $dormitorio->vagas_ocupadas,
                    'percentual_ocupacao' => $dormitorio->percentual_ocupacao,
                    'observacoes' => $dormitorio->observacoes,
                ];
            });

        return response()->json($dormitorios);
    }

    /**
     * Detalhes de um dormitório específico
     */
    public function dormitorioDetalhes(Dormitorio $dormitorio)
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $ocupacoes = $dormitorio->ocupacoesAtivas()
            ->with('reservavel')
            ->orderBy('numero_vaga')
            ->get()
            ->map(function ($ocupacao) {
                return [
                    'id' => $ocupacao->id,
                    'numero_vaga' => $ocupacao->numero_vaga,
                    'hospede_nome' => $ocupacao->reservavel->nome,
                    'hospede_tipo' => $ocupacao->tipo_reserva,
                    'checkin_at' => $ocupacao->checkin_at->format('d/m/Y H:i'),
                    'duracao' => $ocupacao->getDuracaoEstadia(),
                    'observacoes' => $ocupacao->observacoes,
                    'reserva_id' => $ocupacao->reservavel_id,
                    'reserva_type' => $ocupacao->reservavel_type,
                ];
            });

        return response()->json([
            'dormitorio' => [
                'id' => $dormitorio->id,
                'numero' => $dormitorio->numero,
                'nome' => $dormitorio->nome,
                'capacidade_maxima' => $dormitorio->capacidade_maxima,
                'vagas_ocupadas' => $dormitorio->vagas_ocupadas,
                'vagas_disponiveis' => $dormitorio->vagas_disponiveis,
                'percentual_ocupacao' => $dormitorio->percentual_ocupacao,
                'status' => $dormitorio->status,
                'status_texto' => $dormitorio->status_texto,
                'reservado_plantao' => $dormitorio->isReservadoPlantao(),
                'disponivel_checkin' => $dormitorio->disponivelParaCheckin(),
                'observacoes' => $dormitorio->observacoes,
            ],
            'ocupacoes' => $ocupacoes,
            'vagas_livres' => $dormitorio->getVagasLivres()
        ]);
    }

    /**
     * Buscar reservas para check-in
     */
    public function buscarReservas(Request $request)
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $termo = $request->get('termo', '');
        
        if (strlen($termo) < 3) {
            return response()->json(['reservas' => []]);
        }

        // Converter para minúsculo para busca case-insensitive
        $termoLimpo = strtolower(preg_replace('/[^0-9a-zA-ZÀ-ÿ\s]/', '', $termo));

        try {
            \Log::info('=== BUSCA CASE-INSENSITIVE ===', [
                'termo_original' => $termo,
                'termo_limpo' => $termoLimpo,
                'data_atual' => now()->toDateString()
            ]);

            // Buscar reservas de usuários aprovadas que NUNCA tiveram ocupação
            $reservasUsuarios = Alojamento::with(['usuario', 'ocupacoes'])
                ->where('status', 'aprovada')
                ->where(function($query) use ($termoLimpo) {
                    $query->whereRaw('LOWER(nome) LIKE ?', ["%{$termoLimpo}%"])
                          ->orWhereRaw('LOWER(cpf) LIKE ?', ["%{$termoLimpo}%"])
                          ->orWhereRaw('LOWER(matricula) LIKE ?', ["%{$termoLimpo}%"])
                          ->orWhereRaw('LOWER(email) LIKE ?', ["%{$termoLimpo}%"])
                          ->orWhereHas('usuario', function($userQuery) use ($termoLimpo) {
                              $userQuery->whereRaw('LOWER(name) LIKE ?', ["%{$termoLimpo}%"])
                                       ->orWhereRaw('LOWER(email) LIKE ?', ["%{$termoLimpo}%"])
                                       ->orWhereRaw('LOWER(matricula) LIKE ?', ["%{$termoLimpo}%"]);
                          });
                })
                // Excluir reservas que JÁ TIVERAM qualquer ocupação
                ->whereDoesntHave('ocupacoes')
                // Filtrar por período válido (com flexibilidade de 1 dia)
                ->whereDate('data_inicial', '<=', now()->addDay()->toDateString())
                ->whereDate('data_final', '>=', now()->toDateString())
                ->get()
                ->filter(function($alojamento) {
                    $pode = $alojamento->podeCheckin();
                    
                    \Log::info('USUÁRIO VERIFICADO:', [
                        'id' => $alojamento->id,
                        'nome_reserva' => $alojamento->nome,
                        'nome_usuario' => $alojamento->usuario?->name,
                        'total_ocupacoes' => $alojamento->ocupacoes->count(),
                        'pode_checkin' => $pode,
                        'data_inicial' => $alojamento->data_inicial?->format('Y-m-d'),
                        'data_final' => $alojamento->data_final?->format('Y-m-d'),
                        'motivo' => $alojamento->motivoNaoPodeCheckin()
                    ]);
                    
                    return $pode;
                })
                ->map(function($alojamento) {
                    return [
                        'id' => $alojamento->id,
                        'tipo' => 'usuario',
                        'nome' => $alojamento->usuario ? $alojamento->usuario->name : $alojamento->nome,
                        'cpf' => $alojamento->cpf,
                        'matricula' => $alojamento->usuario ? $alojamento->usuario->matricula : $alojamento->matricula,
                        'orgao' => $alojamento->orgao,
                        'data_inicial' => $alojamento->data_inicial,
                        'data_final' => $alojamento->data_final,
                    ];
                });

            // Buscar reservas de visitantes aprovadas que NUNCA tiveram ocupação
            $reservasVisitantes = Visitante::with('ocupacoes')
                ->where('status', 'aprovada')
                ->where(function($query) use ($termoLimpo) {
                    $query->whereRaw('LOWER(nome) LIKE ?', ["%{$termoLimpo}%"])
                          ->orWhereRaw('LOWER(cpf) LIKE ?', ["%{$termoLimpo}%"])
                          ->orWhereRaw('LOWER(matricula_funcional) LIKE ?', ["%{$termoLimpo}%"])
                          ->orWhereRaw('LOWER(email) LIKE ?', ["%{$termoLimpo}%"]);
                })
                // Excluir reservas que JÁ TIVERAM qualquer ocupação
                ->whereDoesntHave('ocupacoes')
                // Filtrar por período válido (com flexibilidade de 1 dia)
                ->whereDate('data_inicial', '<=', now()->addDay()->toDateString())
                ->whereDate('data_final', '>=', now()->toDateString())
                ->get()
                ->filter(function($visitante) {
                    $pode = $visitante->podeCheckin();
                    
                    \Log::info('VISITANTE VERIFICADO:', [
                        'id' => $visitante->id,
                        'nome' => $visitante->nome,
                        'total_ocupacoes' => $visitante->ocupacoes->count(),
                        'pode_checkin' => $pode,
                        'data_inicial' => $visitante->data_inicial?->format('Y-m-d'),
                        'data_final' => $visitante->data_final?->format('Y-m-d'),
                        'motivo' => $visitante->motivoNaoPodeCheckin()
                    ]);
                    
                    return $pode;
                })
                ->map(function($visitante) {
                    return [
                        'id' => $visitante->id,
                        'tipo' => 'visitante',
                        'nome' => $visitante->nome,
                        'cpf' => $visitante->cpf,
                        'matricula' => $visitante->matricula_funcional,
                        'orgao' => $visitante->orgao_trabalho,
                        'data_inicial' => $visitante->data_inicial,
                        'data_final' => $visitante->data_final,
                    ];
                });

            // Combinar e limitar resultados
            $todasReservas = $reservasUsuarios->concat($reservasVisitantes)
                ->sortBy('nome')
                ->take(20)
                ->values();

            \Log::info('RESULTADO FINAL CASE-INSENSITIVE:', [
                'termo_original' => $termo,
                'usuarios_encontrados' => $reservasUsuarios->count(),
                'visitantes_encontrados' => $reservasVisitantes->count(),
                'total' => $todasReservas->count(),
                'detalhes' => $todasReservas->map(function($r) {
                    return [
                        'id' => $r['id'],
                        'tipo' => $r['tipo'],
                        'nome' => $r['nome']
                    ];
                })->toArray()
            ]);

            return response()->json(['reservas' => $todasReservas]);
            
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar reservas: ' . $e->getMessage(), [
                'termo' => $termo,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'reservas' => [],
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * API index para retornar dados em JSON
     */
    public function apiIndex(Request $request)
    {
        $this->authorize('adminViewAny', Alojamento::class);

        $dormitorios = Dormitorio::with(['ocupacoesAtivas.reservavel'])
            ->orderBy('numero')
            ->get()
            ->map(function ($dormitorio) {
                $vagas = [];
                
                // Criar array de vagas com informações (capacidade dinâmica)
                for ($i = 1; $i <= $dormitorio->capacidade_maxima; $i++) {
                    $ocupacao = $dormitorio->ocupacoesAtivas()
                        ->where('numero_vaga', $i)
                        ->with('reservavel')
                        ->first();
                    
                    $vagas[] = [
                        'numero' => $i,
                        'ocupada' => $ocupacao ? true : false,
                        'ocupacao' => $ocupacao ? [
                        'id' => $ocupacao->id,
                        'hospede_nome' => $ocupacao->reservavel->nome,
                        'hospede_tipo' => $ocupacao->tipo_reserva,
                        'hospede_documento' => $ocupacao->reservavel->cpf ?? 'Não informado',
                        'hospede_telefone' => $ocupacao->reservavel->telefone ?? 'Não informado',
                        'checkin_at' => $ocupacao->checkin_at->format('d/m/Y H:i'),
                        'checkout_previsto' => $ocupacao->reservavel->data_final ? 
                            \Carbon\Carbon::parse($ocupacao->reservavel->data_final)->format('d/m/Y') : 
                            'Não informado',
                        'duracao' => $ocupacao->getDuracaoEstadia(),
                        'reserva_id' => $ocupacao->reservavel_id,
                        'reserva_type' => $ocupacao->reservavel_type,
                        'dormitorio_numero' => $dormitorio->numero,
                        'observacoes' => $ocupacao->observacoes,
                    ] : null
                    ];
                }
                
                return [
                    'id' => $dormitorio->id,
                    'numero' => $dormitorio->numero,
                    'nome' => $dormitorio->nome,
                    'capacidade_maxima' => $dormitorio->capacidade_maxima,
                    'vagas_ocupadas' => $dormitorio->vagas_ocupadas,
                    'vagas_disponiveis' => $dormitorio->vagas_disponiveis,
                    'percentual_ocupacao' => $dormitorio->percentual_ocupacao,
                    'classe_status' => $dormitorio->classe_status,
                    'status' => $dormitorio->status,
                    'status_texto' => $dormitorio->status_texto,
                    'reservado_plantao' => $dormitorio->isReservadoPlantao(),
                    'disponivel_checkin' => $dormitorio->disponivelParaCheckin(),
                    'vagas' => $vagas
                ];
            });

        // Estatísticas gerais (incluindo informações sobre reservados)
        $totalDormitorios = $dormitorios->count();
        $dormitoriosAtivos = $dormitorios->where('status', 'ativo')->count();
        $dormitoriosReservados = $dormitorios->where('reservado_plantao', true)->count();
        $totalVagas = $dormitorios->sum('capacidade_maxima');
        $vagasOcupadas = $dormitorios->sum('vagas_ocupadas');
        $vagasDisponiveis = $totalVagas - $vagasOcupadas;
        $percentualOcupacaoGeral = $totalVagas > 0 ? round(($vagasOcupadas / $totalVagas) * 100, 1) : 0;

        $estatisticas = [
            'total_dormitorios' => $totalDormitorios,
            'dormitorios_ativos' => $dormitoriosAtivos,
            'dormitorios_reservados' => $dormitoriosReservados,
            'total_vagas' => $totalVagas,
            'vagas_ocupadas' => $vagasOcupadas,
            'vagas_disponiveis' => $vagasDisponiveis,
            'percentual_ocupacao' => $percentualOcupacaoGeral,
            'dormitorios_lotados' => $dormitorios->where('vagas_disponiveis', 0)->count(),
            'dormitorios_disponiveis' => $dormitorios->where('disponivel_checkin', true)->count(),
        ];

        // Se for requisição AJAX, retorna JSON
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'props' => [
                    'dormitorios' => $dormitorios,
                    'estatisticas' => $estatisticas
                ]
            ]);
        }

        // Caso contrário, retorna a view normal
        return Inertia::render('Admin/Alojamento/Index', [
            'dormitorios' => $dormitorios,
            'estatisticas' => $estatisticas
        ]);
    }
}