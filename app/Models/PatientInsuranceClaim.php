<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientInsuranceClaim extends Model
{
    use SoftDeletes;

    public const CLAIM_TYPE_OPTIONS = [
        'claim' => 'Claim',
        'preauth' => 'Pre-authorization',
    ];

    public const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'ready' => 'Ready',
        'submitted' => 'Submitted',
        'pending' => 'Pending',
        'paid' => 'Paid',
        'partially_paid' => 'Partially Paid',
        'denied' => 'Denied',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'patient_insurance_policy_id',
        'provider_id',
        'appointment_id',
        'encounter_id',
        'treatment_plan_id',
        'created_by',
        'claim_number',
        'claim_type',
        'claim_date',
        'service_date',
        'submitted_at',
        'status',
        'preauth_number',
        'payer_reference',
        'billed_amount',
        'estimated_coverage',
        'insurance_paid',
        'patient_responsibility',
        'procedure_summary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'service_date' => 'date',
            'submitted_at' => 'date',
            'billed_amount' => 'decimal:2',
            'estimated_coverage' => 'decimal:2',
            'insurance_paid' => 'decimal:2',
            'patient_responsibility' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $claim): void {
            if (blank($claim->claim_number)) {
                $claim->claim_number = self::generateClaimNumber();
            }
        });

        static::saving(function (self $claim): void {
            $claim->billed_amount = max((float) ($claim->billed_amount ?? 0), 0);
            $claim->estimated_coverage = min(max((float) ($claim->estimated_coverage ?? 0), 0), $claim->billed_amount);
            $claim->insurance_paid = min(max((float) ($claim->insurance_paid ?? 0), 0), $claim->billed_amount);
            $claim->patient_responsibility = max($claim->billed_amount - $claim->estimated_coverage, 0);
        });
    }

    public static function generateClaimNumber(): string
    {
        $prefix = 'CLM-' . now()->format('Ym') . '-';

        $latest = static::withTrashed()
            ->where('claim_number', 'like', "{$prefix}%")
            ->latest('id')
            ->value('claim_number');

        $sequence = 1;

        if ($latest && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
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

    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(PatientInsurancePolicy::class, 'patient_insurance_policy_id');
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(PatientInsuranceClaimLineItem::class);
    }

    public function billingWorkItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class, 'patient_insurance_claim_id');
    }

    public function refreshFinancialSummary(): void
    {
        if (! $this->exists) {
            return;
        }

        $lineItems = $this->lineItems()->get([
            'description',
            'tooth_number',
            'tooth_surface',
            'billed_amount',
            'estimated_coverage',
            'insurance_paid',
            'patient_responsibility',
        ]);

        $this->billed_amount = round((float) $lineItems->sum('billed_amount'), 2);
        $this->estimated_coverage = round(min((float) $lineItems->sum('estimated_coverage'), (float) $this->billed_amount), 2);
        $this->insurance_paid = round(min((float) $lineItems->sum('insurance_paid'), (float) $this->billed_amount), 2);
        $this->patient_responsibility = round(max((float) $lineItems->sum('patient_responsibility'), 0), 2);
        $this->procedure_summary = $lineItems
            ->map(function (PatientInsuranceClaimLineItem $item): string {
                $tooth = filled($item->tooth_number) ? "Tooth {$item->tooth_number}" : null;
                $surface = filled($item->tooth_surface) ? $item->tooth_surface : null;

                return collect([$item->description, $tooth, $surface])
                    ->filter()
                    ->implode(' - ');
            })
            ->filter()
            ->implode("\n");

        $this->saveQuietly();
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->patient?->full_name ?? 'Patient') . ' - ' . ($this->claim_number ?? 'Claim')),
        );
    }
}
