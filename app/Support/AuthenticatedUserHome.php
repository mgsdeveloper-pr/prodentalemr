<?php

namespace App\Support;

use App\Models\User;
use Filament\PanelRegistry;

class AuthenticatedUserHome
{
    public static function url(User $user): string
    {
        if ($user->shouldLandInVerificationWorkspace() && ! $user->isSaasAdmin()) {
            return url('/verification');
        }

        $panels = app(PanelRegistry::class);

        if ($user->canAccessPanel($panels->get('saas'))) {
            return url('/saas');
        }

        if ($user->canAccessPanel($panels->get('dso'))) {
            return url('/dso');
        }

        if ($user->canAccessPanel($panels->get('admin'))) {
            return url('/verification');
        }

        if ($user->canAccessPanel($panels->get('clinic'))) {
            return ClinicWorkspace::loginRedirectFor($user);
        }

        return route('profile.edit');
    }

    public static function verifiedUrl(User $user): string
    {
        $url = self::url($user);

        return $url.(str_contains($url, '?') ? '&' : '?').'verified=1';
    }
}
