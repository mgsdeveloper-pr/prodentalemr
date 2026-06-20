<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dso_id',
        'organization_id',
        'clinic_id',
        'subscription_scope',
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
        'internal_notes',
        'billing_notes',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'effective_date' => 'date',
            'renewal_date' => 'date',
            'cancel_at_period_end' => 'boolean',
            'cancelled_at' => 'datetime',
            'trial_starts_at' => 'date',
            'trial_ends_at' => 'date',
            'is_demo' => 'boolean',
            'proration_amount' => 'decimal:2',
            'entitlement_overrides' => 'array',
            'usage_snapshot' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function dso(): BelongsTo
    {
        return $this->belongsTo(Dso::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function previousSubscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'previous_subscription_plan_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial'
            || (filled($this->trial_ends_at) && now()->toDateString() <= $this->trial_ends_at->toDateString());
    }

    public function isServiceActive(): bool
    {
        return in_array($this->service_status, ['active', 'trial'], true)
            && ! in_array($this->status, ['cancelled', 'expired'], true);
    }

    public function usageValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->usage_snapshot ?? [], $key, $default);
    }

    public function entitlementOverride(string $key, mixed $default = null): mixed
    {
        return data_get($this->entitlement_overrides ?? [], $key, $default);
    }
}
