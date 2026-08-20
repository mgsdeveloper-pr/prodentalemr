<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;

class UserPolicy
{
    use HandlesEnterpriseAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canUseSaas($user, 'users')
            || $this->canUseVerification($user, 'users')
            || $this->canUseClinic($user, 'users');
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user) && $this->canManageTarget($user, $target);
    }

    public function create(User $user): bool
    {
        return $this->canUseSaas($user, 'users', 'add')
            || $this->canUseVerification($user, 'users', 'add')
            || $this->canUseClinic($user, 'users', 'add');
    }

    public function update(User $user, User $target): bool
    {
        return (
            $this->canUseSaas($user, 'users', 'update')
            || $this->canUseVerification($user, 'users', 'update')
            || $this->canUseClinic($user, 'users', 'update')
        ) && $this->canManageTarget($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return (
            $this->canUseSaas($user, 'users', 'delete')
            || $this->canUseVerification($user, 'users', 'delete')
            || $this->canUseClinic($user, 'users', 'delete')
        ) && ! $target->hasRole('saas_admin') && $this->canManageTarget($user, $target);
    }

    protected function canManageTarget(User $user, User $target): bool
    {
        if ($user->isSaasAdmin()) {
            return true;
        }

        if ($user->canAccessVerificationWorkspace()) {
            return $target->hasAnyRole(array_keys(User::verificationRoleOptions()));
        }

        return $this->withinClinicTenant($user, $target);
    }
}
