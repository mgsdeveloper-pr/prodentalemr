<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManagedBillingService extends Model
{
    use SoftDeletes;

    public const CATEGORY_OPTIONS = [
        'verification' => 'Verification',
        'coding' => 'Coding',
        'claims' => 'Claims',
        'ar' => 'AR Follow-up',
        'payment_posting' => 'Payment Posting',
        'credentialing' => 'Credentialing',
        'analysis' => 'Analysis',
        'integration' => 'PMS Integration',
    ];

    public const PRIORITY_OPTIONS = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'service_level_agreement_hours',
        'default_priority',
        'requires_appointment',
        'requires_patient',
        'requires_policy',
        'requires_claim',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'service_level_agreement_hours' => 'integer',
            'requires_appointment' => 'boolean',
            'requires_patient' => 'boolean',
            'requires_policy' => 'boolean',
            'requires_claim' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClientServiceEnrollment::class);
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class);
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->name,
        );
    }
}
