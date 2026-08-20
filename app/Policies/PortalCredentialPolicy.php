<?php

namespace App\Policies;

use App\Models\PortalCredential;
use App\Models\User;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;

class PortalCredentialPolicy
{
    use HandlesEnterpriseAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canUseVerification($user, 'portal_credentials')
            || $this->canUseSaas($user, 'portal_credentials')
            || $this->canUseClinic($user, 'portal_credentials');
    }

    public function view(User $user, PortalCredential $credential): bool
    {
        if ($this->canUseVerification($user, 'portal_credentials') || $this->canUseSaas($user, 'portal_credentials')) {
            return true;
        }

        return $this->canUseClinic($user, 'portal_credentials') && $this->withinClinicTenant($user, $credential);
    }

    public function create(User $user): bool
    {
        return $this->canUseVerification($user, 'portal_credentials', 'add')
            || $this->canUseSaas($user, 'portal_credentials', 'add')
            || $this->canUseClinic($user, 'portal_credentials', 'add');
    }

    public function update(User $user, PortalCredential $credential): bool
    {
        return (
            $this->canUseVerification($user, 'portal_credentials', 'update')
            || $this->canUseSaas($user, 'portal_credentials', 'update')
            || $this->canUseClinic($user, 'portal_credentials', 'update')
        ) && $this->view($user, $credential);
    }

    public function delete(User $user, PortalCredential $credential): bool
    {
        return (
            $this->canUseVerification($user, 'portal_credentials', 'delete')
            || $this->canUseSaas($user, 'portal_credentials', 'delete')
            || $this->canUseClinic($user, 'portal_credentials', 'delete')
        ) && $this->view($user, $credential);
    }
}
