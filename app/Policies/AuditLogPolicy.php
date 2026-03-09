<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
{
  use HandlesAuthorization;

  /**
   * Apenas super-admin e admin podem visualizar os logs de auditoria.
   */
  public function adminViewAny(User $user): bool
  {
    return $user->hasAnyRole(['super-admin', 'admin']);
  }
}
