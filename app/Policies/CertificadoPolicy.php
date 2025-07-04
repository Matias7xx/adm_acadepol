<?php

namespace App\Policies;

use App\Models\Certificado;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificadoPolicy
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
        // Todos os usuários autenticados podem ver seus próprios certificados
        return true;
    }

    /**
     * 
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Certificado  $certificado
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Certificado $certificado)
    {
        // O usuário pode ver se:
        // 1. É o dono do certificado, OU
        // 2. É admin
        return $user->id === $certificado->user_id || 
               $user->hasRole(['admin', 'super-admin']) ||
               $user->hasPermissionTo('admin user');
    }

    /**
     * 
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Certificado  $certificado
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function download(User $user, Certificado $certificado)
    {
        // O usuário pode baixar se:
        // 1. É o dono do certificado, OU
        // 2. É admin
        return $user->id === $certificado->user_id || 
               $user->hasRole(['admin', 'super-admin']) ||
               $user->hasPermissionTo('admin user');
    }

    /**
     *
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Apenas administradores podem criar certificados
        return $user->hasRole(['admin', 'super-admin']) ||
               $user->hasPermissionTo('admin user');
    }

    /**
     *
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Certificado  $certificado
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Certificado $certificado)
    {
        // Apenas administradores podem atualizar certificados
        return $user->hasRole(['admin', 'super-admin']) ||
               $user->hasPermissionTo('admin user');
    }

    /**
     *
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Certificado  $certificado
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Certificado $certificado)
    {
        // Apenas administradores podem excluir certificados
        return $user->hasRole(['admin', 'super-admin']) ||
               $user->hasPermissionTo('admin user');
    }

    /**
     *
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manage(User $user)
    {
        // Apenas administradores podem gerenciar certificados de outros usuários
        return $user->hasRole(['admin', 'super-admin']) ||
               $user->hasPermissionTo('admin user');
    }
}