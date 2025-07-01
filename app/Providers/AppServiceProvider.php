<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DataSanitizerService;
use App\Services\AuditLoggerService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use App\Models\Noticia;
use App\Observers\NoticiaObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */

     protected $policies = [
        'App\Models\Matricula' => 'App\Policies\MatriculaPolicy',
        'App\Models\Curso' => 'App\Policies\CursoPolicy',
    ];

    public function register()
    {
        // Registrar serviço de sanitização de dados
        $this->app->singleton(DataSanitizerService::class, function ($app) {
            return new DataSanitizerService();
        });

        // Registrar serviço de log de auditoria
        $this->app->singleton(AuditLoggerService::class, function ($app) {
            return new AuditLoggerService($app->make(Request::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
         // Força HTTPS em ambiente de produção
         if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Configurar Paginator para usar Tailwind CSS
        Paginator::useBootstrap();
        
        // Adicionar validação personalizada para CPF
        Validator::extend('cpf', function ($attribute, $value, $parameters, $validator) {
            return $this->validateCpf($value);
        }, 'O :attribute informado não é válido.');
        
        // Adicionar validação personalizada para matrícula
        Validator::extend('matricula', function ($attribute, $value, $parameters, $validator) {
            // Valida se a matrícula tem pelo menos 7 caracteres e contém apenas números
            return preg_match('/^[0-9]{7,}$/', $value);
        }, 'A :attribute deve ter pelo menos 7 caracteres e conter apenas números.');

        // Registrar o Observer para Notícia
        Noticia::observe(NoticiaObserver::class);
    }
    
    /**
     * Valida CPF
     *
     * @param string $cpf
     * @return bool
     */
    private function validateCpf($cpf)
    {
        // Extrai somente os números
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);
        
        // Verifica se foi informado todos os dígitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se foi informada uma sequência de dígitos repetidos
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Faz o cálculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
}
