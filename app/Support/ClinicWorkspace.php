<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\User;

class ClinicWorkspace
{
    public const SESSION_KEY = 'clinic.selected_workspace';
    public const VERIFICATION = 'verification';
    public const CLINIC_PMS = 'clinic_pms';

    public static function selected(): ?string
    {
        $workspace = session(self::SESSION_KEY);

        return in_array($workspace, [self::VERIFICATION, self::CLINIC_PMS], true)
            ? $workspace
            : null;
    }

    public static function select(string $workspace): void
    {
        if (in_array($workspace, [self::VERIFICATION, self::CLINIC_PMS], true)) {
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

        if ($clinic->hasActiveClinicOperations()) {
            $workspaces[] = self::CLINIC_PMS;
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
            self::CLINIC_PMS => url('/clinic'),
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

    public static function moduleMatchesSelectedWorkspace(string $module, ?Clinic $clinic): bool
    {
        if (! self::needsChoice($clinic)) {
            return true;
        }

        $selected = self::selected();

        if ($selected === self::VERIFICATION) {
            return in_array($module, self::verificationModules(), true)
                || in_array($module, self::sharedModules(), true);
        }

        if ($selected === self::CLINIC_PMS) {
            return in_array($module, self::clinicPmsModules(), true)
                || in_array($module, self::sharedModules(), true);
        }

        return true;
    }

    public static function verificationModules(): array
    {
        return [
            'verification_requests',
            'appointments',
            'portal_credentials',
        ];
    }

    public static function clinicPmsModules(): array
    {
        return [
            'patients',
            'providers',
            'appointments',
            'encounters',
            'treatment_plans',
            'clinic_services',
            'dental_chart_entries',
            'perio_charts',
            'patient_documents',
            'patient_insurance_policies',
            'patient_ledger_entries',
            'patient_insurance_claims',
            'patient_statements',
            'clinic_operatories',
            'patient_consent_forms',
        ];
    }

    public static function sharedModules(): array
    {
        return [
            'users',
            'roles_permissions',
        ];
    }
}
