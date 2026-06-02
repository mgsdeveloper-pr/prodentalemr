<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'provider_id',
        'appointment_id',
        'created_by',
        'encounter_date',
        'status',
        'chief_complaint',
        'subjective_note',
        'objective_note',
        'assessment_note',
        'plan_note',
        'blood_pressure',
        'heart_rate',
        'temperature',
        'weight',
        'prescriptions',
        'follow_up_instructions',
    ];

    protected function casts(): array
    {
        return [
            'encounter_date' => 'date',
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function dentalChartEntries(): HasMany
    {
        return $this->hasMany(DentalChartEntry::class);
    }

    public function perioCharts(): HasMany
    {
        return $this->hasMany(PerioChart::class);
    }

    public function patientDocuments(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PatientLedgerEntry::class);
    }

    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(PatientInsuranceClaim::class);
    }

    public function consentForms(): HasMany
    {
        return $this->hasMany(PatientConsentForm::class);
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->patient?->full_name ?? 'Patient') . ' · ' . ($this->encounter_date?->format('M d, Y') ?? 'Encounter')),
        );
    }
}
