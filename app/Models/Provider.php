<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'user_id',
        'specialization',
        'license_number',
        'npi_number',
        'tax_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function billingWorkItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class);
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->user?->name ?? 'Provider #' . $this->getKey(),
        );
    }
}
