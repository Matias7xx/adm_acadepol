<?php

use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

//Portal Acadepol
Route::get('/', function () {
    /* return Inertia::render('Welcome', */ return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::get('/historia', function () {
    return Inertia::render('Historia');
})->name('historia');

Route::get('/missao', function () {
    return Inertia::render('Missao');
})->name('missao');

Route::get('/diretores', function () {
    return Inertia::render('Diretores');
})->name('diretores');

// API para retornar dados dos diretores para o componente CardDiretores
Route::get('/api/directors', [App\Http\Controllers\Admin\DirectorController::class, 'listarDiretores'])->name('api.directors');


Route::get('/estrutura', function () {
    return Inertia::render('Estrutura');
})->name('estrutura');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/cursos', [CursoController::class, 'cursosPublicos'])->name('cursos');

Route::get('/cursos/{curso}', [CursoController::class, 'showCurso'])->name('detalhes'); //Detalhes do curso

//Route::post('/cursos/{curso}/matricular', [CursoController::class, 'matricularAluno'])->middleware('auth'); //Matricular aluno


//ABRIR FORMULÁRIO DE MATRÍCULA
Route::get('/cursos/{curso}/matricula', [MatriculaController::class, 'inscricao'])
->middleware('auth')
->where('curso', '[0-9]+')
->name('matricula'); //Matricular aluno

// Rota para processar a MATRÍCULA (SALVAR)
Route::post('/matricula', [MatriculaController::class, 'store'])
    ->middleware('auth')
    ->name('matricula.store');


//Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
