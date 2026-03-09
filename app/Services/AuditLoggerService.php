<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AuditLoggerService
{
  protected Request $request;

  public function __construct(Request $request)
  {
    $this->request = $request;
  }

  /**
   * Registra uma ação no banco de dados e no arquivo de log.
   */
  public function registrarAcao(
    string $acao,
    string $modulo,
    array $dados = [],
    string $status = 'success',
    ?string $descricao = null,
  ): ?AuditLog {
    $user = Auth::user();

    // Montar payload base
    $payload = [
      'modulo' => $modulo,
      'acao' => $acao,
      'descricao' =>
        $descricao ?? $this->gerarDescricao($acao, $modulo, $dados),
      'status' => $status,
      'user_id' => $user?->id,
      'user_name' => $user?->name ?? 'guest',
      'user_matricula' => $user?->matricula ?? null,
      'user_email' => $user?->email ?? null,
      'ip' => $this->request->ip(),
      'user_agent' => $this->request->userAgent(),
      'url' => $this->request->fullUrl(),
      'method' => $this->request->method(),
    ];

    // Extrair campos especiais de $dados
    if (isset($dados['model_type'])) {
      $payload['model_type'] = $dados['model_type'];
      unset($dados['model_type']);
    }
    if (isset($dados['model_id'])) {
      $payload['model_id'] = $dados['model_id'];
      unset($dados['model_id']);
    }
    if (isset($dados['model_label'])) {
      $payload['model_label'] = $dados['model_label'];
      unset($dados['model_label']);
    }
    if (isset($dados['dados_anteriores'])) {
      $payload['dados_anteriores'] = AuditLog::sanitizarDados(
        $dados['dados_anteriores'],
      );
      unset($dados['dados_anteriores']);
    }
    if (isset($dados['dados_novos'])) {
      $payload['dados_novos'] = AuditLog::sanitizarDados($dados['dados_novos']);
      unset($dados['dados_novos']);
    }

    // Dados extras vão para a descrição / log file
    $logData = array_merge($payload, $dados);

    // 1. Gravar no banco
    try {
      $auditLog = AuditLog::create($payload);
    } catch (\Throwable $e) {
      Log::error('Falha ao gravar AuditLog no banco', [
        'error' => $e->getMessage(),
        'payload' => $payload,
      ]);
      $auditLog = null;
    }

    // 2. Gravar no arquivo de log (canal audit)
    $this->gravarArquivoLog($status, $acao, $logData);

    return $auditLog;
  }

  // =========================================================================
  // ATALHOS POR EVENTO
  // =========================================================================

  public function registrarLogin(): void
  {
    $this->registrarAcao(
      'login',
      'auth',
      [],
      'success',
      'Login realizado com sucesso',
    );
  }

  public function registrarFalhaLogin(string $reason = ''): void
  {
    $this->registrarAcao(
      'falha_login',
      'auth',
      ['motivo' => $reason],
      'warning',
      'Tentativa de login falhou',
    );
  }

  public function registrarLogout(): void
  {
    $this->registrarAcao('logout', 'auth', [], 'success', 'Logout realizado');
  }

  public function registrarAcessoNaoAutorizado(string $resource = ''): void
  {
    $this->registrarAcao(
      'acesso_negado',
      'auth',
      ['recurso' => $resource],
      'warning',
      'Tentativa de acesso não autorizado',
    );
  }

  /**
   * Atalho para registrar CRUD de qualquer módulo de forma padronizada.
   */
  public function registrarCrud(
    string $acao,
    string $modulo,
    $model,
    ?string $label = null,
    array $dadosExtras = [],
    array $dadosAnteriores = [],
    array $dadosNovos = [],
  ): ?AuditLog {
    $dados = [
      'model_type' => get_class($model),
      'model_id' => $model->id ?? null,
      'model_label' =>
        $label ??
        ($model->nome ??
          ($model->titulo ?? ($model->name ?? (string) ($model->id ?? '')))),
      'dados_anteriores' => $dadosAnteriores,
      'dados_novos' => $dadosNovos,
    ];

    return $this->registrarAcao(
      $acao,
      $modulo,
      array_merge($dados, $dadosExtras),
    );
  }

  public function registrarErro(
    \Throwable $exception,
    string $contexto = '',
  ): void {
    $this->registrarAcao(
      'erro',
      'sistema',
      [
        'exception' => get_class($exception),
        'mensagem' => $exception->getMessage(),
        'arquivo' => $exception->getFile(),
        'linha' => $exception->getLine(),
        'contexto' => $contexto,
      ],
      'error',
      'Erro no sistema: ' . $exception->getMessage(),
    );
  }

  // =========================================================================
  // INTERNOS
  // =========================================================================

  private function gravarArquivoLog(
    string $status,
    string $acao,
    array $dados,
  ): void {
    $mensagem = "Audit [{$acao}]";

    match ($status) {
      'error' => Log::channel('audit')->error($mensagem, $dados),
      'warning' => Log::channel('audit')->warning($mensagem, $dados),
      default => Log::channel('audit')->info($mensagem, $dados),
    };
  }

  private function gerarDescricao(
    string $acao,
    string $modulo,
    array $dados,
  ): string {
    $moduloLabel = AuditLog::MODULOS[$modulo] ?? ucfirst($modulo);
    $acaoLabel = AuditLog::ACOES[$acao] ?? ucfirst($acao);
    $label = $dados['model_label'] ?? '';

    return trim(
      "{$acaoLabel} em {$moduloLabel}" . ($label ? ": {$label}" : ''),
    );
  }
}
