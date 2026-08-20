<?php

namespace App\Support;

use App\Models\Clinic;

class ClinicTemplateSettingsSupportAudit
{
    public static function register(): void
    {
        Clinic::updated(function (Clinic $clinic): void {
            if (! self::shouldAudit($clinic)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_clinic_template_settings_updated',
                $clinic,
                ClinicTemplateSettingsSupport::changedBeforeValues($clinic),
                ClinicTemplateSettingsSupport::changedAfterValues($clinic)
            );
        });
    }

    protected static function shouldAudit(Clinic $clinic): bool
    {
        return SaasSupportAccess::matchesScope((int) $clinic->organization_id, (int) $clinic->id)
            && ClinicTemplateSettingsSupport::changedAfterValues($clinic) !== [];
    }
}
