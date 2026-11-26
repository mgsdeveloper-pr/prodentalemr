<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\User;

class ClinicWorkspace
{
    public const SESSION_KEY = 'clinic.selected_workspace';

    public const VERIFICATION = 'verification';

    public static function selected(): ?string
    {
        $workspace = session(self::SESSION_KEY);

        return $workspace === self::VERIFICATION
            ? $workspace
            : null;
    }

    public static function select(string $workspace): void
    {
        if ($workspace === self::VERIFICATION) {
            session([self::SESSION_KEY => $workspace]);
        }
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function clinicForUser(?User $user = null): ?Clinic
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        return $user->shouldBypassClinicScope()
            ? ClinicPanelScope::selectedClinic()
            : $user->clinic;
    }

    public static function enabledWorkspaces(?Clinic $clinic): array
    {
        if (! $clinic instanceof Clinic) {
            return [];
        }

        $workspaces = [];

        if ($clinic->hasActiveVerificationServices()) {
            $workspaces[] = self::VERIFICATION;
        }

        return $workspaces;
    }

    public static function needsChoice(?Clinic $clinic): bool
    {
        return count(self::enabledWorkspaces($clinic)) > 1;
    }

    public static function defaultWorkspace(?Clinic $clinic): ?string
    {
        $enabled = self::enabledWorkspaces($clinic);

        return $enabled[0] ?? null;
    }

    public static function selectedOrDefault(?Clinic $clinic): ?string
    {
        $selected = self::selected();
        $enabled = self::enabledWorkspaces($clinic);

        if ($selected && in_array($selected, $enabled, true)) {
            return $selected;
        }

        return self::defaultWorkspace($clinic);
    }

    public static function canUse(string $workspace, ?Clinic $clinic): bool
    {
        return in_array($workspace, self::enabledWorkspaces($clinic), true);
    }

    public static function homeUrl(string $workspace): string
    {
        return match ($workspace) {
            self::VERIFICATION => url('/clinic/verification-requests'),
            default => url('/clinic'),
        };
    }

    public static function loginRedirectFor(User $user): string
    {
        $clinic = self::clinicForUser($user);

        if (self::needsChoice($clinic)) {
            self::clear();

            return route('clinic.choose-workspace');
        }

        $workspace = self::defaultWorkspace($clinic);

        if ($workspace) {
            self::select($workspace);

            return self::homeUrl($workspace);
        }

        return url('/clinic');
    }

    public static function verificationModules(): array
    {
        return [
            'verification_requests',
            'managed_services',
            'template_management',
            'template_publishing',
            'appointments',
            'patients',
            'patient_insurance_policies',
            'insurance_directory',
            'patient_documents',
            'portal_credentials',
            'calling',
        ];
    }

    public static function sharedModules(): array
    {
        return [
            'users',
            'locations',
            'providers',
            'roles_permissions',
        ];
    }
}
