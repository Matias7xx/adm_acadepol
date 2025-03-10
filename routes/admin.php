<?php

use App\Http\Middleware\HasAccessAdmin;
use App\Http\Middleware\Admin\HandleInertiaAdminRequests;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MatriculaController;


Route::group([
    'namespace' => 'App\Http\Controllers\Admin',
    'prefix' => config('admin.prefix'),
    'middleware' => ['auth', HasAccessAdmin::class, HandleInertiaAdminRequests::class],
    'as' => 'admin.',
], function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');    
    Route::resource('user', 'UserController');
    Route::resource('role', 'RoleController');
    Route::resource('permission', 'PermissionController');
    Route::resource('menu', 'MenuController')->except([
        'show',
    ]);
    Route::resource('menu.item', 'MenuItemController')->except([
        'show',
    ]);
    Route::group([
        'prefix' => 'category',
        'as' => 'category.',
    ], function () {
        Route::resource('type', 'CategoryTypeController')->except([
            'show',
        ]);
        Route::resource('type.item', 'CategoryController');
    });
    Route::resource('media', 'MediaController');
    Route::get('edit-account-info', 'UserController@accountInfo')->name('account.info');
    Route::post('edit-account-info', 'UserController@accountInfoStore')->name('account.info.store');
    Route::post('change-password', 'UserController@changePasswordStore')->name('account.password.store');

    Route::resource('cursos', 'CursoController');

    Route::resource('directors', 'DirectorController');

    //Aqui é onde o ADMIN analisa as matrículas
    Route::middleware(['auth', HasAccessAdmin::class])->group(function () {
        Route::get('/matriculas', [MatriculaController::class, 'index'])->name('matriculas.index');
        Route::get('/admin/matriculas/{id}', [MatriculaController::class, 'show'])->name('matriculas.show');
        Route::patch('/admin/matriculas/{id}/aprovar', [MatriculaController::class, 'aprovar'])->name('matriculas.aprovar');
        Route::patch('/admin/matriculas/{id}/rejeitar', [MatriculaController::class, 'rejeitar'])->name('matriculas.rejeitar');
        Route::patch('/admin/matriculas/{id}/alterar-status', [MatriculaController::class, 'alterarStatus'])
        ->name('matriculas.alterar-status');
    });
    
});
