<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\Role' => 'BalajiDharma\LaravelAdminCore\Policies\RolePolicy',
        'App\Models\Permission' => 'BalajiDharma\LaravelAdminCore\Policies\PermissionPolicy',
        'App\Models\User' => 'BalajiDharma\LaravelAdminCore\Policies\UserPolicy',
        'BalajiDharma\LaravelCategory\Models\Category' => 'BalajiDharma\LaravelAdminCore\Policies\CategoryPolicy',
        'BalajiDharma\LaravelCategory\Models\CategoryType' => 'BalajiDharma\LaravelAdminCore\Policies\CategoryTypePolicy',
        'BalajiDharma\LaravelMenu\Models\Menu' => 'BalajiDharma\LaravelAdminCore\Policies\MenuPolicy',
        'BalajiDharma\LaravelMenu\Models\MenuItem' => 'BalajiDharma\LaravelAdminCore\Policies\MenuItemPolicy',
        'Plank\Mediable\Media' => 'BalajiDharma\LaravelAdminCore\Policies\MediaPolicy',
        'App\Models\Matricula' => 'App\Policies\MatriculaPolicy',
        'App\Models\Curso' => 'App\Policies\CursoPolicy',
        'App\Models\Director' => 'App\Policies\DirectorPolicy',
        'App\Models\Alojamento' => 'App\Policies\AlojamentoPolicy',
        'App\Models\Noticia' => 'App\Policies\NoticiaPolicy',
        'App\Models\Contato' => 'App\Policies\ContatoPolicy',
        'App\Models\Visitante' => 'App\Policies\VisitantePolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Implicitly grant "Super-Admin" role all permission checks using can()
        Gate::before(function ($user, $ability) {
            if ($user->hasRole(config('admin.roles.super_admin'))) {
                return true;
            }
        });
    }
}
