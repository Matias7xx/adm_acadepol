<?php

namespace App\Policies;

use App\Models\Noticia;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NoticiaPolicy
{
  use HandlesAuthorization;

  /**
   * Determina se o usuário pode visualizar a listagem admin de notícias
   */
  public function adminViewAny(User $user)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determina se o usuário pode visualizar uma notícia específica no admin
   */
  public function adminView(User $user, Noticia $noticia)
  {
    return $user->hasPermissionTo('admin user');
  }

  /**
   * Determina se o usuário pode criar notícias
   */
  public function adminCreate(User $user)
  {
    return $user->hasPermissionTo('noticia create');
  }

  /**
   * Determina se o usuário pode atualizar uma notícia
   */
  public function adminUpdate(User $user, Noticia $noticia)
  {
    return $user->hasPermissionTo('noticia edit');
  }

  /**
   * Determina se o usuário pode excluir uma notícia
   */
  public function adminDelete(User $user, Noticia $noticia)
  {
    return $user->hasPermissionTo('noticia delete');
  }
}
