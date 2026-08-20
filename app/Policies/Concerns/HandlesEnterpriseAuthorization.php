<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HandlesEnterpriseAuthorization
{
    protected function canUseSaas(User $user, string $module, string $action = 'view'): bool
    {
        return $action === 'view'
            ? $user->canAccessSaasModule($module)
            : $user->canPerformSaasModuleAction($module, $action);
    }

    protected function canUseVerification(User $user, string $module, string $action = 'view'): bool
    {
        return $action === 'view'
            ? $user->canAccessVerificationModule($module)
            : $user->canPerformVerificationModuleAction($module, $action);
    }

    protected function canUseClinic(User $user, string $module, string $action = 'view'): bool
    {
        return $action === 'view'
            ? $user->canAccessClinicModule($module)
            : $user->canPerformClinicModuleAction($module, $action);
    }

    protected function ownsOrganization(User $user, Model $record): bool
    {
        $organizationId = $record->getAttribute('organization_id');

        return $organizationId === null
            || (int) $organizationId === (int) $user->organization_id;
    }

    protected function ownsClinic(User $user, Model $record): bool
    {
        $clinicId = $record->getAttribute('clinic_id');

        return $clinicId === null
            || (int) $clinicId === (int) $user->clinic_id;
    }

    protected function withinClinicTenant(User $user, Model $record): bool
    {
        if ($user->shouldBypassClinicScope()) {
            return true;
        }

        return $this->ownsOrganization($user, $record) && $this->ownsClinic($user, $record);
    }
}
