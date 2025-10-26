<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CursoPolicy //Policy garante os controles de autorização adequados
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
   * @param  \App\Models\Curso  $curso
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminView(User $user, Curso $curso)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determine whether the user can create models.
   *
   * @param  \App\Models\User  $user
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminCreate(User $user)
  {
    return $user->hasPermissionTo('curso create');
  }

  /**
   * Determine whether the user can update the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Curso  $curso
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminUpdate(User $user, Curso $curso)
  {
    return $user->hasPermissionTo('curso edit');
  }

  /**
   * Determine whether the user can delete the model.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Models\Curso  $curso
   * @return \Illuminate\Auth\Access\Response|bool
   */
  public function adminDelete(User $user, Curso $curso)
  {
    return $user->hasPermissionTo('curso delete');
  }
}
