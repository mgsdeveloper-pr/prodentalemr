<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'created_by',
        'pms_patient_id',
        'first_name',
        'last_name',
        'dob',
        'gender',
        'phone',
        'email',
        'address',
        'insurance_provider',
        'insurance_number',
        'guarantor_name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
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

    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(PatientInsurancePolicy::class);
    }

    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(PatientInsuranceClaim::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PatientLedgerEntry::class);
    }

    public function statements(): HasMany
    {
        return $this->hasMany(PatientStatement::class);
    }

    public function consentForms(): HasMany
    {
        return $this->hasMany(PatientConsentForm::class);
    }

    public function billingWorkItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim($this->first_name . ' ' . $this->last_name),
        );
    }

    protected function ageLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->dob?->age ? $this->dob->age . ' yrs' : null,
        );
    }
}
