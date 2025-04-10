<?php

use App\Http\Controllers\Admin\CategoryTypeController;
use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\DirectorController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NoticiaController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlojamentoController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\ContatoController;
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
        Route::get('/', [MatriculaController::class, 'index'])->name('matriculas.index'); //Todas as matrículas em todos os cursos (fazer o count futuramente)
        Route::get('/curso/{curso}', [MatriculaController::class, 'index'])->name('matriculas.curso');
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

        // Rotas para gerar a ficha de hospedagem (sem breadcrumbs)
        Route::get('/{alojamento}/ficha', [AlojamentoController::class, 'gerarFichaHospedagem'])
        ->name('alojamento.ficha')
        ->withoutMiddleware([HandleInertiaAdminRequests::class]); // Desabilita o middleware que gera breadcrumbs

        Route::get('/{alojamento}/ficha/visualizar', [AlojamentoController::class, 'visualizarFichaHospedagem'])
        ->name('alojamento.ficha.visualizar')
        ->withoutMiddleware([HandleInertiaAdminRequests::class]); // Desabilita o middleware que gera breadcrumbs
    });

     /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Notícias
    |--------------------------------------------------------------------------
    */
    Route::resource('noticias', NoticiaController::class);
    Route::patch('noticias/{noticia}/toggle-destaque', [NoticiaController::class, 'toggleDestaque'])
    ->name('noticias.toggle-destaque');


    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Mensagens de Contato
    |--------------------------------------------------------------------------
    */
    Route::prefix('contato')->name('contato.')->group(function () {
        Route::get('/', [ContatoController::class, 'index'])->name('index');
        Route::get('/{contato}', [ContatoController::class, 'show'])->name('show');
        Route::post('/{contato}/responder', [ContatoController::class, 'responder'])->name('responder');
        Route::patch('/{contato}/arquivar', [ContatoController::class, 'arquivar'])->name('arquivar');
        Route::patch('/{contato}/retornar-pendente', [ContatoController::class, 'retornarParaPendente'])->name('retornar-pendente');
        Route::patch('/{contato}/alterar-status', [ContatoController::class, 'alterarStatus'])->name('alterar-status');
        Route::delete('/{contato}', [ContatoController::class, 'destroy'])->name('destroy');
    });

});