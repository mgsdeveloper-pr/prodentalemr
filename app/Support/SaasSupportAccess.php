<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\SaasEntitlementAuditLog;
use App\Models\User;
use App\Services\Notifications\ProductNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SaasSupportAccess
{
    public const SESSION_KEY = 'saas_support_access';

    public static function active(): ?array
    {
        $context = session(self::SESSION_KEY);

        return is_array($context) ? $context : null;
    }

    public static function activeOrganizationId(): ?int
    {
        $organizationId = data_get(self::active(), 'organization_id');

        return $organizationId ? (int) $organizationId : null;
    }

    public static function activeClinicId(): ?int
    {
        $clinicId = data_get(self::active(), 'clinic_id');

        return $clinicId ? (int) $clinicId : null;
    }

    public static function isActiveForOrganization(Organization $organization): bool
    {
        return self::activeOrganizationId() === $organization->getKey();
    }

    public static function matchesScope(int $organizationId, ?int $clinicId = null): bool
    {
        $activeOrganizationId = self::activeOrganizationId();
        $activeClinicId = self::activeClinicId();

        if (! $activeOrganizationId || $activeOrganizationId !== $organizationId) {
            return false;
        }

        return ! $activeClinicId || ! $clinicId || $activeClinicId === $clinicId;
    }

    public static function matchesProvider(Provider $provider): bool
    {
        return self::matchesScope((int) $provider->organization_id, (int) $provider->clinic_id);
    }

    public static function start(User $actor, Organization $organization, ?Clinic $clinic, string $reason): array
    {
        $context = [
            'session_id' => (string) Str::uuid(),
            'actor_user_id' => $actor->getKey(),
            'organization_id' => $organization->getKey(),
            'organization_name' => $organization->name,
            'clinic_id' => $clinic?->getKey(),
            'clinic_name' => $clinic?->clinic_name,
            'reason' => trim($reason),
            'started_at' => now()->toIso8601String(),
        ];

        session()->put(self::SESSION_KEY, $context);

        self::record('support_access_started', $context);
        app(ProductNotificationService::class)->supportAccess('started', $actor, $context);

        return $context;
    }

    public static function end(?User $actor = null): ?array
    {
        $context = self::active();

        if (! $context) {
            return null;
        }

        self::record('support_access_ended', [
            ...$context,
            'ended_by_user_id' => $actor?->getKey(),
            'ended_at' => now()->toIso8601String(),
        ]);
        $notificationActor = $actor ?? User::query()->find((int) data_get($context, 'actor_user_id'));

        if ($notificationActor instanceof User) {
            app(ProductNotificationService::class)->supportAccess('ended', $notificationActor, $context);
        }

        session()->forget(self::SESSION_KEY);

        return $context;
    }

    protected static function record(string $eventType, array $context): void
    {
        SaasEntitlementAuditLog::query()->create([
            'organization_id' => data_get($context, 'organization_id'),
            'clinic_id' => data_get($context, 'clinic_id'),
            'actor_user_id' => data_get($context, 'actor_user_id', auth()->id()),
            'event_type' => $eventType,
            'entity_type' => Organization::class,
            'entity_id' => data_get($context, 'organization_id'),
            'after_values' => [
                'support_session_id' => data_get($context, 'session_id'),
                'clinic_id' => data_get($context, 'clinic_id'),
                'clinic_name' => data_get($context, 'clinic_name'),
                'started_at' => data_get($context, 'started_at'),
                'ended_at' => data_get($context, 'ended_at'),
                'ended_by_user_id' => data_get($context, 'ended_by_user_id'),
            ],
            'notes' => data_get($context, 'reason'),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public static function recordModelEvent(
        string $eventType,
        Model $model,
        array $beforeValues = [],
        array $afterValues = []
    ): void {
        $context = self::active();

        if (! $context) {
            return;
        }

        SaasEntitlementAuditLog::query()->create([
            'organization_id' => data_get($context, 'organization_id'),
            'clinic_id' => data_get($context, 'clinic_id'),
            'actor_user_id' => data_get($context, 'actor_user_id', auth()->id()),
            'event_type' => $eventType,
            'entity_type' => $model::class,
            'entity_id' => $model->getKey(),
            'before_values' => [
                'record' => $beforeValues,
                'support_session_id' => data_get($context, 'session_id'),
            ],
            'after_values' => [
                'record' => $afterValues,
                'support_session_id' => data_get($context, 'session_id'),
                'support_reason' => data_get($context, 'reason'),
                'support_started_at' => data_get($context, 'started_at'),
            ],
            'notes' => data_get($context, 'reason'),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
