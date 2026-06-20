<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DsoWorkspaceScope
{
    public const SESSION_KEY = 'dso.selected_clinic_id';

    public static function selectedClinicId(): ?int
    {
        $clinicId = session(self::SESSION_KEY);

        return filled($clinicId) ? (int) $clinicId : null;
    }

    public static function selectedClinic(): ?Clinic
    {
        $clinicId = self::selectedClinicId();

        if (! $clinicId) {
            return null;
        }

        return Cache::remember(
            "dso_workspace_scope.selected_clinic.{$clinicId}",
            now()->addMinutes(5),
            fn (): ?Clinic => Clinic::query()->with('organization')->find($clinicId),
        );
    }

    public static function clinicOptions(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! $user->canAccessDsoWorkspace()) {
            return [];
        }

        return Cache::remember(
            "dso_workspace_scope.clinic_options.{$user->dso_id}",
            now()->addMinutes(5),
            fn (): array => Clinic::query()
                ->with('organization')
                ->whereHas('organization', fn ($query) => $query->where('dso_id', $user->dso_id))
                ->orderBy('clinic_name')
                ->get()
                ->mapWithKeys(fn (Clinic $clinic): array => [
                    $clinic->getKey() => trim($clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '')),
                ])
                ->all(),
        );
    }

    public static function canSelect(?User $user, ?int $clinicId): bool
    {
        if (! $user instanceof User || ! filled($clinicId)) {
            return false;
        }

        return array_key_exists((int) $clinicId, self::clinicOptions($user));
    }
}
