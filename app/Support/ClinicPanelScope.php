<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ClinicPanelScope
{
    public const SESSION_KEY = 'clinic.selected_clinic_id';

    public static function selectedClinicId(): ?int
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->status) {
            session()->forget(self::SESSION_KEY);

            return null;
        }

        $options = self::clinicOptions();
        $clinicId = $user->shouldBypassClinicScope() ? session(self::SESSION_KEY) : $user->clinic_id;
        if (filled($clinicId) && array_key_exists((int) $clinicId, $options)) {
            return (int) $clinicId;
        }
        session()->forget(self::SESSION_KEY);
        if (count($options) === 1) {
            $clinicId = (int) array_key_first($options);
            session([self::SESSION_KEY => $clinicId]);

            return $clinicId;
        }

        return null;
    }

    public static function selectedClinic(): ?Clinic
    {
        $clinicId = self::selectedClinicId();

        if (! $clinicId) {
            return null;
        }

        return Clinic::query()->with('organization')->find($clinicId);
    }

    public static function initializeFor(User $user): ?Clinic
    {
        return self::selectedClinic();
    }

    public static function selectedOrganizationId(): ?int
    {
        $clinic = self::selectedClinic();

        if ($clinic) {
            return (int) $clinic->organization_id;
        }

        $user = auth()->user();

        return filled($user?->organization_id) ? (int) $user->organization_id : null;
    }

    public static function clinicOptions(): array
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->status) {
            return [];
        }

        if ($user->shouldBypassClinicScope()) {
            return Clinic::query()
                ->where('status', true)
                ->with('organization')
                ->orderBy('clinic_name')
                ->get()
                ->mapWithKeys(fn (Clinic $clinic): array => [
                    $clinic->getKey() => trim($clinic->clinic_name.' - '.($clinic->organization?->name ?? '')),
                ])
                ->all();
        }

        $clinic = Clinic::query()->with('organization')->where('status', true)
            ->where('organization_id', $user->organization_id)->find($user->clinic_id);

        if (! $clinic) {
            return [];
        }

        return [
            $clinic->getKey() => trim($clinic->clinic_name.' - '.($clinic->organization?->name ?? '')),
        ];
    }

    public static function apply(Builder $query, string $column = 'clinic_id'): Builder
    {
        $clinicId = self::selectedClinicId();

        if (! $clinicId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($column, $clinicId);
    }
}
