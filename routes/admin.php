<?php

use App\Http\Controllers\Admin\CategoryTypeController;
use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\DirectorController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlojamentoController;
use App\Http\Controllers\MatriculaController;
use App\Http\Middleware\Admin\HandleInertiaAdminRequests;
use App\Http\Middleware\HasAccessAdmin;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rotas Administrativas
|--------------------------------------------------------------------------
*/

Route::group([
    'prefix' => config('admin.prefix'),
    'middleware' => ['auth', HasAccessAdmin::class, HandleInertiaAdminRequests::class],
    'as' => 'admin.',
], function () {
    // Dashboard
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Usuários, Funções e Permissões
    |--------------------------------------------------------------------------
    */
    
    // Usuários
    Route::resource('user', UserController::class);
    Route::controller(UserController::class)->group(function () {
        Route::get('edit-account-info', 'accountInfo')->name('account.info');
        Route::post('edit-account-info', 'accountInfoStore')->name('account.info.store');
        Route::post('change-password', 'changePasswordStore')->name('account.password.store');
    });
    
    // Funções e Permissões
    Route::resource('role', RoleController::class);
    Route::resource('permission', PermissionController::class);
    
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema
    |--------------------------------------------------------------------------
    */
    
    // Menus
    Route::resource('menu', MenuController::class)->except(['show']);
    Route::resource('menu.item', MenuItemController::class)->except(['show']);
    
    // Categorias
    Route::prefix('category')->name('category.')->group(function () {
        Route::resource('type', CategoryTypeController::class)->except(['show']);
        Route::resource('type.item', 'CategoryController');
    });
    
    // Mídia
    Route::resource('media', MediaController::class);
    
    /*
    |--------------------------------------------------------------------------
    | Gerenciamento Acadêmico
    |--------------------------------------------------------------------------
    */
    
    // Cursos
    Route::resource('cursos', CursoController::class);
    
    // Diretores
    Route::resource('directors', DirectorController::class);

    // Matrículas
    Route::prefix('matriculas')->group(function () {
        Route::get('/', [MatriculaController::class, 'index'])->name('matriculas.index');
        Route::get('/{id}', [MatriculaController::class, 'show'])->name('matriculas.show');
        Route::patch('/{id}/aprovar', [MatriculaController::class, 'aprovar'])->name('matriculas.aprovar');
        Route::patch('/{id}/rejeitar', [MatriculaController::class, 'rejeitar'])->name('matriculas.rejeitar');
        Route::patch('/{id}/alterar-status', [MatriculaController::class, 'alterarStatus'])->name('matriculas.alterar-status');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Alojamentos
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('alojamento')->group(function () {
        // Listagem e visualização
        Route::get('/', [AlojamentoController::class, 'index'])->name('alojamento.index');
        Route::get('/{alojamento}', [AlojamentoController::class, 'show'])->name('alojamento.show');
        
        // Ações de aprovação/rejeição
        Route::patch('/{alojamento}/aprovar', [AlojamentoController::class, 'aprovar'])->name('alojamento.aprovar');
        Route::patch('/{alojamento}/rejeitar', [AlojamentoController::class, 'rejeitar'])->name('alojamento.rejeitar');

        // Rota para alteração de status
        Route::patch('/{alojamento}/alterar-status', [AlojamentoController::class, 'alterarStatus'])->name('alojamento.alterar-status');
    });
});