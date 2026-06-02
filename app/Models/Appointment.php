<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'clinic_operatory_id',
        'patient_id',
        'provider_id',
        'appointment_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'confirmed_at',
        'checked_in_at',
        'seated_at',
        'completed_at',
        'cancelled_at',
        'status',
        'appointment_type',
        'notes',
        'arrival_notes',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'seated_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function operatory(): BelongsTo
    {
        return $this->belongsTo(ClinicOperatory::class, 'clinic_operatory_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PatientLedgerEntry::class);
    }

    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(PatientInsuranceClaim::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function treatmentPlanItems(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class);
    }

    public function billingWorkItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class);
    }
}
