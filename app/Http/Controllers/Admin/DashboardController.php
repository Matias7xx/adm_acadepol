<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Alojamento;
use App\Models\Visitante;
use App\Models\Requerimento;
use App\Models\Contato;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
  /**
   * Exibir o dashboard administrativo
   */
  public function index()
  {
    // Obter métricas principais
    $dadosDashboard = $this->obterMetricasDashboard();

    // Obter dados para o gráfico
    $dadosGrafico = $this->obterDadosGrafico();

    return Inertia::render('Admin/Dashboard', [
      'dashboardData' => $dadosDashboard,
      'chartData' => $dadosGrafico,
    ]);
  }

  /**
   * Obter todas as métricas do dashboard
   */
  private function obterMetricasDashboard()
  {
    $mesAtual = Carbon::now()->startOfMonth();
    $mesPassado = Carbon::now()->subMonth()->startOfMonth();

    return [
      'usuarios' => $this->obterMetricasUsuarios($mesAtual, $mesPassado),
      'cursos' => $this->obterMetricasCursos(),
      'matriculas' => $this->obterMetricasMatriculas($mesAtual, $mesPassado),
      'alojamento' => $this->obterMetricasAlojamento($mesAtual, $mesPassado),
      'requerimentos' => $this->obterMetricasRequerimentos(
        $mesAtual,
        $mesPassado,
      ),
      'contatos' => $this->obterMetricasContatos($mesAtual, $mesPassado),
    ];
  }

  /**
   * Métricas de usuários
   */
  private function obterMetricasUsuarios($mesAtual, $mesPassado)
  {
    // Excluir usuários administrativos da contagem
    $usuariosExcluidos = ['Admin User', 'Example User', 'Super Admin'];

    $totalUsuarios = User::whereNotIn('name', $usuariosExcluidos)->count();
    $usuariosMesAtual = User::whereNotIn('name', $usuariosExcluidos)
      ->where('created_at', '>=', $mesAtual)
      ->count();
    $usuariosMesPassado = User::whereNotIn('name', $usuariosExcluidos)
      ->whereBetween('created_at', [$mesPassado, $mesAtual])
      ->count();

    $percentual = $this->calcularPercentual(
      $usuariosMesAtual,
      $usuariosMesPassado,
    );

    return [
      'total' => $totalUsuarios,
      'novos_mes' => $usuariosMesAtual,
      'trend' => $percentual >= 0 ? 'up' : 'down',
      'percentage' => abs($percentual),
    ];
  }

  /**
   * Métricas de cursos
   */
  private function obterMetricasCursos()
  {
    $totalCursos = Curso::count();
    $cursosAbertos = Curso::where('status', 'aberto')->count();
    $cursosFechados = Curso::where('status', 'fechado')->count();
    $cursosConcluidos = Curso::where('status', 'concluído')->count();

    return [
      'total' => $totalCursos,
      'concluidos' => $cursosConcluidos,
      'em_aberto' => $cursosAbertos,
      'fechados' => $cursosFechados,
      'trend' => 'up',
      'percentage' => 8.3,
    ];
  }

  /**
   * Métricas de matrículas
   */
  private function obterMetricasMatriculas($mesAtual, $mesPassado)
  {
    $totalMatriculas = Matricula::count();
    $matriculasPendentes = Matricula::where('status', 'pendente')->count();
    $matriculasAprovadas = Matricula::where('status', 'aprovada')->count();
    $matriculasRejeitadas = Matricula::where('status', 'rejeitada')->count();

    $matriculasMesAtual = Matricula::where(
      'created_at',
      '>=',
      $mesAtual,
    )->count();
    $matriculasMesPassado = Matricula::whereBetween('created_at', [
      $mesPassado,
      $mesAtual,
    ])->count();

    $percentual = $this->calcularPercentual(
      $matriculasMesAtual,
      $matriculasMesPassado,
    );

    return [
      'total' => $totalMatriculas,
      'pendentes' => $matriculasPendentes,
      'aprovadas' => $matriculasAprovadas,
      'rejeitadas' => $matriculasRejeitadas,
      'total_mes' => $matriculasMesAtual,
      'trend' => $percentual >= 0 ? 'up' : 'down',
      'percentage' => abs($percentual),
    ];
  }

  /**
   * Métricas de alojamento
   */
  private function obterMetricasAlojamento($mesAtual, $mesPassado)
  {
    // Combinar reservas de usuários e visitantes
    $reservasUsuariosMes = Alojamento::where(
      'created_at',
      '>=',
      $mesAtual,
    )->count();
    $reservasVisitantesMes = Visitante::where(
      'created_at',
      '>=',
      $mesAtual,
    )->count();
    $totalReservasMes = $reservasUsuariosMes + $reservasVisitantesMes;

    $reservasPendentesUsuarios = Alojamento::where(
      'status',
      'pendente',
    )->count();
    $reservasPendentesVisitantes = Visitante::where(
      'status',
      'pendente',
    )->count();
    $totalPendentes = $reservasPendentesUsuarios + $reservasPendentesVisitantes;

    $reservasAprovadasUsuarios = Alojamento::where(
      'status',
      'aprovada',
    )->count();
    $reservasAprovadasVisitantes = Visitante::where(
      'status',
      'aprovada',
    )->count();
    $totalAprovadas = $reservasAprovadasUsuarios + $reservasAprovadasVisitantes;

    $reservasRejeitadasUsuarios = Alojamento::where(
      'status',
      'rejeitada',
    )->count();
    $reservasRejeitadasVisitantes = Visitante::where(
      'status',
      'rejeitada',
    )->count();
    $totalRejeitadas =
      $reservasRejeitadasUsuarios + $reservasRejeitadasVisitantes;

    // Calcular percentual de crescimento
    $reservasMesPassado =
      Alojamento::whereBetween('created_at', [
        $mesPassado,
        $mesAtual,
      ])->count() +
      Visitante::whereBetween('created_at', [$mesPassado, $mesAtual])->count();
    $percentual = $this->calcularPercentual(
      $totalReservasMes,
      $reservasMesPassado,
    );

    return [
      'reservas_mes' => $totalReservasMes,
      'pendentes' => $totalPendentes,
      'aprovadas' => $totalAprovadas,
      'rejeitadas' => $totalRejeitadas,
      'trend' => $percentual >= 0 ? 'up' : 'down',
      'percentage' => abs($percentual),
    ];
  }

  /**
   * Métricas de requerimentos
   */
  private function obterMetricasRequerimentos($mesAtual, $mesPassado)
  {
    $pendentes = Requerimento::where('status', 'pendente')->count();
    $totalMes = Requerimento::where('created_at', '>=', $mesAtual)->count();
    $deferidos = Requerimento::where('status', 'deferido')->count();
    $indeferidos = Requerimento::where('status', 'indeferido')->count();

    $requerimentosMesPassado = Requerimento::whereBetween('created_at', [
      $mesPassado,
      $mesAtual,
    ])->count();
    $percentual = $this->calcularPercentual(
      $totalMes,
      $requerimentosMesPassado,
    );

    return [
      'pendentes' => $pendentes,
      'total_mes' => $totalMes,
      'deferidos' => $deferidos,
      'indeferidos' => $indeferidos,
      'trend' => $percentual >= 0 ? 'up' : 'down',
      'percentage' => abs($percentual),
    ];
  }

  /**
   * Métricas de contatos
   */
  private function obterMetricasContatos($mesAtual, $mesPassado)
  {
    $pendentes = Contato::where('status', 'pendente')->count();
    $totalMes = Contato::where('created_at', '>=', $mesAtual)->count();
    $respondidos = Contato::where('status', 'respondido')->count();
    $arquivados = Contato::where('status', 'arquivado')->count();

    $contatosMesPassado = Contato::whereBetween('created_at', [
      $mesPassado,
      $mesAtual,
    ])->count();
    $percentual = $this->calcularPercentual($totalMes, $contatosMesPassado);

    return [
      'pendentes' => $pendentes,
      'total_mes' => $totalMes,
      'respondidos' => $respondidos,
      'arquivados' => $arquivados,
      'trend' => $percentual >= 0 ? 'up' : 'down',
      'percentage' => abs($percentual),
    ];
  }

  /**
   * Obter dados para o gráfico de evolução anual
   */
  private function obterDadosGrafico()
  {
    $anoAtual = Carbon::now()->year;
    $meses = [];
    $usuariosPorMes = [];
    $cursosPorMes = [];
    $matriculasPorMes = [];
    $alojamentoPorMes = [];

    // Excluir usuários administrativos
    $usuariosExcluidos = ['Admin User', 'Example User', 'Super Admin'];

    for ($i = 1; $i <= 12; $i++) {
      $inicioMes = Carbon::create($anoAtual, $i, 1)->startOfMonth();
      $fimMes = Carbon::create($anoAtual, $i, 1)->endOfMonth();

      $meses[] = $inicioMes->format('M');

      // Usuários cadastrados até o final do mês
      $usuariosPorMes[] = User::whereNotIn('name', $usuariosExcluidos)
        ->where('created_at', '<=', $fimMes)
        ->count();

      // Cursos concluídos no mês
      $cursosPorMes[] = Curso::where('status', 'concluído')
        ->whereBetween('updated_at', [$inicioMes, $fimMes])
        ->count();

      // Matrículas no mês
      $matriculasPorMes[] = Matricula::whereBetween('created_at', [
        $inicioMes,
        $fimMes,
      ])->count();

      // Reservas de alojamento no mês (usuários + visitantes)
      $reservasUsuarios = Alojamento::whereBetween('created_at', [
        $inicioMes,
        $fimMes,
      ])->count();
      $reservasVisitantes = Visitante::whereBetween('created_at', [
        $inicioMes,
        $fimMes,
      ])->count();
      $alojamentoPorMes[] = $reservasUsuarios + $reservasVisitantes;
    }

    return [
      'labels' => $meses,
      'datasets' => [
        [
          'label' => 'Usuários Cadastrados',
          'data' => $usuariosPorMes,
          'borderColor' => '#3B82F6',
          'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
          'tension' => 0.4,
        ],
        [
          'label' => 'Cursos Concluídos',
          'data' => $cursosPorMes,
          'borderColor' => '#10B981',
          'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
          'tension' => 0.4,
        ],
        [
          'label' => 'Matrículas',
          'data' => $matriculasPorMes,
          'borderColor' => '#8B5CF6',
          'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
          'tension' => 0.4,
        ],
        [
          'label' => 'Reservas de Alojamento',
          'data' => $alojamentoPorMes,
          'borderColor' => '#F59E0B',
          'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
          'tension' => 0.4,
        ],
      ],
    ];
  }

  /**
   * Calcular percentual de variação
   */
  private function calcularPercentual($atual, $anterior)
  {
    if ($anterior == 0) {
      return $atual > 0 ? 100 : 0;
    }

    return round((($atual - $anterior) / $anterior) * 100, 1);
  }

  /**
   * Obter estatísticas adicionais do sistema
   */
  public function obterEstatisticasAdicionais()
  {
    // Taxa de aprovação geral (requerimentos + alojamento + matrículas)
    $totalRequerimentos = Requerimento::whereIn('status', [
      'deferido',
      'indeferido',
    ])->count();
    $requerimentosDeferidos = Requerimento::where(
      'status',
      'deferido',
    )->count();

    $totalReservas =
      Alojamento::whereIn('status', ['aprovada', 'rejeitada'])->count() +
      Visitante::whereIn('status', ['aprovada', 'rejeitada'])->count();
    $reservasAprovadas =
      Alojamento::where('status', 'aprovada')->count() +
      Visitante::where('status', 'aprovada')->count();

    $totalMatriculas = Matricula::whereIn('status', [
      'aprovada',
      'rejeitada',
    ])->count();
    $matriculasAprovadas = Matricula::where('status', 'aprovada')->count();

    $totalItens = $totalRequerimentos + $totalReservas + $totalMatriculas;
    $itensAprovados =
      $requerimentosDeferidos + $reservasAprovadas + $matriculasAprovadas;

    $taxaAprovacao =
      $totalItens > 0 ? round(($itensAprovados / $totalItens) * 100) : 0;

    // Tempo médio de resposta (baseado em requerimentos, contatos e matrículas)
    $tempoMedioResposta = $this->calcularTempoMedioResposta();

    // Ocupação do alojamento (baseado em reservas aprovadas dos últimos 30 dias)
    $ocupacaoAlojamento = $this->calcularOcupacaoAlojamento();

    return [
      'taxa_aprovacao' => $taxaAprovacao,
      'tempo_medio_resposta' => $tempoMedioResposta,
      'ocupacao_alojamento' => $ocupacaoAlojamento,
    ];
  }

  /**
   * Calcular tempo médio de resposta em dias
   */
  private function calcularTempoMedioResposta()
  {
    $requerimentosRespondidos = Requerimento::whereIn('status', [
      'deferido',
      'indeferido',
    ])
      ->whereNotNull('data_resposta')
      ->get();

    $contatosRespondidos = Contato::where('status', 'respondido')
      ->whereNotNull('data_resposta')
      ->get();

    $matriculasRespondidas = Matricula::whereIn('status', [
      'aprovada',
      'rejeitada',
    ])
      ->where(function ($query) {
        $query->whereNotNull('data_aprovacao')->orWhereNotNull('data_rejeicao');
      })
      ->get();

    $totalTempos = 0;
    $totalItens = 0;

    foreach ($requerimentosRespondidos as $req) {
      $totalTempos += $req->created_at->diffInDays($req->data_resposta);
      $totalItens++;
    }

    foreach ($contatosRespondidos as $contato) {
      $totalTempos += $contato->created_at->diffInDays($contato->data_resposta);
      $totalItens++;
    }

    foreach ($matriculasRespondidas as $matricula) {
      $dataResposta = $matricula->data_aprovacao ?? $matricula->data_rejeicao;
      if ($dataResposta) {
        $totalTempos += $matricula->created_at->diffInDays($dataResposta);
        $totalItens++;
      }
    }

    return $totalItens > 0 ? round($totalTempos / $totalItens) : 0;
  }

  /**
   * Calcular ocupação do alojamento
   */
  private function calcularOcupacaoAlojamento()
  {
    $capacidadeMaxima = config('alojamento.capacidade_maxima', 30);
    $ultimosTrintaDias = Carbon::now()->subDays(30);

    $reservasAprovadas =
      Alojamento::where('status', 'aprovada')
        ->where('data_inicial', '>=', $ultimosTrintaDias)
        ->count() +
      Visitante::where('status', 'aprovada')
        ->where('data_inicial', '>=', $ultimosTrintaDias)
        ->count();

    return $capacidadeMaxima > 0
      ? round(($reservasAprovadas / $capacidadeMaxima) * 100)
      : 0;
  }
}
