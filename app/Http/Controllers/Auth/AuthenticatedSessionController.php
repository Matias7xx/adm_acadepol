<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        // Armazenar parâmetros de intenção (por exemplo, curso_id, acao, etc.)
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
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        $portalUrl = env('PORTAL_ALUNO_URL', 'http://localhost:8001');

        // Verificar se há uma rota específica para redirecionar após o login
        if (Session::has('intended_route')) {
            $route = Session::get('intended_route');
            $params = [];
            
            // Adicionar parâmetros conforme necessário
            if (Session::has('intended_curso_id')) {
                $params['curso'] = Session::get('intended_curso_id');
            }
            
            // Limpar as variáveis de sessão após usá-las
            Session::forget(['intended_route', 'intended_curso_id', 'intended_acao']);
            
            return redirect()->route($route, $params);
        }

        // Redirecionamento baseado em role
     if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
     }/* else if ($user->hasRole('servidor')) {
        // Redirecionar para a home do servidor no portal-aluno
        return redirect($portalUrl . '/servidor');
    } else if ($user->hasRole('aluno')) {
        // Redirecionar para a home do aluno no portal-aluno 
        return redirect($portalUrl . '/aluno');
    } */

    // Fallback
    return redirect()->intended(route('home', absolute: false));

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}