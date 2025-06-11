<?php

use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\DirectorController;
use App\Http\Controllers\AlojamentoController;
use App\Http\Controllers\VisitanteController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NoticiaController;
use App\Http\Controllers\ContatoController;
use App\Http\Controllers\RequerimentoController;
use App\Http\Controllers\UploadController;
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

Route::post('/api/upload-ckeditor-images', [UploadController::class, 'uploadCKEditorImage'])
    ->middleware(['web']); // Apenas middleware web para CSRF

/*
|--------------------------------------------------------------------------
| API Públicas
|--------------------------------------------------------------------------
*/

// API para diretores
Route::get('/api/directors', [DirectorController::class, 'listarDiretores'])
    ->name('api.directors');

// API para visitantes (busca por CPF)
Route::prefix('api/visitante')->name('api.visitante.')->group(function () {
    Route::post('/buscar-cpf', [VisitanteController::class, 'buscarPorCpf'])->name('buscar.cpf');
});

/*
|--------------------------------------------------------------------------
| Páginas Institucionais
|--------------------------------------------------------------------------
*/

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

Route::get('/regimento-interno', function () {
    return Inertia::render('RegimentoInterno');
})->name('regimento.interno');

Route::get('/organograma', function () {
    return Inertia::render('Organograma');
})->name('organograma');

Route::get('/manual-aluno', function () {
    return Inertia::render('ManualAluno');
})->name('manual.aluno');

/*
|--------------------------------------------------------------------------
| Cursos - Acesso Público
|--------------------------------------------------------------------------
*/

Route::get('/cursos', [CursoController::class, 'cursosPublicos'])
    ->name('cursos');

Route::get('/cursos/{curso}', [CursoController::class, 'showCurso'])
    ->name('detalhes');

/*
|--------------------------------------------------------------------------
| Notícias - Rotas Públicas
|--------------------------------------------------------------------------
*/

Route::get('/noticias', [NoticiaController::class, 'ListarTodas'])->name('noticias');
Route::get('/noticias/{id}', [NoticiaController::class, 'exibir'])->name('noticias.exibir');
Route::get('/api/ultimas-noticias', [NoticiaController::class, 'ultimasNoticias'])->name('api.ultimas-noticias');
Route::get('/api/noticias-home', [NoticiaController::class, 'noticiasHome'])->name('api.noticias-home');

// API para notícias paginadas com suporte a busca
Route::get('/api/noticias', [NoticiaController::class, 'apiNoticias'])
    ->name('api.noticias');

/*
|--------------------------------------------------------------------------
| Fale Conosco - Rota Pública
|--------------------------------------------------------------------------
*/

Route::controller(ContatoController::class)->prefix('fale-conosco')->name('contato.')->group(function () {
    Route::get('/', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/confirmacao', 'confirmacao')->name('confirmacao');
});

/*
|--------------------------------------------------------------------------
| Rotas de Visitantes (Públicas)
|--------------------------------------------------------------------------
*/

// Alojamento - Página intermediária de escolha
Route::get('/alojamento/escolha-tipo', function () {
    return Inertia::render('Components/TipoSolicitante');
})->name('alojamento.escolha.tipo');

Route::controller(VisitanteController::class)->prefix('visitante')->name('visitante.')->group(function () {
    // Formulário de reserva para visitantes
    Route::get('/reserva', 'formularioReserva')->name('formulario');
    Route::post('/reserva', 'store')->name('store');
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

    // Alojamento - Para usuários logados (policiais civis da PB)
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