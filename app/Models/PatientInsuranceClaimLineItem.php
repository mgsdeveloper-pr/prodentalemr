<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientInsuranceClaimLineItem extends Model
{
    use SoftDeletes;

    public const STATUS_OPTIONS = [
        'ready' => 'Ready',
        'submitted' => 'Submitted',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'denied' => 'Denied',
    ];

    protected $fillable = [
        'patient_insurance_claim_id',
        'treatment_plan_item_id',
        'service_item_id',
        'clinic_service_id',
        'procedure_code',
        'description',
        'tooth_number',
        'tooth_surface',
        'quantity',
        'unit_fee',
        'billed_amount',
        'estimated_coverage',
        'insurance_paid',
        'patient_responsibility',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_fee' => 'decimal:2',
            'billed_amount' => 'decimal:2',
            'estimated_coverage' => 'decimal:2',
            'insurance_paid' => 'decimal:2',
            'patient_responsibility' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->quantity = max((float) ($item->quantity ?? 1), 0.01);
            $item->unit_fee = max((float) ($item->unit_fee ?? 0), 0);
            $item->billed_amount = round($item->quantity * $item->unit_fee, 2);
            $item->estimated_coverage = min(max((float) ($item->estimated_coverage ?? 0), 0), $item->billed_amount);
            $item->insurance_paid = min(max((float) ($item->insurance_paid ?? 0), 0), $item->billed_amount);
            $item->patient_responsibility = round(max($item->billed_amount - $item->estimated_coverage, 0), 2);
        });

        $refreshClaim = function (self $item): void {
            $item->claim?->refreshFinancialSummary();
        };

        static::saved($refreshClaim);
        static::deleted($refreshClaim);
        static::restored($refreshClaim);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(PatientInsuranceClaim::class, 'patient_insurance_claim_id');
    }

    public function treatmentPlanItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class);
    }

    public function serviceItem(): BelongsTo
    {
        return $this->belongsTo(ServiceItem::class);
    }

    public function clinicService(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class);
    }
}
