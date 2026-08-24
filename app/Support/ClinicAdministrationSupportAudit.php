<?php

namespace App\Support;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ClinicAdministrationSupportAudit
{
    public static function register(): void
    {
        static::registerModel(Location::class, 'location', [
            'clinic_id', 'location_name', 'address', 'city', 'state', 'zip_code', 'country', 'phone', 'status',
        ]);

        // Passwords, remember tokens, and verification tokens are intentionally excluded.
        static::registerModel(User::class, 'clinic_user', [
            'organization_id', 'clinic_id', 'location_id', 'name', 'email', 'phone', 'status',
        ]);
    }

    protected static function registerModel(string $modelClass, string $eventPrefix, array $auditedFields): void
    {
        $modelClass::created(function (Model $model) use ($eventPrefix, $auditedFields): void {
            if (! static::matches($model)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                "support_{$eventPrefix}_created",
                $model,
                [],
                Arr::only($model->getAttributes(), $auditedFields),
            );
        });

        $modelClass::updated(function (Model $model) use ($eventPrefix, $auditedFields): void {
            if (! static::matches($model)) {
                return;
            }

            $changedFields = array_values(array_intersect(array_keys($model->getChanges()), $auditedFields));

            if ($changedFields === []) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                "support_{$eventPrefix}_updated",
                $model,
                Arr::only($model->getOriginal(), $changedFields),
                Arr::only($model->getAttributes(), $changedFields),
            );
        });

        $modelClass::deleted(function (Model $model) use ($eventPrefix, $auditedFields): void {
            if (! static::matches($model)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                "support_{$eventPrefix}_archived",
                $model,
                Arr::only($model->getOriginal(), $auditedFields),
                ['deleted_at' => optional($model->deleted_at)->toIso8601String()],
            );
        });

        $modelClass::restored(function (Model $model) use ($eventPrefix): void {
            if (! static::matches($model)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                "support_{$eventPrefix}_restored",
                $model,
                ['deleted_at' => $model->getOriginal('deleted_at')],
                ['deleted_at' => null],
            );
        });
    }

    protected static function matches(Model $model): bool
    {
        $organizationId = (int) ($model->organization_id ?? $model->clinic?->organization_id);
        $clinicId = (int) ($model->clinic_id ?? 0);

        return $organizationId > 0
            && $clinicId > 0
            && SaasSupportAccess::matchesScope($organizationId, $clinicId);
    }
}
