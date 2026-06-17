<?php

namespace App\Support;

use App\Models\Clinic;

class AppointmentWorkspaceScope
{
    public static function selectedClinicId(): ?int
    {
        return AdminClinicScope::selectedClinicId()
            ?: ClinicPanelScope::selectedClinicId();
    }

    public static function selectedClinic(): ?Clinic
    {
        return AdminClinicScope::selectedClinic()
            ?: ClinicPanelScope::selectedClinic();
    }

    public static function selectedOrganizationId(): ?int
    {
        $clinic = self::selectedClinic();

        if ($clinic) {
            return (int) $clinic->organization_id;
        }

        return ClinicPanelScope::selectedOrganizationId();
    }
}
