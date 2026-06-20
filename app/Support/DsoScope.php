<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\Dso;
use App\Models\Organization;
use App\Models\User;

class DsoScope
{
    public static function organizationIdsFor(User $user): array
    {
        if (filled($user->dso_id)) {
            return Organization::query()
                ->where('dso_id', $user->dso_id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        if (filled($user->organization_id)) {
            return [(int) $user->organization_id];
        }

        return [];
    }

    public static function clinicIdsFor(User $user): array
    {
        if (filled($user->dso_id)) {
            return Clinic::query()
                ->whereHas('organization', fn ($query) => $query->where('dso_id', $user->dso_id))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        if (filled($user->organization_id)) {
            return Clinic::query()
                ->where('organization_id', $user->organization_id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        if (filled($user->clinic_id)) {
            return [(int) $user->clinic_id];
        }

        return [];
    }

    public static function userIdsFor(User $user): array
    {
        if (filled($user->dso_id)) {
            return User::query()
                ->where('dso_id', $user->dso_id)
                ->orWhereIn('organization_id', self::organizationIdsFor($user))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        if (filled($user->organization_id)) {
            return User::query()
                ->where('organization_id', $user->organization_id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        return filled($user->id) ? [(int) $user->id] : [];
    }

    public static function ownsOrganization(Dso $dso, Organization $organization): bool
    {
        return (int) $organization->dso_id === (int) $dso->getKey();
    }

    public static function ownsClinic(Dso $dso, Clinic $clinic): bool
    {
        return Organization::query()
            ->whereKey($clinic->organization_id)
            ->where('dso_id', $dso->getKey())
            ->exists();
    }
}
