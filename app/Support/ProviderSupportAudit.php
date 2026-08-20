<?php

namespace App\Support;

use App\Models\Provider;
use Illuminate\Support\Arr;

class ProviderSupportAudit
{
    public static function register(): void
    {
        Provider::created(function (Provider $provider): void {
            if (! SaasSupportAccess::matchesProvider($provider)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_provider_created',
                $provider,
                [],
                Arr::only($provider->getAttributes(), self::auditedFields())
            );
        });

        Provider::updated(function (Provider $provider): void {
            if (! SaasSupportAccess::matchesProvider($provider)) {
                return;
            }

            $changedFields = array_values(array_intersect(array_keys($provider->getChanges()), self::auditedFields()));

            if ($changedFields === []) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_provider_updated',
                $provider,
                Arr::only($provider->getOriginal(), $changedFields),
                Arr::only($provider->getAttributes(), $changedFields)
            );
        });

        Provider::deleted(function (Provider $provider): void {
            if (! SaasSupportAccess::matchesProvider($provider)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_provider_deleted',
                $provider,
                Arr::only($provider->getOriginal(), self::auditedFields()),
                ['deleted_at' => optional($provider->deleted_at)->toIso8601String()]
            );
        });

        Provider::restored(function (Provider $provider): void {
            if (! SaasSupportAccess::matchesProvider($provider)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_provider_restored',
                $provider,
                ['deleted_at' => $provider->getOriginal('deleted_at')],
                ['deleted_at' => null]
            );
        });
    }

    protected static function auditedFields(): array
    {
        return [
            'organization_id',
            'clinic_id',
            'location_id',
            'user_id',
            'specialization',
            'license_number',
            'npi_number',
            'tax_id',
            'status',
        ];
    }
}
