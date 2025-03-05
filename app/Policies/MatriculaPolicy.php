<?php

namespace App\Policies;

use App\Models\Matricula;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MatriculaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('admin user');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matricula  $matricula
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Matricula $matricula)
    {
        // Admins podem ver qualquer matrícula
        if ($user->hasPermissionTo('admin user')) {
            return true;
        }
        
        // Usuários podem ver suas próprias matrículas
        return $user->id === $matricula->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Qualquer usuário autenticado pode se inscrever em um curso
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matricula  $matricula
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Matricula $matricula)
    {
        // Apenas administradores podem atualizar matrículas
        return $user->hasPermissionTo('admin user');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matricula  $matricula
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Matricula $matricula)
    {
        // Apenas administradores podem excluir matrículas
        return $user->hasPermissionTo('admin user');
    }
    
    /**
     * Determine whether the user can approve the matricula.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matricula  $matricula
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function approve(User $user, Matricula $matricula)
    {
        // Apenas administradores podem aprovar matrículas
        return $user->hasPermissionTo('admin user');
    }
    
    /**
     * Determine whether the user can reject the matricula.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Matricula  $matricula
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function reject(User $user, Matricula $matricula)
    {
        // Apenas administradores podem rejeitar matrículas
        return $user->hasPermissionTo('admin user');
    }
}