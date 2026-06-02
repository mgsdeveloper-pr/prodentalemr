<?php

namespace App\Support;

use App\Models\ClientServiceEnrollment;

class VerificationManagedServiceAccess
{
    public static function selectedClinicHasActiveVerificationService(): bool
    {
        return self::clinicHasActiveVerificationService(
            ClinicPanelScope::selectedClinicId(),
            ClinicPanelScope::selectedOrganizationId(),
        );
    }

    public static function clinicHasActiveVerificationService(?int $clinicId, ?int $organizationId): bool
    {
        if (! $clinicId || ! $organizationId) {
            return false;
        }

        return ClientServiceEnrollment::query()
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where('status', 'active')
            ->whereHas('managedBillingService', fn ($query) => $query->where('category', 'verification'))
            ->exists();
    }
}
