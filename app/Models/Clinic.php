<?php

namespace App\Models;

use App\Models\VerificationFormQuestion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'verification_services_enabled',
        'clinic_operations_enabled',
        'service_status',
        'pms_service_status',
        'verification_service_status',
        'managed_services_status',
        'trial_ends_at',
        'demo_mode',
        'feature_overrides',
        'usage_snapshot',
        'account_manager_user_id',
        'service_notes',
        'verification_pdf_output_mode',
        'verification_default_form_template',
        'verification_pdf_output_sections',
        'verification_pdf_output_question_ids',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'verification_services_enabled' => 'boolean',
            'clinic_operations_enabled' => 'boolean',
            'trial_ends_at' => 'date',
            'demo_mode' => 'boolean',
            'feature_overrides' => 'array',
            'usage_snapshot' => 'array',
            'verification_pdf_output_sections' => 'array',
            'verification_pdf_output_question_ids' => 'array',
        ];
    }

    public function hasVerificationServices(): bool
    {
        return (bool) $this->verification_services_enabled;
    }

    public function hasClinicOperations(): bool
    {
        return (bool) $this->clinic_operations_enabled;
    }

    public function hasActiveClinicOperations(): bool
    {
        return $this->hasClinicOperations()
            && in_array($this->pms_service_status, ['active', 'trial'], true)
            && in_array($this->service_status, ['active', 'trial'], true);
    }

    public function hasActiveVerificationServices(): bool
    {
        return $this->hasVerificationServices()
            && in_array($this->verification_service_status, ['active', 'trial'], true)
            && in_array($this->service_status, ['active', 'trial'], true);
    }

    public function allowsManagedServices(): bool
    {
        return in_array($this->managed_services_status, ['active', 'trial'], true);
    }

    public function featureOverride(string $feature, mixed $default = null): mixed
    {
        return data_get($this->feature_overrides ?? [], $feature, $default);
    }

    public function usageValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->usage_snapshot ?? [], $key, $default);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_user_id');
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

    public function verificationInboxMailbox(): HasOne
    {
        return $this->hasOne(VerificationInboxMailbox::class);
    }

    public function getVerificationPdfOutputMode(): string
    {
        $mode = (string) ($this->verification_pdf_output_mode ?: 'standard');

        return array_key_exists($mode, \App\Support\VerificationResultPdf::OUTPUT_MODE_OPTIONS)
            ? $mode
            : 'standard';
    }

    public function getVerificationDefaultFormTemplate(): string
    {
        return array_key_exists(
            (string) $this->verification_default_form_template,
            VerificationFormQuestion::TEMPLATE_OPTIONS
        )
            ? (string) $this->verification_default_form_template
            : 'template_2';
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
