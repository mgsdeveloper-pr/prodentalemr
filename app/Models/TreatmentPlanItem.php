<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreatmentPlanItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'treatment_plan_id',
        'service_item_id',
        'clinic_service_id',
        'appointment_id',
        'tooth_number',
        'tooth_surface',
        'description',
        'quantity',
        'unit_fee',
        'estimated_insurance',
        'estimated_patient',
        'line_total',
        'status',
        'target_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_fee' => 'decimal:2',
            'estimated_insurance' => 'decimal:2',
            'estimated_patient' => 'decimal:2',
            'line_total' => 'decimal:2',
            'target_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        $refreshParent = function (self $item): void {
            $item->treatmentPlan?->refreshEstimateSummary();
        };

        static::saved($refreshParent);
        static::deleted($refreshParent);
        static::restored($refreshParent);
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
