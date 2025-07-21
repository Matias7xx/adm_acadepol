<?php

use App\Http\Controllers\Admin\CursoController;
use App\Http\Controllers\Admin\DirectorController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NoticiaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlojamentoController;
use App\Http\Controllers\MatriculaRelatorioController;
use App\Http\Controllers\VisitanteController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\RequerimentoController;
use App\Http\Controllers\ContatoController;
use App\Http\Middleware\Admin\HandleInertiaAdminRequests;
use App\Http\Middleware\HasAccessAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\OcupacaoController;
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
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

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

        // Rotas para gerenciar certificados do usuário
        Route::post('user/{user}/certificados', 'adicionarCertificado')->name('certificados.adicionar.usuario');
        Route::delete('user/{user}/certificados/{certificado}', 'removerCertificado')->name('certificados.remover.usuario');
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
        Route::get('/curso/{curso}', [MatriculaController::class, 'index'])->name('matriculas.curso');
        Route::get('/{id}', [MatriculaController::class, 'show'])->name('matriculas.show');
        Route::patch('/{id}/aprovar', [MatriculaController::class, 'aprovar'])->name('matriculas.aprovar');
        Route::patch('/{id}/rejeitar', [MatriculaController::class, 'rejeitar'])->name('matriculas.rejeitar');
        Route::patch('/{id}/alterar-status', [MatriculaController::class, 'alterarStatus'])->name('matriculas.alterar-status');
        
        Route::get('/relatorio/{curso}/pdf', [MatriculaRelatorioController::class, 'gerarRelatorioPDF'])->name('matriculas.relatorio.pdf');
        Route::get('/relatorio/{curso}/excel', [MatriculaRelatorioController::class, 'gerarRelatorioExcel'])->name('matriculas.relatorio.excel');
    });

    // Certificados - Administrador
    Route::prefix('certificados')->name('certificados.')->group(function () {
        
        // GERAR CERTIFICADO
        Route::post('/gerar/{matricula}', [CertificadoController::class, 'gerar'])
            ->name('gerar')
            ->where('matricula', '[0-9]+');
        
        // CRIAR CERTIFICADO EXTERNO (cursos fora do sistema)
        Route::post('/externo', [CertificadoController::class, 'criarCertificadoExterno'])
            ->name('externo.criar');
        
        // EXCLUIR CERTIFICADO
        Route::delete('/{certificado}', [CertificadoController::class, 'excluir'])
            ->name('excluir')
            ->where('certificado', '[0-9]+'); 
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Alojamentos
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('alojamento')->group(function () {
        // Listagem e visualização
        Route::get('/', [AlojamentoController::class, 'index'])->name('alojamento.index');
        
        // Rota para visualizar reservas de qualquer tipo
        Route::get('/{tipo}/{id}', [AlojamentoController::class, 'showReserva'])
            ->where(['tipo' => 'usuario|visitante', 'id' => '[0-9]+'])
            ->name('alojamento.show.reserva');
        
        // Visualização de reservas de usuário
        Route::get('/{alojamento}', [AlojamentoController::class, 'show'])->name('alojamento.show');
        
        // Ações de aprovação/rejeição
        Route::patch('/{alojamento}/aprovar', [AlojamentoController::class, 'aprovar'])->name('alojamento.aprovar');
        Route::patch('/{alojamento}/rejeitar', [AlojamentoController::class, 'rejeitar'])->name('alojamento.rejeitar');
        Route::patch('/{alojamento}/alterar-status', [AlojamentoController::class, 'alterarStatus'])->name('alojamento.alterar-status');

        // Ficha de hospedagem para usuários
        Route::get('/{alojamento}/ficha', [AlojamentoController::class, 'gerarFichaHospedagem'])
            ->name('alojamento.ficha')
            ->withoutMiddleware([HandleInertiaAdminRequests::class]);

        // Adicionar rotas de check-in/check-out para reservas individuais
        Route::post('/{alojamento}/checkin', [AlojamentoController::class, 'checkin'])->name('alojamento.checkin');
        Route::post('/{alojamento}/checkout', [AlojamentoController::class, 'checkout'])->name('alojamento.checkout');
    });

    Route::prefix('visitante')->group(function () {
        // Visualização
        Route::get('/{visitante}', [VisitanteController::class, 'show'])->name('visitante.show');
        
        // Rota para alteração de status
        Route::patch('/{visitante}/alterar-status', [VisitanteController::class, 'alterarStatus'])->name('visitante.alterar-status');

        // Rotas para gerar a ficha de hospedagem
        Route::get('/{visitante}/ficha', [VisitanteController::class, 'gerarFichaHospedagem'])
            ->name('visitante.ficha')
            ->withoutMiddleware([HandleInertiaAdminRequests::class]);

        Route::get('/{visitante}/ficha/visualizar', [VisitanteController::class, 'visualizarFichaHospedagem'])
            ->name('visitante.ficha.visualizar')
            ->withoutMiddleware([HandleInertiaAdminRequests::class]);

        // Adicionar rotas de check-in/check-out para visitantes
        Route::post('/{visitante}/checkin', [VisitanteController::class, 'checkin'])->name('visitante.checkin');
        Route::post('/{visitante}/checkout', [VisitanteController::class, 'checkout'])->name('visitante.checkout');
    });

    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Visitantes
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('visitantes')->name('visitantes.')->group(function () {
        // Listagem e visualização
        Route::get('/', [VisitanteController::class, 'index'])->name('index');
        Route::get('/{visitante}', [VisitanteController::class, 'show'])->name('show');
        
        // Ações de aprovação/rejeição
        Route::patch('/{visitante}/aprovar', [VisitanteController::class, 'aprovar'])->name('aprovar');
        Route::patch('/{visitante}/rejeitar', [VisitanteController::class, 'rejeitar'])->name('rejeitar');
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
    | Gerenciamento de Requerimentos
    |--------------------------------------------------------------------------
    */
    Route::prefix('requerimentos')->group(function () {
        // Listagem e visualização
        Route::get('/', [RequerimentoController::class, 'index'])->name('requerimentos.index');
        Route::get('/{requerimento}', [RequerimentoController::class, 'show'])->name('requerimentos.show');
        
        // Ações de aprovação/rejeição
        Route::post('/{requerimento}/aprovar', [RequerimentoController::class, 'deferir'])->name('requerimentos.deferir');
        Route::post('/{requerimento}/rejeitar', [RequerimentoController::class, 'indeferir'])->name('requerimentos.indeferir');

        // Alteração de status
        Route::patch('/{requerimento}/alterar-status', [RequerimentoController::class, 'alterarStatus'])->name('requerimentos.alterar-status');
    });

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

    /*
    |--------------------------------------------------------------------------
    | Gerenciamento de Ocupação de Dormitórios
    |--------------------------------------------------------------------------
    */
    Route::prefix('ocupacao')->name('ocupacao.')->group(function () {
        // Dashboard principal de ocupação
        Route::get('/', [OcupacaoController::class, 'index'])->name('index');
        
        // Buscar reservas para check-in rápido
        Route::get('/buscar-reservas', [OcupacaoController::class, 'buscarReservas'])->name('buscar-reservas');
        
        // Funcionalidades de check-in e check-out
        Route::get('/modal-checkin', [OcupacaoController::class, 'modalCheckin'])->name('modal-checkin');
        Route::post('/checkin', [OcupacaoController::class, 'checkin'])->name('checkin');
        Route::post('/{ocupacao}/checkout', [OcupacaoController::class, 'checkout'])->name('checkout');
        
        // Informações
        Route::get('/dormitorios-disponiveis', [OcupacaoController::class, 'dormitoriosDisponiveis'])->name('dormitorios-disponiveis');
        Route::get('/dormitorio/{dormitorio}/detalhes', [OcupacaoController::class, 'dormitorioDetalhes'])->name('dormitorio-detalhes');
        
        // API para atualização em tempo real
        Route::get('/api', [OcupacaoController::class, 'apiIndex'])->name('api.index');
    });

});