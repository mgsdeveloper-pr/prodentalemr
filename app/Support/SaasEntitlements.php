<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;

class SaasEntitlements
{
    /**
     * Core clinic capabilities are available on every plan. Feature-level
     * enhancements (such as appointment import) remain separately gated.
     */
    private const CORE_CLINIC_MODULES = [
        'appointments',
        'locations',
    ];

    public static function currentSubscriptionFor(?Organization $organization): ?Subscription
    {
        if (! $organization) {
            return null;
        }

        $organizationId = (int) $organization->getKey();
        $requestKey = "prodental.entitlements.current_subscription.{$organizationId}";

        if (app()->bound('request') && request()->attributes->has($requestKey)) {
            return request()->attributes->get($requestKey);
        }

        $subscription = $organization->subscriptions()
            ->with('subscriptionPlan')
            ->whereIn('status', ['active', 'trial'])
            ->whereIn('service_status', ['active', 'trial', 'pending_setup'])
            ->latest('start_date')
            ->latest('id')
            ->first();

        if (app()->bound('request')) {
            request()->attributes->set($requestKey, $subscription);
        }

        return $subscription;
    }

    public static function planForClinic(?Clinic $clinic): ?SubscriptionPlan
    {
        return self::currentSubscriptionFor($clinic?->organization)?->subscriptionPlan;
    }

    public static function planForUser(?User $user): ?SubscriptionPlan
    {
        return self::currentSubscriptionFor($user?->organization)?->subscriptionPlan;
    }

    public static function clinicModuleAllowed(?Clinic $clinic, string $module): bool
    {
        if (in_array($module, self::CORE_CLINIC_MODULES, true)) {
            return true;
        }

        if ($module === 'template_management') {
            return self::clinicModuleAllowed($clinic, 'verification_requests');
        }

        $plan = self::planForClinic($clinic);

        if (! $plan) {
            return true;
        }

        return in_array($module, $plan->included_modules ?? [], true);
    }

    public static function userFeatureAllowed(?User $user, string $feature, ?Clinic $clinic = null): bool
    {
        if ($user?->featureOverride($feature) === true || $clinic?->featureOverride($feature) === true) {
            return true;
        }

        if ($user?->featureOverride($feature) === false || $clinic?->featureOverride($feature) === false) {
            return false;
        }

        $plan = $clinic ? self::planForClinic($clinic) : self::planForUser($user);

        if (! $plan) {
            return true;
        }

        return $plan->allowsFeature($feature);
    }

    public static function limitFor(?Clinic $clinic, string $key, mixed $default = null): mixed
    {
        return self::planForClinic($clinic)?->limitValue($key, $default) ?? $default;
    }

    public static function workspacesForPlan(?SubscriptionPlan $plan): array
    {
        if (! $plan) {
            return [];
        }

        return array_values(array_filter([
            $plan->includesVerification() ? 'verification' : null,
            $plan->includesPms() ? 'clinic' : null,
        ]));
    }

    public static function defaultWorkspaceForPlan(?SubscriptionPlan $plan): ?string
    {
        if (! $plan) {
            return null;
        }

        return match ($plan->workspace_mode) {
            SubscriptionPlan::WORKSPACE_VERIFICATION => 'verification',
            SubscriptionPlan::WORKSPACE_PMS => 'clinic',
            default => count(self::workspacesForPlan($plan)) === 1
                ? self::workspacesForPlan($plan)[0]
                : null,
        };
    }
}
