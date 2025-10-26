<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
  use HandlesAuthorization;

  /**
   * Determine whether the user can view any models.
   */
  public function adminViewAny(User $user): bool
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can view the model.
   */
  public function adminView(User $user, Role $role): bool
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can create models.
   * APENAS SUPER-ADMIN pode criar roles
   */
  public function adminCreate(User $user): bool
  {
    return $user->hasRole('super-admin');
  }

  /**
   * Determine whether the user can update the model.
   * APENAS SUPER-ADMIN pode editar roles
   */
  public function adminUpdate(User $user, Role $role = null): bool
  {
    return $user->hasRole('super-admin');
  }

  /**
   * Determine whether the user can delete the model.
   * APENAS SUPER-ADMIN pode deletar roles
   */
  public function adminDelete(User $user, Role $role = null): bool
  {
    // Se uma role específica foi passada, não pode deletar a role super-admin
    if ($role && $role->name === 'super-admin') {
      return false;
    }

    return $user->hasRole('super-admin');
  }

  /**
   * Determine whether the user can restore the model.
   */
  public function adminRestore(User $user, Role $role): bool
  {
    return $user->hasRole('super-admin');
  }

  /**
   * Determine whether the user can permanently delete the model.
   */
  public function adminForceDelete(User $user, Role $role): bool
  {
    return $user->hasRole('super-admin');
  }
}
