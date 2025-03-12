<?php

use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\DirectorController;
use App\Http\Controllers\AlojamentoController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Portal Acadepol - Rotas Públicas
|--------------------------------------------------------------------------
*/

// Página inicial
Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// Páginas institucionais
Route::get('/historia', function () {
    return Inertia::render('Historia');
})->name('historia');

Route::get('/missao', function () {
    return Inertia::render('Missao');
})->name('missao');

Route::get('/diretores', function () {
    return Inertia::render('Diretores');
})->name('diretores');

Route::get('/estrutura', function () {
    return Inertia::render('Estrutura');
})->name('estrutura');

// API pública
Route::get('/api/directors', [DirectorController::class, 'listarDiretores'])
    ->name('api.directors');

// Cursos - acesso público
Route::get('/cursos', [CursoController::class, 'cursosPublicos'])
    ->name('cursos');

Route::get('/cursos/{curso}', [CursoController::class, 'showCurso'])
    ->name('detalhes');

/*
|--------------------------------------------------------------------------
| Rotas Autenticadas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Perfil do usuário
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    // Matrículas
    Route::controller(MatriculaController::class)->group(function () {
        Route::get('/cursos/{curso}/matricula', 'inscricao')
            ->where('curso', '[0-9]+')
            ->name('matricula');
        Route::post('/matricula', 'store')
            ->name('matricula.store');
        Route::get('/matricula/confirmacao', 'confirmacao')->name('confirmacao');
    });

    // Alojamento
    Route::controller(AlojamentoController::class)->prefix('alojamento')->name('alojamento.')->group(function () {
        Route::get('/pre-reserva', 'reservaForm')->name('reserva.form');
        Route::post('/pre-reserva', 'store')->name('reserva.store');
        Route::get('/minhas-reservas', 'minhasReservas')->name('minhas-reservas');
        Route::get('/confirmacao', 'confirmacao')->name('confirmacao');
    });
});

// Rotas de autenticação
require __DIR__.'/auth.php';