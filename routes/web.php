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
use App\Http\Controllers\CertificadoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\URL;
use App\Helpers\StorageHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

URL::forceScheme(env('HTTP_SCHEMA'));
URL::forceRootUrl(env('APP_URL'));

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

// Rotas para upload do CKEditor
Route::post('/api/upload-ckeditor-images', [UploadController::class, 'uploadCKEditorImage'])
    ->middleware(['web']); // Apenas middleware web para CSRF

Route::post('/api/upload-ckeditor-files', [UploadController::class, 'uploadCKEditorFile'])
    ->middleware(['web']); // Apenas middleware web para CSRF

Route::get('/download', [App\Http\Controllers\DownloadController::class, 'downloadFile'])
    ->name('file.download'); //Download do file na notícia
    
Route::get('/view-file', [App\Http\Controllers\DownloadController::class, 'viewFile'])
    ->name('file.view'); //Visualizar o file da notícia

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

Route::get('/concursos', function () {
    return Inertia::render('Concursos');
})->name('concursos');

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
    /* Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard'); */

     //Rota para servir as imagens do minIO
    Route::get('/foto-usuario/{cpf?}', function($cpf = null) {
        Log::info("=== DEBUG ROTA FOTO ===");
        Log::info("CPF recebido: " . ($cpf ?? 'null'));
        
        if (!Auth::check()) {
            Log::warning("Usuário não autenticado");
            return abort(404);
        }
        
        // Se não passou CPF, usa o do usuário logado
        if (!$cpf) {
            $cpf = str_replace(['.', '-'], '', Auth::user()->cpf ?? '');
            Log::info("CPF do usuário logado: {$cpf}");
        }
        
        $nomeArquivo = "{$cpf}_F.jpg";
        Log::info("Nome do arquivo: {$nomeArquivo}");
        
        try {
            // DEBUG: Configuração do disco
            $diskConfig = config('filesystems.disks.s3');
            Log::info("Configuração do disco:", $diskConfig);
            
            // Usar o disco s3 (bucket funcionais)
            $exists = StorageHelper::fotos()->exists($nomeArquivo);
            Log::info("Arquivo existe: " . ($exists ? 'SIM' : 'NÃO'));
            
            if ($exists) {
                $conteudo = StorageHelper::fotos()->get($nomeArquivo);
                $tamanho = strlen($conteudo);
                Log::info("Arquivo encontrado - Tamanho: {$tamanho} bytes");
                
                return response($conteudo, 200)
                    ->header('Content-Type', 'image/jpg')
                    ->header('Cache-Control', 'public, max-age=3600'); // Cache por 1 hora
            } else {
                // DEBUG: Listar o que tem no bucket
                try {
                    $files = Storage::disk('s3')->allFiles();
                    Log::info("Arquivos no bucket:", $files);
                } catch (\Exception $e) {
                    Log::error("Erro ao listar arquivos: " . $e->getMessage());
                }
                
                Log::warning("Arquivo não encontrado: {$nomeArquivo}");
                return abort(404);
            }
            
        } catch (\Exception $e) {
            Log::error("Erro na rota de foto: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return abort(500);
        }
        
    })->name('foto.usuario');

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

    // Certificados - Acesso autenticado
    Route::controller(CertificadoController::class)->prefix('certificados')->name('certificados.')->group(function () {
        // Listar certificados do usuário logado
        Route::get('/meus', 'meusCertificados')->name('meus');
        
        // DOWNLOAD via MinIO
        Route::get('/{certificado}/download', 'download')->name('download')
            ->where('certificado', '[0-9]+');
        
        // VISUALIZAR via MinIO
        Route::get('/{certificado}/view', 'view')->name('view')
            ->where('certificado', '[0-9]+');
    });
});

// Rotas de autenticação
require __DIR__.'/auth.php';