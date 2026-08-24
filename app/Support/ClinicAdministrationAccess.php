<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\User;

class ClinicAdministrationAccess
{
    public static function clinic(): ?Clinic
    {
        return ClinicPanelScope::selectedClinic();
    }

    public static function canView(string $module): bool
    {
        $user = auth()->user();
        $clinic = static::clinic();

        if (! $user instanceof User || ! $user->status || ! $clinic instanceof Clinic) {
            return false;
        }

        if (! $user->shouldBypassClinicScope()
            && ((int) $user->organization_id !== (int) $clinic->organization_id
                || (int) $user->clinic_id !== (int) $clinic->getKey())) {
            return false;
        }

        return $user->canAccessClinicModule($module);
    }

    public static function canMutate(string $module, string $action): bool
    {
        $user = auth()->user();
        $clinic = static::clinic();

        if (! static::canView($module)
            || ! $user instanceof User
            || ! $clinic instanceof Clinic
            || ! $user->canPerformClinicModuleAction($module, $action)) {
            return false;
        }

        if (! $user->shouldBypassClinicScope()) {
            return true;
        }

        return SaasSupportAccess::matchesScope(
            (int) $clinic->organization_id,
            (int) $clinic->getKey(),
        );
    }
}
