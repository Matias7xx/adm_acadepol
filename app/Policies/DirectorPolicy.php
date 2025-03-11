<?php

namespace App\Policies;

use App\Models\Director;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DirectorPolicy
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
     * @param  \App\Models\Director  $diretor
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function adminView(User $user, Director $diretor)
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
        return $user->hasPermissionTo('director create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Director  $diretor
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function adminUpdate(User $user, Director $diretor)
    {
        return $user->hasPermissionTo('director edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Director  $diretor
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function adminDelete(User $user, Director $diretor)
    {
        return $user->hasPermissionTo('director delete');
    }
}