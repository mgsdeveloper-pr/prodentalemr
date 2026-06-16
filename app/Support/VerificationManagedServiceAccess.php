<?php

namespace App\Support;

use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;

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

        if (! Clinic::query()
            ->whereKey($clinicId)
            ->where('verification_services_enabled', true)
            ->exists()) {
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
