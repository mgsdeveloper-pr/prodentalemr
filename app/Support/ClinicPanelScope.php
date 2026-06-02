<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ClinicPanelScope
{
    public const SESSION_KEY = 'clinic.selected_clinic_id';

    public static function selectedClinicId(): ?int
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        if (! $user->shouldBypassClinicScope()) {
            return filled($user->clinic_id) ? (int) $user->clinic_id : null;
        }

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

        return Cache::remember(
            "clinic_panel_scope.selected_clinic.{$clinicId}",
            now()->addMinutes(5),
            fn (): ?Clinic => Clinic::query()->with('organization')->find($clinicId),
        );
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

        if (! $user instanceof User) {
            return [];
        }

        if ($user->shouldBypassClinicScope()) {
            return Cache::remember(
                'clinic_panel_scope.master_clinic_options',
                now()->addMinutes(5),
                fn (): array => Clinic::query()
                    ->with('organization')
                    ->orderBy('clinic_name')
                    ->get()
                    ->mapWithKeys(fn (Clinic $clinic): array => [
                        $clinic->getKey() => trim($clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '')),
                    ])
                    ->all(),
            );
        }

        $clinic = $user->clinic?->loadMissing('organization');

        if (! $clinic) {
            return [];
        }

        return [
            $clinic->getKey() => trim($clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '')),
        ];
    }

    public static function apply(Builder $query, string $column = 'clinic_id'): Builder
    {
        $clinicId = self::selectedClinicId();

        if (! $clinicId) {
            return $query;
        }

        return $query->where($column, $clinicId);
    }
}
