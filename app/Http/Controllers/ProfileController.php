<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {

        $additionalValidation = [];
        if ($request->has('telefone')) {
            $additionalValidation['telefone'] = ['nullable', 'string', 'max:20'];
        }
        if ($request->has('lotacao')) {
            $additionalValidation['lotacao'] = ['nullable', 'string', 'max:255'];
        }
        
        if (!empty($additionalValidation)) {
            $request->validate($additionalValidation);
        }

        $data = $request->validated();
        
        if ($request->has('telefone')) {
            $data['telefone'] = $request->telefone;
        }
        if ($request->has('lotacao')) {
            $data['lotacao'] = $request->lotacao;
        }
        
        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('message', 'Perfil atualizado com sucesso!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('message', 'Sua conta foi excluída com sucesso.');
    }
}