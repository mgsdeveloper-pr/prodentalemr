<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientInsurancePolicy extends Model
{
    use SoftDeletes;

    public const PRIORITY_OPTIONS = [
        'primary' => 'Primary',
        'secondary' => 'Secondary',
        'tertiary' => 'Tertiary',
    ];

    public const RELATIONSHIP_OPTIONS = [
        'self' => 'Self',
        'spouse' => 'Spouse',
        'child' => 'Child',
        'other' => 'Other',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'created_by',
        'coverage_priority',
        'insurance_company',
        'plan_name',
        'member_id',
        'group_number',
        'subscriber_name',
        'subscriber_relationship',
        'subscriber_dob',
        'subscriber_employer',
        'payer_phone',
        'claims_address',
        'effective_date',
        'termination_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subscriber_dob' => 'date',
            'effective_date' => 'date',
            'termination_date' => 'date',
            'status' => 'boolean',
        ];
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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(PatientInsuranceClaim::class, 'patient_insurance_policy_id');
    }

    public function billingWorkItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class, 'patient_insurance_policy_id');
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->patient?->full_name ?? 'Patient') . ' - ' . ($this->insurance_company ?? 'Insurance')),
        );
    }
}
