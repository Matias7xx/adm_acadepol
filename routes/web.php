<?php

use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\DirectorController;
use App\Http\Controllers\AlojamentoController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NoticiaController;
use App\Http\Controllers\ContatoController;
use App\Http\Controllers\RequerimentoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
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

//Rota para upload de imagens na notícia
Route::post('/api/upload-ckeditor-images', [UploadController::class, 'uploadCKEditorImage'])
->middleware(['auth', 'verified']);

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

Route::get('/regimento-interno', function () {
    return Inertia::render('RegimentoInterno');
})->name('regimento.interno');

Route::get('/organograma', function () {
    return Inertia::render('Organograma');
})->name('organograma');

Route::get('/manual-aluno', function () {
    return Inertia::render('ManualAluno');
})->name('manual.aluno');

// Cursos - acesso público
Route::get('/cursos', [CursoController::class, 'cursosPublicos'])
    ->name('cursos');

Route::get('/cursos/{curso}', [CursoController::class, 'showCurso'])
    ->name('detalhes');

    //Rotas públicas para notícias
Route::get('/noticias', [NoticiaController::class, 'ListarTodas'])->name('noticias');
Route::get('/noticias/{id}', [NoticiaController::class, 'exibir'])->name('noticias.exibir');
Route::get('/api/ultimas-noticias', [NoticiaController::class, 'ultimasNoticias'])->name('api.ultimas-noticias');

// API para notícias paginadas com suporte a busca
Route::get('/api/noticias', [NoticiaController::class, 'apiNoticias'])
    ->name('api.noticias');

    // Fale Conosco (rota pública)
Route::controller(ContatoController::class)->prefix('fale-conosco')->name('contato.')->group(function () {
    Route::get('/', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/confirmacao', 'confirmacao')->name('confirmacao');
});

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

    // Requerimentos
    Route::controller(RequerimentoController::class)->prefix('requerimentos')->name('requerimentos.')->group(function () {
    // Criação e confirmação
    Route::get('/novo', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/confirmacao', 'confirmacao')->name('confirmacao');
});
});

// Rotas de autenticação
require __DIR__.'/auth.php';