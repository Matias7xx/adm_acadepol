<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;
use Illuminate\Support\Facades\Route;
use App\Helpers\BreadcrumbsHelper;

// admin dashboard
Breadcrumbs::for('admin.dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('admin.dashboard'));
});

// Breadcrumbs para a página inicial
Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('home'));
});

// Breadcrumbs para cursos (frontend)
Breadcrumbs::for('cursos', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Cursos', route('cursos'));
});

// Breadcrumbs para detalhe de curso (frontend)
Breadcrumbs::for('detalhes', function (BreadcrumbTrail $trail, $curso) {
    $trail->parent('cursos');
    $trail->push(BreadcrumbsHelper::getTitle($curso, 'Curso'), route('detalhes', $curso));
});

Breadcrumbs::macro('resource', function (string $name, string $title, ?string $parentName = null) {
    if ($parentName) {
        Breadcrumbs::for("{$name}.index", function (BreadcrumbTrail $trail, $model) use ($name, $title, $parentName) {
            $trail->parent("{$parentName}.show", $model);
            $trail->push($title, route("{$name}.index", $model));
        });

        Breadcrumbs::for("{$name}.create", function (BreadcrumbTrail $trail, $model) use ($name) {
            $trail->parent("{$name}.index", $model);
            $trail->push('Criar', route("{$name}.create", $model));
        });
    
        Breadcrumbs::for("{$name}.show", function (BreadcrumbTrail $trail, $model, $item) use ($name) {
            $trail->parent("{$name}.index", $model, $item);
            
            if (Route::has("{$name}.show")) {
                // Usa o helper para obter um título amigável
                $itemTitle = BreadcrumbsHelper::getTitle($item, 'Item');
                $trail->push($itemTitle, route("{$name}.show", [$model, $item]));
            } else {
                $trail->push(BreadcrumbsHelper::getTitle($model, 'Item'));
            }
        });
    
        Breadcrumbs::for("{$name}.edit", function (BreadcrumbTrail $trail, $model, $item) use ($name) {
            $trail->parent("{$name}.show", $model, $item);
            $trail->push('Editar', route("{$name}.edit", [$model, $item]));
        });
        
    } else {
        Breadcrumbs::for("{$name}.index", function (BreadcrumbTrail $trail) use ($name, $title) {
            $trail->parent('admin.dashboard');
            $trail->push($title, route("{$name}.index"));
        });

        Breadcrumbs::for("{$name}.create", function (BreadcrumbTrail $trail) use ($name) {
            $trail->parent("{$name}.index");
            $trail->push('Criar', route("{$name}.create"));
        });
    
        Breadcrumbs::for("{$name}.show", function (BreadcrumbTrail $trail, $model) use ($name) {
            $trail->parent("{$name}.index");
            
            if (Route::has("{$name}.show")) {
                // Usa o helper para obter um título amigável
                $modelTitle = BreadcrumbsHelper::getTitle($model, 'Item');
                $trail->push($modelTitle, route("{$name}.show", $model));
            } else {
                $trail->push(BreadcrumbsHelper::getTitle($model, 'Item'));
            }
        });
    
        Breadcrumbs::for("{$name}.edit", function (BreadcrumbTrail $trail, $model) use ($name) {
            $trail->parent("{$name}.show", $model);
            $trail->push('Editar', route("{$name}.edit", $model));
        });
    }
});

// Definição de recursos
Breadcrumbs::resource('admin.permission', 'Permissões');
Breadcrumbs::resource('admin.role', 'Funções');
Breadcrumbs::resource('admin.user', 'Usuários');
Breadcrumbs::resource('admin.media', 'Mídia');
Breadcrumbs::resource('admin.menu', 'Menu');
Breadcrumbs::resource('admin.menu.item', 'Itens de Menu', 'admin.menu');
Breadcrumbs::resource('admin.category.type', 'Tipos de Categoria');
Breadcrumbs::resource('admin.category.type.item', 'Itens', 'admin.category.type');
Breadcrumbs::resource('admin.cursos', 'Cursos');
Breadcrumbs::resource('admin.matriculas', 'Matrículas');

// admin account Info
Breadcrumbs::for('admin.account.info', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.dashboard');
    $trail->push('Informações da Conta', route('admin.account.info'));
});