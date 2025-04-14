<?php

namespace App\Policies;

use App\Models\Requerimento;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RequerimentoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function adminViewAny(User $user)
    {
        return $user->hasPermissionTo('admin user');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Requerimento  $requerimento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function adminView(User $user, Requerimento $requerimento)
    {
        return $user->hasPermissionTo('admin user');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(?User $user)
    {
        // Qualquer usuário autenticado pode criar requerimentos
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Requerimento  $requerimento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function adminUpdate(User $user, Requerimento $requerimento)
    {
        return $user->hasPermissionTo('admin user');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Requerimento  $requerimento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function adminDelete(User $user, Requerimento $requerimento)
    {
        return $user->hasPermissionTo('admin user');
    }

    /**
     * Determine whether the user can view models belonging to themselves.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Requerimento  $requerimento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Requerimento $requerimento)
    {
        // Usuários podem ver seus próprios requerimentos
        return $user->id === $requerimento->user_id || $user->hasPermissionTo('admin user');
    }

    /**
     * Determine whether the user can cancel the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Requerimento  $requerimento
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function cancel(User $user, Requerimento $requerimento)
    {
        // Usuários só podem cancelar seus próprios requerimentos pendentes
        return $user->id === $requerimento->user_id && $requerimento->status === 'pendente';
    }
}