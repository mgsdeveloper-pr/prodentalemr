<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientLedgerEntry extends Model
{
    use SoftDeletes;

    public const ENTRY_TYPE_OPTIONS = [
        'charge' => 'Charge',
        'patient_payment' => 'Patient Payment',
        'insurance_payment' => 'Insurance Payment',
        'adjustment' => 'Adjustment',
        'refund' => 'Refund',
        'write_off' => 'Write-off',
    ];

    public const STATUS_OPTIONS = [
        'posted' => 'Posted',
        'pending' => 'Pending',
        'void' => 'Void',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'provider_id',
        'appointment_id',
        'encounter_id',
        'treatment_plan_id',
        'service_item_id',
        'clinic_service_id',
        'created_by',
        'posted_on',
        'entry_type',
        'status',
        'reference_number',
        'description',
        'quantity',
        'unit_amount',
        'debit_amount',
        'credit_amount',
        'insurance_portion',
        'patient_portion',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'posted_on' => 'date',
            'quantity' => 'decimal:2',
            'unit_amount' => 'decimal:2',
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'insurance_portion' => 'decimal:2',
            'patient_portion' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $entry): void {
            $entry->quantity = (float) ($entry->quantity ?? 1) ?: 1;
            $entry->unit_amount = (float) ($entry->unit_amount ?? 0);
            $entry->debit_amount = max((float) ($entry->debit_amount ?? 0), 0);
            $entry->credit_amount = max((float) ($entry->credit_amount ?? 0), 0);
            $entry->insurance_portion = max((float) ($entry->insurance_portion ?? 0), 0);
            $entry->patient_portion = max((float) ($entry->patient_portion ?? 0), 0);

            if (blank($entry->description)) {
                $entry->description = $entry->clinicService?->name ?: $entry->serviceItem?->name;
            }
        });
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

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class);
    }

    public function clinicService(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function balanceImpact(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round((float) $this->debit_amount - (float) $this->credit_amount, 2),
        );
    }

    protected function balanceImpactLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => sprintf('%s$%0.2f', $this->balance_impact >= 0 ? '+' : '-', abs((float) $this->balance_impact)),
        );
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->patient?->full_name ?? 'Patient') . ' - ' . ($this->reference_number ?: $this->description ?: 'Ledger entry')),
        );
    }
}
