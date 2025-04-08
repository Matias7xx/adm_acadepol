<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\DataSanitizerService;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'matricula' => 'required|string|min:7|unique:'.User::class,
            'cpf' => 'required|string|size:11|unique:'.User::class,
            'cargo' => 'required|string|max:255',
            'orgao' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
            'data_nascimento' => 'required|date|before:today|after:1940-01-01',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Sanitizar os dados
        $sanitizer = app(DataSanitizerService::class);
        $cpfClean = preg_replace('/[^0-9]/', '', $request->cpf);
        $telefoneClean = preg_replace('/[^0-9]/', '', $request->telefone);

        $user = User::create([
            'name' => $sanitizer->sanitizeString($request->name),
            'email' => $sanitizer->sanitizeEmail($request->email),
            'matricula' => $sanitizer->sanitizeString($request->matricula),
            'cpf' => $cpfClean,
            'cargo' => $sanitizer->sanitizeString($request->cargo),
            'orgao' => $sanitizer->sanitizeString($request->orgao),
            'telefone' => $telefoneClean,
            'data_nascimento' => $request->data_nascimento,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('aluno');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}