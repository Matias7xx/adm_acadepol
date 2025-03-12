<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AuditLoggerService
{
    /**
     * Request atual
     * @var Request
     */
    protected $request;
    
    /**
     * Construtor - injeta a request atual
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * Registra ação do usuário em log de auditoria
     * 
     * @param string $acao Descrição da ação realizada
     * @param string $modulo Módulo do sistema (matricula, curso, alojamento, etc)
     * @param array $dados Dados adicionais para o log
     * @param string $status Status da ação (success, error, warning)
     * @return void
     */
    public function registrarAcao(string $acao, string $modulo, array $dados = [], string $status = 'success'): void
    {
        // Dados básicos do log
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'user_id' => Auth::id() ?? 'guest',
            'user_name' => Auth::user()->name ?? 'guest',
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'url' => $this->request->fullUrl(),
            'method' => $this->request->method(),
            'module' => $modulo,
            'action' => $acao,
            'status' => $status,
        ];
        
        // Mesclar dados adicionais
        $logData = array_merge($logData, $dados);
        
        // Registrar no canal de logs adequado
        switch ($status) {
            case 'error':
                Log::error('Audit Log: ' . $acao, $logData);
                break;
            case 'warning':
                Log::warning('Audit Log: ' . $acao, $logData);
                break;
            case 'success':
            default:
                Log::info('Audit Log: ' . $acao, $logData);
                break;
        }
    }
    
    /**
     * Registra login bem-sucedido
     * 
     * @return void
     */
    public function registrarLogin(): void
    {
        $this->registrarAcao('Login realizado', 'auth');
    }
    
    /**
     * Registra falha de login
     * 
     * @param string $reason Motivo da falha
     * @return void
     */
    public function registrarFalhaLogin(string $reason): void
    {
        $this->registrarAcao('Falha no login', 'auth', ['reason' => $reason], 'warning');
    }
    
    /**
     * Registra logout
     * 
     * @return void
     */
    public function registrarLogout(): void
    {
        $this->registrarAcao('Logout realizado', 'auth');
    }
    
    /**
     * Registra tentativa de acesso não autorizado
     * 
     * @param string $resource Recurso que tentou acessar
     * @return void
     */
    public function registrarAcessoNaoAutorizado(string $resource): void
    {
        $this->registrarAcao('Tentativa de acesso não autorizado', 'auth', ['resource' => $resource], 'warning');
    }
    
    /**
     * Registra ação específica de matrícula
     * 
     * @param string $acao Descrição da ação
     * @param int $matriculaId ID da matrícula
     * @param array $dados Dados adicionais
     * @return void
     */
    public function registrarAcaoMatricula(string $acao, int $matriculaId, array $dados = []): void
    {
        $logData = ['matricula_id' => $matriculaId];
        
        if (!empty($dados)) {
            $logData = array_merge($logData, $dados);
        }
        
        $this->registrarAcao($acao, 'matricula', $logData);
    }
    
    /**
     * Registra ação específica de alojamento
     * 
     * @param string $acao Descrição da ação
     * @param int $alojamentoId ID do alojamento
     * @param array $dados Dados adicionais
     * @return void
     */
    public function registrarAcaoAlojamento(string $acao, int $alojamentoId, array $dados = []): void
    {
        $logData = ['alojamento_id' => $alojamentoId];
        
        if (!empty($dados)) {
            $logData = array_merge($logData, $dados);
        }
        
        $this->registrarAcao($acao, 'alojamento', $logData);
    }
    
    /**
     * Registra ação específica de curso
     * 
     * @param string $acao Descrição da ação
     * @param int $cursoId ID do curso
     * @param array $dados Dados adicionais
     * @return void
     */
    public function registrarAcaoCurso(string $acao, int $cursoId, array $dados = []): void
    {
        $logData = ['curso_id' => $cursoId];
        
        if (!empty($dados)) {
            $logData = array_merge($logData, $dados);
        }
        
        $this->registrarAcao($acao, 'curso', $logData);
    }
    
    /**
     * Registra erro no sistema
     * 
     * @param \Exception $exception Exceção ocorrida
     * @param string $contexto Contexto onde ocorreu o erro
     * @return void
     */
    public function registrarErro(\Exception $exception, string $contexto): void
    {
        $logData = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $contexto
        ];
        
        $this->registrarAcao('Erro no sistema', 'system', $logData, 'error');
    }
}