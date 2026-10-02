<?php

namespace App\Support;

use App\Models\Clinic;

class ClinicNavigation
{
    public const RETURN_KEY = 'clinic.navigation_return';

    public static function destination(?string $target, bool $changingClinic = false): string
    {
        $parts = parse_url($target ?: '/clinic');
        $origin = parse_url(url('/'));
        if (! is_array($parts) || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['host']) && ($parts['host'] !== ($origin['host'] ?? null)
                || ($parts['port'] ?? null) !== ($origin['port'] ?? null)
                || ($parts['scheme'] ?? null) !== ($origin['scheme'] ?? null)))) {
            return url('/clinic');
        }
        $path = $parts['path'] ?? '/clinic';
        if (! preg_match('~^/clinic(?:/[a-z0-9-]+)*$~i', $path)
            || preg_match('~/(?:sign-out|clinic-scope)(?:/|$)~', $path)) return url('/clinic');

        if ($changingClinic) {
            $segments = explode('/', trim($path, '/'));
            $path = '/'.implode('/', array_slice($segments, 0, 2));
        }
        // Never carry record IDs, filters, or nested return URLs to a different clinic.
        return url($path).(! $changingClinic && ! empty($parts['query']) ? '?'.$parts['query'] : '');
    }

    public static function enter(Clinic $clinic, string $target): string
    {
        $slug = explode('/', trim(parse_url($target, PHP_URL_PATH) ?: '', '/'))[1] ?? '';
        $requested = null;
        if (str_starts_with($slug, 'verification') || in_array($slug, ['shared-inbox', 'shared-inbox-settings', 'portal-credentials', 'import-verification-template', 'request-response'], true)) {
            $requested = ClinicWorkspace::VERIFICATION;
        } elseif (in_array($slug, array_map(fn ($module) => str_replace('_', '-', $module), ClinicWorkspace::clinicPmsModules()), true)
            && $slug !== 'appointments') {
            $requested = ClinicWorkspace::CLINIC_PMS;
        }
        $selected = ClinicWorkspace::selectedOrDefault($clinic);
        if ($requested && ClinicWorkspace::canUse($requested, $clinic)) $selected = $requested;
        if (! $selected) return route('clinic.choose-workspace');
        ClinicWorkspace::select($selected);
        if (($requested && $requested !== $selected) || $slug === '') return ClinicWorkspace::homeUrl($selected);
        return $target;
    }
}
