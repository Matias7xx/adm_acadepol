<?php

namespace App\Http\Middleware;

use BalajiDharma\LaravelMenu\Models\Menu;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
  /**
   * The root template that is loaded on the first page visit.
   *
   * @var string
   */
  protected $rootView = 'app';

  /**
   * Determine the current asset version.
   */
  public function version(Request $request): string|null
  {
    return parent::version($request);
  }

  /**
   * Define the props that are shared by default.
   *
   * @return array<string, mixed>
   */
  public function share(Request $request): array
  {
    $user = $request->user();

    return [
      ...parent::share($request),
      'auth' => [
        'user' => $user
          ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'matricula' => $user->matricula ?? null,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'cargo' => $user->cargo ?? null,
            'lotacao' => $user->lotacao ?? null,
            'cpf' => $user->cpf ?? null,
            'telefone' => $user->telefone ?? null,
            'orgao' => $user->orgao ?? null,
            'data_nascimento' => $user->data_nascimento ?? null,
            // Adicionar roles e permissions
            'roles' => $user->roles ? json_decode($user->roles, true) : [],
            'permissions' => $user->permissions
              ? json_decode($user->permissions, true)
              : [],
            // Adicionar métodos para verificação
            'has_admin_access' => $this->hasAdminAccess($user),
          ]
          : null,
      ],
      'flash' => [
        'message' => fn() => $request->session()->get('message'),
      ],
    ];
  }

  /**
   * Verifica se o usuário tem acesso de administrador
   */
  protected function hasAdminAccess($user): bool
  {
    if (!$user) {
      return false;
    }

    // Verifica roles
    if ($user->roles) {
      $roles = json_decode($user->roles, true) ?: [];
      $hasAdminRole = collect($roles)->contains(function ($role) {
        return in_array($role['name'] ?? '', ['admin', 'super-admin']);
      });
      if ($hasAdminRole) {
        return true;
      }
    }

    // Verifica permissions
    if ($user->permissions) {
      $permissions = json_decode($user->permissions, true) ?: [];
      $hasAdminPermission = collect($permissions)->contains(function (
        $permission,
      ) {
        return ($permission['name'] ?? '') === 'admin user';
      });
      if ($hasAdminPermission) {
        return true;
      }
    }

    return false;
  }
}
