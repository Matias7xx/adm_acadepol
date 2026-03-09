<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Trait Auditable
 *
 * Adicione esta trait em qualquer Model para capturar automaticamente
 * os eventos created, updated e deleted na tabela audit_logs.
 *
 * Configuração no Model:
 *   protected string $auditModulo = 'noticia';   // nome do módulo
 *   protected string $auditLabel  = 'titulo';    // atributo que identifica o registro
 *   protected array  $auditExclude = ['campo'];  // campos a ignorar no diff
 */
trait Auditable
{
  public static function bootAuditable(): void
  {
    static::created(function ($model) {
      $model->registrarAuditoria(
        'criar',
        [],
        AuditLog::sanitizarDados($model->getAttributes()),
      );
    });

    static::updated(function ($model) {
      $changes = $model->getChanges();
      // Remover updated_at do diff pois não agrega valor
      unset($changes['updated_at']);

      if (empty($changes)) {
        return;
      }

      $exclude = $model->auditExclude ?? [];
      $before = collect($model->getOriginal())
        ->only(array_keys($changes))
        ->except($exclude)
        ->except(AuditLog::CAMPOS_SENSIVEIS)
        ->toArray();
      $after = collect($changes)
        ->except($exclude)
        ->except(AuditLog::CAMPOS_SENSIVEIS)
        ->toArray();

      $model->registrarAuditoria('editar', $before, $after);
    });

    static::deleted(function ($model) {
      $model->registrarAuditoria(
        'excluir',
        AuditLog::sanitizarDados($model->getAttributes()),
        [],
      );
    });
  }

  private function registrarAuditoria(
    string $acao,
    array $antes,
    array $depois,
  ): void {
    try {
      $user = Auth::user();
      $modulo = $this->auditModulo ?? $this->getAuditModulo();
      $label = $this->{$this->auditLabel ?? 'id'} ?? ($this->id ?? null);
      $request = Request::instance();

      AuditLog::create([
        'modulo' => $modulo,
        'acao' => $acao,
        'descricao' => $this->gerarDescricaoAudit($acao, $modulo, $label),
        'model_type' => get_class($this),
        'model_id' => $this->id ?? null,
        'model_label' => (string) ($label ?? ''),
        'dados_anteriores' => empty($antes) ? null : $antes,
        'dados_novos' => empty($depois) ? null : $depois,
        'status' => 'success',
        'user_id' => $user?->id,
        'user_name' => $user?->name ?? 'sistema',
        'user_matricula' => $user?->matricula ?? null,
        'user_email' => $user?->email ?? null,
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'url' => $request->fullUrl(),
        'method' => $request->method(),
      ]);
    } catch (\Throwable $e) {
      // Nunca deixar falha de auditoria quebrar a operação principal
      Log::error('Falha ao registrar auditoria via Trait', [
        'error' => $e->getMessage(),
        'model' => get_class($this),
        'model_id' => $this->id ?? null,
        'acao' => $acao,
      ]);
    }
  }

  private function getAuditModulo(): string
  {
    // Derivar módulo do nome da classe: App\Models\NoticiaPost => noticia_post
    $className = class_basename($this);
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
  }

  private function gerarDescricaoAudit(
    string $acao,
    string $modulo,
    mixed $label,
  ): string {
    $moduloLabel = AuditLog::MODULOS[$modulo] ?? ucfirst($modulo);
    $acaoLabel = AuditLog::ACOES[$acao] ?? ucfirst($acao);
    return trim(
      "{$acaoLabel} em {$moduloLabel}" . ($label ? ": {$label}" : ''),
    );
  }
}
