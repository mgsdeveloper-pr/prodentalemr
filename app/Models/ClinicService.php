<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'service_code',
        'name',
        'description',
        'category',
        'default_fee',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'default_fee' => 'decimal:2',
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

    public function treatmentPlanItems(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class);
    }

    public function dentalChartEntries(): HasMany
    {
        return $this->hasMany(DentalChartEntry::class);
    }

    public function patientLedgerEntries(): HasMany
    {
        return $this->hasMany(PatientLedgerEntry::class);
    }

    public function insuranceClaimLineItems(): HasMany
    {
        return $this->hasMany(PatientInsuranceClaimLineItem::class);
    }
}
