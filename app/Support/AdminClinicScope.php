<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdminClinicScope
{
    public const SESSION_KEY = 'admin.selected_clinic_id';

    public static function selectedClinicId(): ?int
    {
        $clinicId = session(self::SESSION_KEY);

        if (! filled($clinicId)) {
            return null;
        }

        return (int) $clinicId;
    }

    public static function selectedClinic(): ?Clinic
    {
        $clinicId = self::selectedClinicId();

        if (! $clinicId) {
            return null;
        }

        $user = auth()->user();

        if ($user instanceof User && ! $user->hasFullVerificationClinicAccess() && ! $user->canAccessVerificationClinic($clinicId)) {
            return null;
        }

        return Cache::remember(
            "admin_clinic_scope.selected_clinic.{$clinicId}",
            now()->addMinutes(5),
            fn (): ?Clinic => Clinic::query()->with('organization')->find($clinicId),
        );
    }

    public static function clinicOptions(): array
    {
        $user = auth()->user();

        return self::accessibleManagedServiceClinicQuery($user)
            ->with('organization')
            ->orderBy('clinic_name')
            ->get()
            ->mapWithKeys(fn (Clinic $clinic): array => [
                $clinic->getKey() => trim($clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '')),
            ])
            ->all();
    }

    public static function clinics(): Collection
    {
        $user = auth()->user();

        return self::accessibleManagedServiceClinicQuery($user)
            ->with('organization')
            ->orderBy('clinic_name')
            ->get();
    }

    public static function apply(Builder $query, string $column = 'clinic_id'): Builder
    {
        $user = auth()->user();
        $clinicId = self::selectedClinicId();

        if (! $user instanceof User) {
            return $query;
        }

        if ($clinicId && $user->canAccessVerificationClinic($clinicId)) {
            return $query->where($column, $clinicId);
        }

        if ($user->hasFullVerificationClinicAccess()) {
            return $query;
        }

        $accessibleClinicIds = $user->verificationAccessibleClinicIds();

        if ($accessibleClinicIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $accessibleClinicIds);
    }

    public static function managedServiceClinicQuery(): Builder
    {
        return Clinic::query()
            ->where('verification_services_enabled', true)
            ->whereHas('serviceEnrollments', function (Builder $query): void {
                $query
                    ->where('status', 'active')
                    ->whereHas('managedBillingService', function (Builder $serviceQuery): void {
                        $serviceQuery->where('category', 'verification');
                    });
            });
    }

    public static function accessibleManagedServiceClinicQuery(?User $user = null): Builder
    {
        $user ??= auth()->user();
        $query = self::managedServiceClinicQuery();

        if (! $user instanceof User || $user->hasFullVerificationClinicAccess()) {
            return $query;
        }

        $accessibleClinicIds = $user->verificationAccessibleClinicIds();

        if ($accessibleClinicIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('clinics.id', $accessibleClinicIds);
    }
}
