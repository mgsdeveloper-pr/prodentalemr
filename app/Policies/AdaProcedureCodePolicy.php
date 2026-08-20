<?php

namespace App\Policies;

use App\Models\AdaProcedureCode;
use App\Models\User;

class AdaProcedureCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessSaasModule('settings');
    }

    public function view(User $user, AdaProcedureCode $code): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canPerformSaasModuleAction('settings', 'update');
    }

    public function update(User $user, AdaProcedureCode $code): bool
    {
        return $user->canPerformSaasModuleAction('settings', 'update');
    }

    public function delete(User $user, AdaProcedureCode $code): bool
    {
        return false;
    }

    public function restore(User $user, AdaProcedureCode $code): bool
    {
        return false;
    }

    public function forceDelete(User $user, AdaProcedureCode $code): bool
    {
        return false;
    }
}
