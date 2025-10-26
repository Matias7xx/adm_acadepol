<?php

namespace App\Policies;

use App\Models\Contato;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContatoPolicy
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
   * @param  \App\Models\Contato  $contato
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminView(User $user, Contato $contato)
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
    // Qualquer usuário (ou visitante) pode enviar mensagem de contato
    return true;
  }

  /**
   * Determine whether the user can update the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Contato  $contato
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminUpdate(User $user, Contato $contato)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can delete the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Contato  $contato
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminDelete(User $user, Contato $contato)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can respond to the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Contato  $contato
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function responder(User $user, Contato $contato)
  {
    return $user->hasPermissionTo('admin user');
  }
}
