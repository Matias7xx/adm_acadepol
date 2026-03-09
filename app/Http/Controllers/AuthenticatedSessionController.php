<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
  protected AuditLoggerService $auditLogger;

  public function __construct(AuditLoggerService $auditLogger)
  {
    $this->auditLogger = $auditLogger;
  }

  /**
   * Display the login view.
   */
  public function create(Request $request): Response
  {
    if ($request->has('intended_route')) {
      Session::put('intended_route', $request->input('intended_route'));
    }
    if ($request->has('curso_id')) {
      Session::put('intended_curso_id', $request->input('curso_id'));
    }
    if ($request->has('acao')) {
      Session::put('intended_acao', $request->input('acao'));
    }

    return Inertia::render('Auth/Login', [
      'canResetPassword' => Route::has('password.request'),
      'status' => session('status'),
      'intendedAction' => session('intended_acao'),
    ]);
  }

  /**
   * Handle an incoming authentication request.
   */
  public function store(LoginRequest $request): RedirectResponse
  {
    try {
      $request->authenticate();
    } catch (\Illuminate\Validation\ValidationException $e) {
      // Auditoria: falha de login
      $this->auditLogger->registrarFalhaLogin(
        'Credenciais inválidas para matrícula: ' .
          $request->input('matricula', '-'),
      );
      throw $e;
    }

    $request->session()->regenerate();

    // Auditoria: login bem-sucedido
    $this->auditLogger->registrarLogin();

    $user = Auth::user();

    if (Session::has('intended_route')) {
      $route = Session::get('intended_route');
      $params = [];
      if (Session::has('intended_curso_id')) {
        $params['curso'] = Session::get('intended_curso_id');
      }
      Session::forget(['intended_route', 'intended_curso_id', 'intended_acao']);
      return redirect()->route($route, $params);
    }

    return redirect()->intended(route('home', absolute: false));
  }

  /**
   * Destroy an authenticated session.
   */
  public function destroy(Request $request): RedirectResponse
  {
    // Auditoria: logout (antes de sair)
    $this->auditLogger->registrarLogout();

    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
  }
}
