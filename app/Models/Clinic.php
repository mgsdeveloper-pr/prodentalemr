<?php

namespace App\Models;

use App\Models\VerificationFormQuestion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'clinic_name',
        'clinic_code',
        'timezone',
        'status',
        'verification_pdf_output_mode',
        'verification_pdf_output_sections',
        'verification_pdf_output_question_ids',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'verification_pdf_output_sections' => 'array',
            'verification_pdf_output_question_ids' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function verificationUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'verification_clinic_user')
            ->withTimestamps();
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function serviceEnrollments(): HasMany
    {
        return $this->hasMany(ClientServiceEnrollment::class);
    }

    public function insuranceCarrierOverrides(): HasMany
    {
        return $this->hasMany(ClinicInsuranceCarrierOverride::class);
    }

    public function billingWorkItems(): HasMany
    {
        return $this->hasMany(BillingWorkItem::class);
    }

    public function getVerificationPdfOutputMode(): string
    {
        $mode = (string) ($this->verification_pdf_output_mode ?: 'standard');

        return array_key_exists($mode, \App\Support\VerificationResultPdf::OUTPUT_MODE_OPTIONS)
            ? $mode
            : 'standard';
    }

    public function getVerificationPdfOutputSections(): array
    {
        $sections = is_array($this->verification_pdf_output_sections)
            ? $this->verification_pdf_output_sections
            : [];

        return array_values(array_filter(
            $sections,
            fn ($section): bool => is_string($section)
                && array_key_exists($section, VerificationFormQuestion::SECTION_OPTIONS)
        ));
    }

    public function getVerificationPdfOutputQuestionIds(): array
    {
        $questionIds = is_array($this->verification_pdf_output_question_ids)
            ? $this->verification_pdf_output_question_ids
            : [];

        return array_values(array_filter(
            $questionIds,
            fn ($questionId): bool => is_numeric($questionId) && (int) $questionId > 0
        ));
    }
}
