<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminClinicScope
{
    public const SESSION_KEY = 'admin.selected_clinic_id';

    public static function selectedClinicId(): ?int
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->status) {
            session()->forget(self::SESSION_KEY);

            return null;
        }
        $clinicId = session(self::SESSION_KEY);
        if (filled($clinicId) && $user->canAccessVerificationClinic((int) $clinicId)
            && Clinic::whereKey($clinicId)->where('status', true)->exists()) {
            return (int) $clinicId;
        }
        session()->forget(self::SESSION_KEY);
        $ids = self::accessibleManagedServiceClinicQuery($user)->limit(2)->pluck('clinics.id');
        if ($ids->count() === 1) {
            session([self::SESSION_KEY => (int) $ids->first()]);

            return (int) $ids->first();
        }

        return null;
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

        return Clinic::query()->with('organization')->find($clinicId);
    }

    public static function clinicOptions(): array
    {
        $user = auth()->user();

        return self::accessibleManagedServiceClinicQuery($user)
            ->with('organization')
            ->orderBy('clinic_name')
            ->get()
            ->mapWithKeys(fn (Clinic $clinic): array => [
                $clinic->getKey() => trim($clinic->clinic_name.' - '.($clinic->organization?->name ?? '')),
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
            return $query->whereRaw('1 = 0');
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

    public static function applyVerificationRequests(
        Builder $query,
        string $clinicColumn = 'clinic_id',
        string $assigneeColumn = 'assigned_to'
    ): Builder {
        $query = self::apply($query, $clinicColumn);
        $user = auth()->user();

        if ($user?->hasRole('verification_user') && ! $user->canManageVerificationQueue()) {
            $query->where($assigneeColumn, $user->getAuthIdentifier());
        }

        return $query;
    }

    public static function managedServiceClinicQuery(): Builder
    {
        return Clinic::query()
            ->where('status', true)
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

        if (! $user instanceof User || ! $user->status) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->hasFullVerificationClinicAccess()) {
            return $query;
        }

        $accessibleClinicIds = $user->verificationAccessibleClinicIds();

        if ($accessibleClinicIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('clinics.id', $accessibleClinicIds);
    }
}
