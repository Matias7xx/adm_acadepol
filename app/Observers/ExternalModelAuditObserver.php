<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Observer para auditar models de pacotes externos.
 * Registrado no AppServiceProvider para cada model externo.
 *
 * Exemplo de registro:
 *   Role::observe(ExternalModelAuditObserver::class);
 *   Permission::observe(ExternalModelAuditObserver::class);
 *   Menu::observe(ExternalModelAuditObserver::class);
 *   MenuItem::observe(ExternalModelAuditObserver::class);
 */
class ExternalModelAuditObserver
{
  /**
   * Mapeamento classe => [modulo, campo_label]
   */
  private static array $mapa = [
    // Spatie / BalajiDharma
    'Spatie\Permission\Models\Role' => ['role', 'name'],
    'Spatie\Permission\Models\Permission' => ['permission', 'name'],
    'BalajiDharma\LaravelMenu\Models\Menu' => ['menu', 'name'],
    'BalajiDharma\LaravelMenu\Models\MenuItem' => ['menu_item', 'name'],
  ];

  public function created(Model $model): void
  {
    $this->gravar(
      $model,
      'criar',
      [],
      AuditLog::sanitizarDados($model->getAttributes()),
    );
  }

  public function updated(Model $model): void
  {
    $changes = $model->getChanges();
    unset($changes['updated_at']);
    if (empty($changes)) {
      return;
    }

    $before = collect($model->getOriginal())
      ->only(array_keys($changes))
      ->except(AuditLog::CAMPOS_SENSIVEIS)
      ->toArray();
    $after = collect($changes)->except(AuditLog::CAMPOS_SENSIVEIS)->toArray();

    $this->gravar($model, 'editar', $before, $after);
  }

  public function deleted(Model $model): void
  {
    $this->gravar(
      $model,
      'excluir',
      AuditLog::sanitizarDados($model->getAttributes()),
      [],
    );
  }

  private function gravar(
    Model $model,
    string $acao,
    array $antes,
    array $depois,
  ): void {
    try {
      [$modulo, $campoLabel] = $this->resolverMapa($model);
      $user = Auth::user();
      $request = Request::instance();
      $label = $model->{$campoLabel} ?? ($model->id ?? null);

      AuditLog::create([
        'modulo' => $modulo,
        'acao' => $acao,
        'descricao' => ucfirst($acao) . " em {$modulo}: {$label}",
        'model_type' => get_class($model),
        'model_id' => $model->id ?? null,
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
      Log::error('Falha ao registrar auditoria (Observer externo)', [
        'error' => $e->getMessage(),
        'model' => get_class($model),
        'acao' => $acao,
      ]);
    }
  }

  private function resolverMapa(Model $model): array
  {
    $class = get_class($model);
    if (isset(self::$mapa[$class])) {
      return self::$mapa[$class];
    }
    // Fallback: derivar do nome da classe
    $modulo = strtolower(class_basename($model));
    return [$modulo, 'name'];
  }
}
