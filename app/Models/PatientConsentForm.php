<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientConsentForm extends Model
{
    use SoftDeletes;

    public const FORM_TYPE_OPTIONS = [
        'hipaa_acknowledgement' => 'HIPAA Acknowledgement',
        'treatment_consent' => 'Treatment Consent',
        'medical_history' => 'Medical History',
        'financial_policy' => 'Financial Policy',
        'photo_consent' => 'Photo Consent',
        'privacy_notice' => 'Privacy Notice',
        'other' => 'Other',
    ];

    public const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'pending_signature' => 'Pending Signature',
        'signed' => 'Signed',
        'declined' => 'Declined',
        'expired' => 'Expired',
        'revoked' => 'Revoked',
    ];

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'location_id',
        'patient_id',
        'provider_id',
        'encounter_id',
        'uploaded_by',
        'form_type',
        'title',
        'status',
        'document_date',
        'signed_on',
        'expires_on',
        'signed_by_name',
        'relationship_to_patient',
        'typed_signature',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'body_text',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'signed_on' => 'date',
            'expires_on' => 'date',
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

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim(($this->patient?->full_name ?? 'Patient') . ' - ' . ($this->title ?: 'Consent Form')),
        );
    }
}
