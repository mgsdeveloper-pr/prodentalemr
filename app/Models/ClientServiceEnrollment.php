<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientServiceEnrollment extends Model
{
    use SoftDeletes;

    public const STATUS_OPTIONS = [
        'requested' => 'Requested',
        'active' => 'Active',
        'paused' => 'Paused',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'managed_billing_service_id',
        'created_by',
        'status',
        'clinic_workspace_enabled',
        'normal_sla_days',
        'urgent_sla_hours',
        'start_date',
        'end_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'clinic_workspace_enabled' => 'boolean',
            'normal_sla_days' => 'integer',
            'urgent_sla_hours' => 'integer',
        ];
    }

    public function calculateDueAt(string $priority = 'normal'): \Illuminate\Support\Carbon
    {
        $now = now();

        if ($priority === 'urgent') {
            return $now->copy()->addHours(max(1, (int) ($this->urgent_sla_hours ?: 24)));
        }

        return $now->copy()->addDays(max(1, (int) ($this->normal_sla_days ?: 3)));
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function managedBillingService(): BelongsTo
    {
        return $this->belongsTo(ManagedBillingService::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class);
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $parts = [
                    $this->organization?->name,
                    $this->clinic?->clinic_name,
                    $this->managedBillingService?->name,
                ];

                return collect($parts)->filter()->implode(' - ');
            },
        );
    }

    protected function slaSummary(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Normal T+' . (int) ($this->normal_sla_days ?: 3) . ' days / Urgent +' . (int) ($this->urgent_sla_hours ?: 24) . ' hrs',
        );
    }
}
