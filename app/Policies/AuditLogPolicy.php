<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessSaasRevenueOperations()
            || $user->canAccessSaasModule('roles_permissions');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
