<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuditLogController extends Controller
{
  public function index(Request $request)
  {
    $this->authorize('adminViewAny', AuditLog::class);

    $query = AuditLog::query()->with('user:id,name,matricula');

    // Filtro: módulo
    if ($request->filled('modulo')) {
      $query->where('modulo', $request->modulo);
    }

    // Filtro: ação
    if ($request->filled('acao')) {
      $query->where('acao', $request->acao);
    }

    // Filtro: status
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    // Filtro: usuário (por nome ou matrícula)
    if ($request->filled('usuario')) {
      $busca = $request->usuario;
      $query->where(function ($q) use ($busca) {
        $q->where('user_name', 'ILIKE', "%{$busca}%")
          ->orWhere('user_matricula', 'ILIKE', "%{$busca}%")
          ->orWhere('user_email', 'ILIKE', "%{$busca}%");
      });
    }

    // Filtro: model_label (ex: título da notícia)
    if ($request->filled('search')) {
      $busca = $request->search;
      $query->where(function ($q) use ($busca) {
        $q->where('descricao', 'ILIKE', "%{$busca}%")->orWhere(
          'model_label',
          'ILIKE',
          "%{$busca}%",
        );
      });
    }

    // Filtro: período
    if ($request->filled('data_inicio')) {
      $query->whereDate('created_at', '>=', $request->data_inicio);
    }
    if ($request->filled('data_fim')) {
      $query->whereDate('created_at', '<=', $request->data_fim);
    }

    // Filtro: IP
    if ($request->filled('ip')) {
      $query->where('ip', $request->ip_filter);
    }

    $logs = $query
      ->orderByDesc('created_at')
      ->paginate(50)
      ->onEachSide(2)
      ->appends($request->query());

    // Estatísticas rápidas (últimas 24h)
    $stats = [
      'total_hoje' => AuditLog::whereDate('created_at', today())->count(),
      'total_erros' => AuditLog::whereDate('created_at', today())
        ->where('status', 'error')
        ->count(),
      'total_avisos' => AuditLog::whereDate('created_at', today())
        ->where('status', 'warning')
        ->count(),
      'total_logins' => AuditLog::whereDate('created_at', today())
        ->where('acao', 'login')
        ->count(),
    ];

    return Inertia::render('Admin/AuditLogs/Index', [
      'logs' => $logs,
      'stats' => $stats,
      'modulos' => AuditLog::MODULOS,
      'acoes' => AuditLog::ACOES,
      'filters' => $request->only([
        'modulo',
        'acao',
        'status',
        'usuario',
        'search',
        'data_inicio',
        'data_fim',
        'ip_filter',
      ]),
      'can' => [
        'view' => Auth::user()->can('adminViewAny', AuditLog::class),
      ],
    ]);
  }

  public function show(AuditLog $auditLog)
  {
    $this->authorize('adminViewAny', AuditLog::class);

    return Inertia::render('Admin/AuditLogs/Show', [
      'log' => $auditLog->load('user:id,name,matricula,email'),
      'modulos' => AuditLog::MODULOS,
      'acoes' => AuditLog::ACOES,
    ]);
  }
}
