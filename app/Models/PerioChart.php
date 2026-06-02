<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerioChart extends Model
{
    use SoftDeletes;

    public const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'in_progress' => 'In progress',
        'finalized' => 'Finalized',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'provider_id',
        'encounter_id',
        'created_by',
        'chart_date',
        'status',
        'exam_type',
        'plaque_level',
        'bleeding_notes',
        'diagnosis_summary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'chart_date' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PerioChartEntry::class);
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->patient?->full_name ?? 'Patient') . ' · Perio · ' . ($this->chart_date?->format('M d, Y') ?? 'Chart')),
        );
    }
}
