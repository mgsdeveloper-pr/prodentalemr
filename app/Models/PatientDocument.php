<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientDocument extends Model
{
    use SoftDeletes;

    public const TYPE_OPTIONS = [
        'xray' => 'X-ray',
        'photo' => 'Intraoral photo',
        'consent' => 'Consent form',
        'insurance' => 'Insurance document',
        'clinical' => 'Clinical attachment',
        'lab' => 'Lab document',
        'pdf' => 'PDF',
        'other' => 'Other',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'provider_id',
        'encounter_id',
        'uploaded_by',
        'document_type',
        'title',
        'notes',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    protected function fileSizeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match (true) {
                $this->file_size >= 1024 * 1024 => number_format($this->file_size / (1024 * 1024), 2) . ' MB',
                $this->file_size >= 1024 => number_format($this->file_size / 1024, 2) . ' KB',
                default => (string) $this->file_size . ' B',
            },
        );
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->title ?: ($this->original_name ?: 'Patient document'),
        );
    }
}
