<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreatmentPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'provider_id',
        'appointment_id',
        'encounter_id',
        'created_by',
        'plan_number',
        'title',
        'plan_date',
        'status',
        'phase',
        'priority',
        'notes',
        'acceptance_notes',
        'accepted_at',
        'estimated_total',
        'estimated_insurance',
        'estimated_patient',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'accepted_at' => 'date',
            'estimated_total' => 'decimal:2',
            'estimated_insurance' => 'decimal:2',
            'estimated_patient' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $plan): void {
            if (blank($plan->plan_number)) {
                $plan->plan_number = self::generatePlanNumber();
            }
        });
    }

    public static function generatePlanNumber(): string
    {
        $prefix = 'TP-' . now()->format('Ym') . '-';

        $latest = static::withTrashed()
            ->where('plan_number', 'like', "{$prefix}%")
            ->latest('id')
            ->value('plan_number');

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class);
    }

    public function unscheduledItems(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class)
            ->whereNull('appointment_id')
            ->whereIn('status', ['accepted', 'scheduled', 'proposed']);
    }

    public function dentalChartEntries(): HasMany
    {
        return $this->hasMany(DentalChartEntry::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PatientLedgerEntry::class);
    }

    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(PatientInsuranceClaim::class);
    }

    public function refreshEstimateSummary(): void
    {
        $estimatedTotal = (float) $this->items()->sum('line_total');
        $estimatedInsurance = (float) $this->items()->sum('estimated_insurance');
        $estimatedPatient = (float) $this->items()->sum('estimated_patient');

        $this->estimated_total = $estimatedTotal;
        $this->estimated_insurance = min($estimatedInsurance, $estimatedTotal);
        $this->estimated_patient = max($estimatedPatient, 0);

        $this->saveQuietly();
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->patient?->full_name ?? 'Patient') . ' · ' . ($this->plan_number ?? 'Treatment plan')),
        );
    }
}
