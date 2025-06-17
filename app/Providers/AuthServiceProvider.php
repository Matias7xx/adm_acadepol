<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\Role' => 'BalajiDharma\LaravelAdminCore\Policies\RolePolicy',
        'App\Models\Permission' => 'BalajiDharma\LaravelAdminCore\Policies\PermissionPolicy',
        'App\Models\User' => 'BalajiDharma\LaravelAdminCore\Policies\UserPolicy',
        'BalajiDharma\LaravelCategory\Models\Category' => 'BalajiDharma\LaravelAdminCore\Policies\CategoryPolicy',
        'BalajiDharma\LaravelCategory\Models\CategoryType' => 'BalajiDharma\LaravelAdminCore\Policies\CategoryTypePolicy',
        'BalajiDharma\LaravelMenu\Models\Menu' => 'BalajiDharma\LaravelAdminCore\Policies\MenuPolicy',
        'BalajiDharma\LaravelMenu\Models\MenuItem' => 'BalajiDharma\LaravelAdminCore\Policies\MenuItemPolicy',
        'Plank\Mediable\Media' => 'BalajiDharma\LaravelAdminCore\Policies\MediaPolicy',
        'App\Models\Matricula' => 'App\Policies\MatriculaPolicy',
        'App\Models\Curso' => 'App\Policies\CursoPolicy',
        'App\Models\Director' => 'App\Policies\DirectorPolicy',
        'App\Models\Alojamento' => 'App\Policies\AlojamentoPolicy',
        'App\Models\Noticia' => 'App\Policies\NoticiaPolicy',
        'App\Models\Contato' => 'App\Policies\ContatoPolicy',
        'App\Models\Visitante' => 'App\Policies\VisitantePolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Implicitly grant "Super-Admin" role all permission checks using can()
        Gate::before(function ($user, $ability) {
            if ($user->hasRole(config('admin.roles.super_admin'))) {
                return true;
            }
        });

        // Configurar autenticação customizada via API
        Auth::provider('api_provider', function ($app, array $config) {
            return new ApiUserProvider($config['model']);
        });
    }
}

/**
 * Provedor de usuário customizado para autenticação via API
 */
class ApiUserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function retrieveById($identifier)
    {
        return $this->model::find($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(\Illuminate\Contracts\Auth\Authenticatable $user, $token)
    {

    }

    public function retrieveByCredentials(array $credentials)
    {
        try {
            // Verificar se as variáveis de ambiente da API estão configuradas
            $apiToken = env('API_TOKEN');
            $apiLoginUrl = env('API_LOGIN_URL');
            
            if (empty($apiToken) || empty($apiLoginUrl)) {
                Log::warning('API não configurada, usando apenas autenticação local', [
                    'api_token_empty' => empty($apiToken),
                    'api_login_url_empty' => empty($apiLoginUrl)
                ]);
                
                // Fallback para banco local
                return $this->model::where('matricula', $credentials['matricula'])->first();
            }
            
            Log::info('Tentativa de autenticação via API', [
                'matricula' => $credentials['matricula'],
                'api_url' => $apiLoginUrl,
                'user_agent' => request()->header('User-Agent'),
                'ip' => request()->ip()
            ]);
            
            // Tenta autenticar via API
            $response = Http::timeout(20) // Timeout
                ->connectTimeout(10) // Timeout de conexão
                ->retry(2, 1000) // Retry 2 vezes com 1 segundo de intervalo
                ->withToken($apiToken)
                ->post($apiLoginUrl . '/api/servidor/login', [
                    'matricula' => $credentials['matricula'],
                    'senha' => $credentials['password']
                ]);
            
            Log::info('Resposta da API recebida', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'size' => strlen($response->body())
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Log do retorno da API
                Log::debug('Dados recebidos da API:', ['userData' => $data]);
                
                // Verificar se o usuário já existe
                $user = $this->model::where('matricula', $data['matricula'])->first();
                
                if (!$user) {
                    // Criar novo usuário simples
                    $user = $this->createSimpleUser($data, $credentials['password']);
                    Log::info('Novo usuário criado: ' . $user->id);
                } else {
                    // Atualizar apenas dados essenciais
                    $user->name = $data['nome'];
                    $user->save();
                    Log::info('Usuário existente atualizado: ' . $user->id);
                }
                
                // Atribuir role de servidor para novos usuários ou verificar role existente
                $this->setupUserRole($user);
                
                return $user;
            } else {
                // Log da falha
                Log::warning('Falha na autenticação com API', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'api_url' => $apiLoginUrl,
                    'headers' => $response->headers()
                ]);
                
                // Fallback para banco local
                $user = $this->model::where('matricula', $credentials['matricula'])->first();
                if ($user && Hash::check($credentials['password'], $user->password)) {
                    Log::info('Autenticação local bem-sucedida para matrícula: ' . $credentials['matricula']);
                    
                    // Atribuir role de servidor mesmo para autenticação local
                    $this->setupUserRole($user);
                    
                    return $user;
                }
                return null;
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Erro de conexão com a API', [
                'message' => $e->getMessage(),
                'api_url' => $apiLoginUrl,
                'curl_error' => $e->getCode()
            ]);
            
            // Fallback para banco local
            $user = $this->model::where('matricula', $credentials['matricula'])->first();
            if ($user && Hash::check($credentials['password'], $user->password)) {
                Log::info('Fallback para autenticação local (erro de conexão) para matrícula: ' . $credentials['matricula']);
                
                // Atribuir role de servidor mesmo para fallback
                $this->setupUserRole($user);
                
                return $user;
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Erro na autenticação: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'api_url' => $apiLoginUrl,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback para banco local
            $user = $this->model::where('matricula', $credentials['matricula'])->first();
            if ($user && Hash::check($credentials['password'], $user->password)) {
                Log::info('Fallback para autenticação local (exceção geral) para matrícula: ' . $credentials['matricula']);
                
                // Atribuir role de servidor mesmo para fallback
                $this->setupUserRole($user);
                
                return $user;
            }
            return null;
        }
    }

    public function validateCredentials(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials)
    {
        // Verificar se é fallback para banco local
        if (isset($credentials['password'])) {
            return Hash::check($credentials['password'], $user->password);
        }
        
        return true;
    }

    public function rehashPasswordIfRequired(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials, bool $force = false)
    {
        // Não implementado - retorna null para não fazer rehash
        return null;
    }

    /**
     * Cria um usuário básico
     */
    private function createSimpleUser($data, $password)
    {
        try {
            $user = new $this->model();
            $user->name = $data['nome'];
            $user->email = $data['email'] ?: $data['matricula'].'@pc.pb.gov.br';
            $user->matricula = $data['matricula'];
            $user->password = Hash::make($password);
            $user->cargo = $data['cargo'] ?? null;
            
            $lotacao = $this->determineLotacao($data);
            
            // Adicionar campos que podem existir no modelo User
            if (in_array('lotacao', Schema::getColumnListing('users'))) {
                $user->lotacao = $lotacao;
            }
            if (in_array('orgao', Schema::getColumnListing('users'))) {
                $user->orgao = 'Polícia Civil da Paraíba';
            }
            if (in_array('cpf', Schema::getColumnListing('users'))) {
                $user->cpf = $data['cpf'] ?? null;
            }
            
            $user->save();
            
            Log::info('Usuário criado com sucesso', [
                'id' => $user->id,
                'matricula' => $user->matricula,
                'lotacao' => $lotacao,
                'orgao' => 'Polícia Civil da Paraíba'
            ]);
            
            return $user;
        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Determina a lotação
     */
    private function determineLotacao($data)
    {
        // Determinar lotação
        $lotacao = "Sem Lotação";
        
        if (isset($data['lotacao_principal']) && 
            is_array($data['lotacao_principal']) && 
            !empty($data['lotacao_principal']['unidade_lotacao'])) {
            $lotacao = trim($data['lotacao_principal']['unidade_lotacao']);
        } elseif (!empty($data['unidade_lotacao'])) {
            $lotacao = trim($data['unidade_lotacao']);
        }
        
        // Normalizar o nome da lotação
        $lotacao = ucwords(mb_strtolower($lotacao));
        
        Log::info('Lotação determinada: ' . $lotacao);
        
        return $lotacao;
    }
    
    /**
     * Configura role do usuário - atribui role "servidor" automaticamente
     */
    private function setupUserRole($user)
    {
        try {
            // Verificar se o usuário já tem alguma role
            if ($user->roles->isEmpty()) {
                // Verificar se a role "servidor" existe
                $servidorRole = \Spatie\Permission\Models\Role::where('name', 'servidor')->first();
                
                if ($servidorRole) {
                    $user->assignRole('servidor');
                    Log::info('Role "servidor" atribuída ao usuário', [
                        'user_id' => $user->id,
                        'matricula' => $user->matricula,
                        'role' => 'servidor'
                    ]);
                } else {
                    Log::warning('Role "servidor" não encontrada no sistema', [
                        'user_id' => $user->id,
                        'matricula' => $user->matricula
                    ]);
                }
            } else {
                Log::info('Usuário já possui roles, não alterando', [
                    'user_id' => $user->id,
                    'matricula' => $user->matricula,
                    'roles' => $user->roles->pluck('name')->toArray()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao configurar role do usuário: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'matricula' => $user->matricula,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}