<?php

namespace App\Models;

use App\Traits\HasPublicId;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasPublicId, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'user_id',
        'specialization',
        'license_number',
        'license_state',
        'license_expires_at',
        'npi_number',
        'taxonomy_code',
        'dea_number',
        'tax_id',
        'credentialing_status',
        'credentialing_effective_at',
        'credentialing_expires_at',
        'additional_licenses',
        'business_hours',
        'schedule_exceptions',
        'scheduling_buffer_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tax_id' => 'encrypted',
            'dea_number' => 'encrypted',
            'license_expires_at' => 'date',
            'credentialing_effective_at' => 'date',
            'credentialing_expires_at' => 'date',
            'additional_licenses' => 'array',
            'business_hours' => 'array',
            'schedule_exceptions' => 'array',
            'scheduling_buffer_minutes' => 'integer',
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
