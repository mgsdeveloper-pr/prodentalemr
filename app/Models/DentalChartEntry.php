<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DentalChartEntry extends Model
{
    use SoftDeletes;

    public const CHART_TYPE_OPTIONS = [
        'condition' => 'Condition',
        'existing' => 'Existing work',
        'planned' => 'Planned treatment',
        'completed' => 'Completed treatment',
    ];

    public const STATUS_OPTIONS = [
        'active' => 'Active',
        'watch' => 'Watch',
        'planned' => 'Planned',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ];

    public const CONDITION_CODE_OPTIONS = [
        'caries' => 'Caries',
        'missing' => 'Missing',
        'filling' => 'Filling',
        'crown' => 'Crown',
        'bridge' => 'Bridge',
        'implant' => 'Implant',
        'root_canal' => 'Root canal',
        'fracture' => 'Fracture',
        'sealant' => 'Sealant',
        'veneer' => 'Veneer',
        'extraction' => 'Extraction',
        'perio_concern' => 'Perio concern',
        'monitor' => 'Monitor',
        'other' => 'Other',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'provider_id',
        'encounter_id',
        'treatment_plan_id',
        'service_item_id',
        'clinic_service_id',
        'created_by',
        'recorded_on',
        'tooth_number',
        'tooth_surface',
        'chart_type',
        'condition_code',
        'status',
        'description',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
        ];
    }

    public static function permanentToothOptions(): array
    {
        return collect(range(1, 32))
            ->mapWithKeys(fn (int $number): array => [(string) $number => (string) $number])
            ->all();
    }

    public static function primaryToothOptions(): array
    {
        return collect(range('A', 'T'))
            ->mapWithKeys(fn (string $letter): array => [$letter => $letter])
            ->all();
    }

    public static function toothOptions(): array
    {
        return [
            'Permanent' => self::permanentToothOptions(),
            'Primary' => self::primaryToothOptions(),
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

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim('Tooth ' . ($this->tooth_number ?: '?') . ' · ' . ($this->patient?->full_name ?? 'Patient')),
        );
    }
}
