<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
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
    public function adminView(User $user, User $model): bool
    {
        return $user->hasPermissionTo('admin user');
    }

    /**
     * Determine whether the user can create models.
     * APENAS SUPER-ADMIN pode criar usuários
     */
    public function adminCreate(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can update the model.
     * APENAS SUPER-ADMIN pode editar usuários
     */
    public function adminUpdate(User $user, User $model = null): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can delete the model.
     * APENAS SUPER-ADMIN pode deletar usuários
     */
    public function adminDelete(User $user, User $model = null): bool
    {
        // Se um modelo específico foi passado, super-admin não pode deletar a si mesmo
        if ($model && $user->id === $model->id) {
            return false;
        }
        
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function adminRestore(User $user, User $model): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function adminForceDelete(User $user, User $model): bool
    {
        return $user->hasRole('super-admin');
    }
}