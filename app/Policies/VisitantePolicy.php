<?php

namespace App\Policies;

use App\Models\Visitante;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitantePolicy
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
   * @param  \App\Models\Visitante  $visitante
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminView(User $user, Visitante $visitante)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can create models.
   *
   * @param  \App\Models\User|null  $user
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function create(?User $user)
  {
    // Visitantes podem criar reservas sem estar logados
    return true;
  }

  /**
   * Determine whether the user can update the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Visitante  $visitante
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminUpdate(User $user, Visitante $visitante)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can delete the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Visitante  $visitante
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminDelete(User $user, Visitante $visitante)
  {
    return $user->hasPermissionTo('admin user');
  }
}
