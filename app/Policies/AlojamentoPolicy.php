<?php

namespace App\Policies;

use App\Models\Alojamento;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlojamentoPolicy
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
   * @param  \App\Models\Alojamento  $alojamento
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminView(User $user, Alojamento $alojamento)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can create models.
   *
   * @param  \App\Models\User  $user
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function create(User $user)
  {
    // Qualquer usuário autenticado pode criar uma pré-reserva
    return true;
  }

  /**
   * Determine whether the user can update the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Alojamento  $alojamento
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminUpdate(User $user, Alojamento $alojamento)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can delete the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Alojamento  $alojamento
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminDelete(User $user, Alojamento $alojamento)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can view own reservations.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Alojamento  $alojamento
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function view(User $user, Alojamento $alojamento)
  {
    // Usuários só podem ver suas próprias reservas
    return $user->id === $alojamento->user_id ||
      $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can cancel the reservation.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Alojamento  $alojamento
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function cancel(User $user, Alojamento $alojamento)
  {
    // Usuários só podem cancelar suas próprias reservas pendentes
    return $user->id === $alojamento->user_id &&
      $alojamento->status === 'pendente';
  }
}
