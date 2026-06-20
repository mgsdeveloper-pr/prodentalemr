<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\SaasEntitlementAuditLog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

class SaasEntitlementAudit
{
    public static function register(): void
    {
        foreach (self::trackedModels() as $modelClass => $fields) {
            $modelClass::updated(function (Model $model) use ($fields): void {
                self::recordIfTrackedFieldsChanged($model, $fields);
            });
        }
    }

    protected static function trackedModels(): array
    {
        return [
            SubscriptionPlan::class => [
                'plan_code',
                'plan_type',
                'workspace_mode',
                'included_modules',
                'included_features',
                'plan_limits',
                'managed_services_allowed',
                'trial_days',
                'demo_mode_available',
                'status',
            ],
            Subscription::class => [
                'subscription_plan_id',
                'previous_subscription_plan_id',
                'change_type',
                'effective_date',
                'renewal_date',
                'cancel_at_period_end',
                'cancelled_at',
                'trial_starts_at',
                'trial_ends_at',
                'is_demo',
                'service_status',
                'service_status_reason',
                'proration_mode',
                'proration_amount',
                'entitlement_overrides',
                'usage_snapshot',
                'account_manager_user_id',
                'status',
            ],
            Organization::class => [
                'status',
                'lifecycle_status',
                'onboarding_status',
                'account_manager_user_id',
                'internal_notes',
            ],
            Clinic::class => [
                'status',
                'verification_services_enabled',
                'clinic_operations_enabled',
                'service_status',
                'pms_service_status',
                'verification_service_status',
                'managed_services_status',
                'trial_ends_at',
                'demo_mode',
                'feature_overrides',
                'usage_snapshot',
                'account_manager_user_id',
                'service_notes',
            ],
            User::class => [
                'default_workspace',
                'allowed_workspaces',
                'feature_overrides',
                'status',
            ],
        ];
    }

    protected static function recordIfTrackedFieldsChanged(Model $model, array $fields): void
    {
        $changedFields = array_values(array_intersect(array_keys($model->getChanges()), $fields));

        if ($changedFields === []) {
            return;
        }

        try {
            SaasEntitlementAuditLog::query()->create([
                ...self::contextFor($model),
                'actor_user_id' => auth()->id(),
                'event_type' => 'entitlement_updated',
                'entity_type' => $model::class,
                'entity_id' => $model->getKey(),
                'before_values' => Arr::only($model->getOriginal(), $changedFields),
                'after_values' => Arr::only($model->getAttributes(), $changedFields),
                'notes' => implode(', ', $changedFields),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (Throwable) {
            // Auditing should never interrupt SaaS administration work.
        }
    }

    protected static function contextFor(Model $model): array
    {
        return match (true) {
            $model instanceof SubscriptionPlan => [
                'subscription_plan_id' => $model->id,
            ],
            $model instanceof Subscription => [
                'organization_id' => $model->organization_id,
                'subscription_id' => $model->id,
                'subscription_plan_id' => $model->subscription_plan_id,
            ],
            $model instanceof Organization => [
                'organization_id' => $model->id,
            ],
            $model instanceof Clinic => [
                'organization_id' => $model->organization_id,
                'clinic_id' => $model->id,
            ],
            $model instanceof User => [
                'organization_id' => $model->organization_id,
                'clinic_id' => $model->clinic_id,
                'target_user_id' => $model->id,
            ],
            default => [],
        };
    }
}
