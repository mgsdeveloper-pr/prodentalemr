<?php

namespace App\Models;

use App\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationFormQuestion extends Model
{
    use HasPublicId;

    public const DEFAULT_TEMPLATE_KEY = 'template_3';

    public const TEMPLATE_OPTIONS = [
        'template_3' => 'Master Template',
    ];

    public const ACTIVE_TEMPLATE_OPTIONS = [
        'template_3' => 'Master Template',
    ];

    public const QUESTION_KIND_NORMAL = 'normal';

    public const QUESTION_KIND_CONDITIONAL = 'conditional';

    public const QUESTION_KIND_OPTIONS = [
        self::QUESTION_KIND_NORMAL => 'Primary question',
        self::QUESTION_KIND_CONDITIONAL => 'Conditional sub-question',
    ];

    public const CONDITIONAL_TRIGGER_OPTIONS = [
        'yes' => 'Yes',
        'no' => 'No',
    ];

    public const SECTION_OPTIONS = [
        'core_details' => 'Core Eligibility Snapshot',
        'coverage_matrix' => 'Category Coverage',
        'plan_provisions' => 'Plan Provisions',
        'history' => 'History',
        'frequency_diagnostic_preventative' => 'Frequency & Percentage / Diagnostic & Preventative',
        'frequency_basic' => 'Frequency & Percentage / Basic',
        'frequency_major' => 'Frequency & Percentage / Major',
        'frequency_orthodontics_benefit' => 'Frequency & Percentage / Orthodontics Benefit',
        'service_history' => 'Service History',
        'verification_information' => 'Verification Information',
    ];

    public const TEMPLATE_3_SECTION_OPTIONS = [
        'template_3_patient_subscriber' => 'Patient & Subscriber Information',
        'template_3_insurance' => 'Insurance Information',
        'template_3_maximums_deductibles' => 'Maximums & Deductibles',
        'template_3_coverage_category' => 'Deductible & Coverage Category',
        'template_3_plan_provisions' => 'Plan Provisions',
        'template_3_service_history' => 'Service History',
        'template_3_frequency_percentage' => 'Frequency & Percentage',
        'template_3_frequency_diagnostic_preventative' => 'Frequency & Percentage / Diagnostic & Preventative',
        'template_3_frequency_general' => 'Frequency & Percentage / General',
        'template_3_frequency_basic' => 'Frequency & Percentage / Basic',
        'template_3_frequency_major' => 'Frequency & Percentage / Major',
        'template_3_frequency_orthodontics' => 'Frequency & Percentage / Orthodontics',
        'template_3_verification_information' => 'Verification Information',
    ];

    public const TEMPLATE_3_LIVE_SECTION_KEYS = [
        'template_3_patient_subscriber',
        'template_3_insurance',
        'template_3_maximums_deductibles',
        'template_3_coverage_category',
        'template_3_plan_provisions',
        'template_3_service_history',
        'template_3_frequency_percentage',
        'template_3_frequency_diagnostic_preventative',
        'template_3_frequency_basic',
        'template_3_frequency_major',
        'template_3_frequency_orthodontics',
        'template_3_verification_information',
    ];

    public const TEMPLATE_3_DEPRECATED_SECTION_LABELS = [
        'Core Eligibility',
        'Core Eligibility Snapshot',
        'Category Coverage',
        'History',
        'Frequency & Percentage / Diagnostic & Preventative',
        'Frequency & Percentage / Orthodontics Benefit',
        'Template 3 Frequency Basic',
        'Template 3 Frequency General',
        'Template 3 Patient Subscriber',
    ];

    public const FORM_TYPE_OPTIONS = [
        'both' => 'Both Forms',
        'full_form' => 'Full Form',
        'short_form' => 'Short Form',
    ];

    public const INPUT_TYPE_OPTIONS = [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'date' => 'Date',
        'month_year' => 'Month + Year',
        'month_only' => 'Only Month',
        'year_only' => 'Only Year',
        'time' => 'Time',
        'email' => 'Email',
        'tel' => 'Phone',
        'number' => 'Number',
        'currency' => 'Currency',
        'yes_no' => 'Yes / No',
        'select' => 'Dropdown',
        'multi_select' => 'Multi Response',
        'percent' => 'Percent',
        'frequency_row' => 'Frequency / Percentage Row',
    ];

    public const FREQUENCY_RESPONSE_MODE_OPTIONS = [
        'current' => 'Current Response',
        'advanced' => 'Advanced Response',
    ];

    public const FREQUENCY_BASE_RESPONSE_FIELDS = [
        'coverage_percent' => '%',
        'frequency' => 'Frequency',
    ];

    public const FREQUENCY_CURRENT_OPTIONAL_FIELDS = [
        'coverage_status' => 'Status',
        'service_history' => 'History',
        'pre_auth_required' => 'Pre-Auth',
        'notes' => 'Notes',
    ];

    public const FREQUENCY_ADVANCED_OPTIONAL_FIELDS = [
        'coverage_status' => 'Status',
        'service_history' => 'History',
        'pre_auth_required' => 'Pre-Auth',
        'downgrade_applies' => 'Downgrade',
        'age_limit' => 'Age Limit',
        'waiting_period' => 'Waiting Period',
        'pre_auth_details' => 'Pre-Auth Detail',
        'downgrade_to' => 'Downgrade Detail',
        'payment_guideline' => 'Payment Guideline / Payer Rule',
        'notes' => 'Additional Notes',
    ];

    public const CODE_PREFIX_OPTIONS = [
        'core_details' => [
            'Clinic Name' => 'Clinic Name',
            'Network Status' => 'Network Status',
            'Appointment Date' => 'Appointment Date',
            'Insurance Name & Tel#' => 'Insurance Name & Tel#',
            'Insurance Claim Mailing Address' => 'Insurance Claim Mailing Address',
            'Electronic Payer ID#' => 'Electronic Payer ID#',
            'Effective Date' => 'Effective Date',
            'Plan Renewal Month' => 'Plan Renewal Month',
            'Future Termination Date' => 'Future Termination Date',
            'Employer / Group Name' => 'Employer / Group Name',
            'Group Number' => 'Group Number',
            'Fee Schedule' => 'Fee Schedule',
            'Annual Maximum' => 'Annual Maximum',
            'Remaining Maximum' => 'Remaining Maximum',
            'Individual Deductible' => 'Individual Deductible',
            'Family Deductible' => 'Family Deductible',
        ],
        'coverage_matrix' => [
            'Diagnostic & Preventive' => 'Diagnostic & Preventive',
            'Basic Restorative' => 'Basic Restorative',
            'Endodontics' => 'Endodontics',
            'Periodontics' => 'Periodontics',
            'Oral Surgery' => 'Oral Surgery',
            'Major Restorative' => 'Major Restorative',
            'Prosthodontics' => 'Prosthodontics',
            'Implant' => 'Implant',
            'Orthodontics' => 'Orthodontics',
        ],
        'plan_provisions' => [
            'Plan Provisions' => 'Plan Provisions',
            'Waiting Periods' => 'Waiting Periods',
            'Waiting Period Perio' => 'Waiting Period Perio',
            'Waiting Period Oral Surgery' => 'Waiting Period Oral Surgery',
            'Waiting Period Crowns' => 'Waiting Period Crowns',
            'Waiting Period Prosthodontics' => 'Waiting Period Prosthodontics',
            'Waiting Period Implant Services' => 'Waiting Period Implant Services',
            'Missing Tooth Clause' => 'Missing Tooth Clause',
            'Crowns Paid On' => 'Crowns Paid On',
            'Allowed Same Day Extraction' => 'Allowed Same Day Extraction',
            'Prosthetic Replacement Period' => 'Prosthetic Replacement Period',
            'COB' => 'COB',
        ],
        'history' => [
            'Service History' => 'Service History',
            'Exams History' => 'Exams History',
            'Prophylaxis History' => 'Prophylaxis History',
            'Bitewings History' => 'Bitewings History',
            'Full Mouth X-Ray History' => 'Full Mouth X-Ray History',
            'Basic or Major History' => 'Basic or Major History',
        ],
        'frequency_diagnostic_preventative' => [
            'Regular Oral Exams' => 'Regular Oral Exams',
            'Limited Exam' => 'Limited Exam',
            'Comprehensive Exam' => 'Comprehensive Exam',
            'Exam Shared' => 'Exam Shared',
            'Oral Cancer Screening' => 'Oral Cancer Screening',
            'Oral Cancer Conjunction' => 'Oral Cancer Conjunction',
            'Prophylaxis' => 'Prophylaxis',
            'Bitewings' => 'Bitewings',
            'Full Mouth / Pano Shared' => 'Full Mouth / Pano Shared',
            'PAs' => 'PAs',
            'Sealants' => 'Sealants',
            'Sealants Guideline' => 'Sealants Guideline',
            'Caries Arresting' => 'Caries Arresting',
            'Fluoride' => 'Fluoride',
        ],
        'frequency_basic' => [
            'Scaling & Root Planing' => 'Scaling & Root Planing',
            'All Quads Same Visit' => 'All Quads Same Visit',
            'Perio Maintenance Share Frequency' => 'Perio Maintenance Share Frequency',
            'FMD' => 'FMD',
            'Root Canal' => 'Root Canal',
            'Simple Extraction' => 'Simple Extraction',
            'Surgical Extraction' => 'Surgical Extraction',
            'Posterior Composites' => 'Posterior Composites',
            'Composites Downgrade' => 'Composites Downgrade',
            'Occlusal Guard' => 'Occlusal Guard',
        ],
        'frequency_major' => [
            'Crowns Downgrade' => 'Crowns Downgrade',
            'PFM High Noble' => 'PFM High Noble',
            'Hydroxyapatite' => 'Hydroxyapatite',
            'Dentures' => 'Dentures',
            'Implant' => 'Implant',
            'Implant Abutment' => 'Implant Abutment',
            'Implant Crown' => 'Implant Crown',
            'Bone Graft with Implant' => 'Bone Graft with Implant',
            'Bone Grafts' => 'Bone Grafts',
        ],
        'frequency_orthodontics_benefit' => [
            'Orthodontics Benefit' => 'Orthodontics Benefit',
            'Orthodontic Retention' => 'Orthodontic Retention',
            'Ortho Lifetime Maximum' => 'Ortho Lifetime Maximum',
            'Remaining Ortho Maximum' => 'Remaining Ortho Maximum',
            'Ortho Deductibles' => 'Ortho Deductibles',
            'Ortho Age Limit' => 'Ortho Age Limit',
            'Additional Ortho Information' => 'Additional Ortho Information',
        ],
        'service_history' => [
            'Service History' => 'Service History',
        ],
        'verification_information' => [
            'Verification Date' => 'Verification Date',
            'Verified By' => 'Verified By',
            'Insurance Representative Name' => 'Insurance Representative Name',
            'Quick Reference' => 'Quick Reference',
            'Verification Notes' => 'Verification Notes',
        ],
    ];

    public static function defaultTemplateKey(): string
    {
        return self::DEFAULT_TEMPLATE_KEY;
    }

    public static function templateOptionsForUi(): array
    {
        return self::ACTIVE_TEMPLATE_OPTIONS;
    }

    public static function isWorksheetTemplate(?string $templateKey): bool
    {
        return self::normalizeTemplateKey($templateKey) === 'template_3';
    }

    public static function normalizeTemplateKey(?string $templateKey): string
    {
        return array_key_exists($templateKey, self::TEMPLATE_OPTIONS)
            ? $templateKey
            : self::DEFAULT_TEMPLATE_KEY;
    }

    protected $fillable = [
        'organization_id',
        'clinic_id',
        'template_version_id',
        'source_question_id',
        'template_key',
        'question_kind',
        'parent_question_id',
        'trigger_answer',
        'prompt',
        'section_key',
        'form_type',
        'input_type',
        'field_key',
        'secondary_field_key',
        'secondary_input_type',
        'code',
        'help_text',
        'placeholder',
        'select_options',
        'frequency_response_mode',
        'frequency_response_fields',
        'has_note',
        'note_label',
        'note_placeholder',
        'sort_order',
        'is_builtin',
        'is_locked_by_admin',
        'is_required_for_audit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'template_version_id' => 'integer',
            'source_question_id' => 'integer',
            'is_builtin' => 'boolean',
            'is_locked_by_admin' => 'boolean',
            'is_required_for_audit' => 'boolean',
            'is_active' => 'boolean',
            'has_note' => 'boolean',
            'sort_order' => 'integer',
            'frequency_response_fields' => 'array',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(VerificationFormAnswer::class);
    }

    public function parentQuestion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_question_id');
    }

    public function childQuestions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_question_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(VerificationTemplateVersion::class, 'template_version_id');
    }

    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_question_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeVisibleForClinic(Builder $query, ?int $clinicId = null, ?int $organizationId = null): Builder
    {
        return $query
            ->where(function (Builder $query) use ($clinicId): void {
                $query->whereNull('clinic_id');

                if (filled($clinicId)) {
                    $query->orWhere('clinic_id', $clinicId);
                }
            })
            ->when(filled($organizationId), function (Builder $query) use ($organizationId): void {
                $query->where(function (Builder $query) use ($organizationId): void {
                    $query
                        ->whereNull('organization_id')
                        ->orWhere('organization_id', $organizationId);
                });
            });
    }

    public function isConditionalQuestion(): bool
    {
        return $this->question_kind === self::QUESTION_KIND_CONDITIONAL;
    }

    public function matchesTrigger(mixed $answer): bool
    {
        if (! $this->isConditionalQuestion()) {
            return true;
        }

        $trigger = strtolower(trim((string) $this->trigger_answer));
        $answer = strtolower(trim((string) $answer));

        return filled($trigger) && $trigger === $answer;
    }

    public static function fieldKeyOptionsForSection(?string $sectionKey = null): array
    {
        $options = [
            'Core Eligibility' => [
                'vf_patient_full_name' => 'Patient Name',
                'vf_patient_dob' => 'Patient DOB',
                'vf_patient_identifier' => 'Member ID',
                'vf_patient_zip' => 'Patient ZIP',
                'vf_appointment_date' => 'Appointment Date',
                'vf_appointment_time' => 'Appointment Time',
                'vf_subscriber_name' => 'Subscriber Name',
                'vf_subscriber_dob' => 'Subscriber DOB',
                'vf_subscriber_id' => 'Subscriber ID',
                'vf_insured_relation' => 'Relationship to Subscriber',
                'vf_coverage_role' => 'Coverage Role',
                'vf_insurance_provider_name' => 'Insurance Provider Name',
                'vf_insurance_claim_mailing_address' => 'Claim Mailing Address',
                'vf_insurance_company_phone_number' => 'Insurance Company Phone',
                'vf_payer_id' => 'Payer ID',
                'vf_effective_date' => 'Effective Date',
                'vf_group_name' => 'Group Name',
                'vf_group_number' => 'Group Number',
                'vf_network_status' => 'Network Status',
                'vf_fee_schedule' => 'Fee Schedule',
                'vf_plan_type' => 'Plan Type',
                'vf_plan_renewal_month' => 'Plan Renewal Month',
                'vf_future_termination_date' => 'Future Termination Date',
            ],
            'Benefits & Deductibles' => [
                'vf_annual_maximum' => 'Annual Maximum',
                'vf_annual_maximum_remaining' => 'Annual Maximum Remaining',
                'vf_individual_deductible' => 'Individual Deductible',
                'vf_individual_deductible_remaining' => 'Individual Deductible Remaining',
                'vf_family_deductible' => 'Family Deductible',
                'vf_family_deductible_remaining' => 'Family Deductible Remaining',
                'vf_deductible_applies_notes' => 'Deductible Applies Notes',
            ],
            'Coverage Matrix' => [
                'vf_coverage_diagnostic_deductible_applies' => 'Diagnostic Deductible Applies',
                'vf_coverage_basic_restorative_deductible_applies' => 'Basic Deductible Applies',
                'vf_coverage_endodontics_deductible_applies' => 'Endodontics Deductible Applies',
                'vf_coverage_periodontics_deductible_applies' => 'Periodontics Deductible Applies',
                'vf_coverage_oral_surgery_deductible_applies' => 'Oral Surgery Deductible Applies',
                'vf_coverage_major_restorative_deductible_applies' => 'Major Deductible Applies',
                'vf_coverage_orthodontics_deductible_applies' => 'Orthodontics Deductible Applies',
                'vf_coverage_diagnostic' => 'Diagnostic Coverage %',
                'vf_coverage_preventive' => 'Preventive Coverage %',
                'vf_coverage_basic_restorative' => 'Basic Restorative Coverage %',
                'vf_coverage_endodontics' => 'Endodontics Coverage %',
                'vf_coverage_periodontics' => 'Periodontics Coverage %',
                'vf_coverage_oral_surgery' => 'Oral Surgery Coverage %',
                'vf_coverage_major_restorative' => 'Major Restorative Coverage %',
                'vf_coverage_prosthodontics' => 'Prosthodontics Coverage %',
                'vf_coverage_implant' => 'Implant Coverage %',
                'vf_ortho_lifetime_maximum' => 'Orthodontics Lifetime Maximum',
            ],
            'Plan Provisions' => [
                'vf_plan_provisions' => 'Plan Provisions',
                'vf_waiting_periods' => 'Waiting Periods',
                'vf_waiting_period_perio' => 'Waiting Period - Perio',
                'vf_waiting_period_oral_surgery' => 'Waiting Period - Oral Surgery',
                'vf_waiting_period_crowns' => 'Waiting Period - Crowns',
                'vf_waiting_period_prosthodontics' => 'Waiting Period - Prosthodontics',
                'vf_waiting_period_implant_services' => 'Waiting Period - Implant Services',
                'vf_missing_tooth_clause' => 'Missing Tooth Clause',
                'vf_crowns_paid_on' => 'Crowns Paid On',
                'vf_allowed_same_day_extraction' => 'Allowed Same Day Extraction',
                'vf_prosthetic_replacement_period' => 'Prosthetic Replacement Period',
                'vf_coordination_of_benefits' => 'Coordination of Benefits',
                'vf_cob' => 'Coordination of Benefits',
            ],
            'History & Frequency' => [
                'vf_service_history' => 'Service History',
                'vf_history_exams' => 'Exams History',
                'vf_history_prophylaxis' => 'Prophylaxis History',
                'vf_history_bitewings' => 'Bitewings History',
                'vf_history_full_mouth_xray' => 'Full Mouth X-Ray History',
                'vf_history_basic_or_major' => 'Basic / Major History',
                'vf_frequency_regular_oral_exams' => 'Regular Oral Exams Frequency',
                'vf_frequency_limited_exam' => 'Limited Exam Frequency',
                'vf_frequency_comprehensive_exam' => 'Comprehensive Exam Frequency',
                'vf_frequency_exam_shared' => 'Exam Shared Frequency',
                'vf_frequency_oral_cancer_screening' => 'Oral Cancer Screening Frequency',
                'vf_frequency_oral_cancer_conjunction' => 'Oral Cancer Conjunction Frequency',
                'vf_frequency_prophylaxis' => 'Prophylaxis Frequency',
                'vf_frequency_bitewings' => 'Bitewings Frequency',
                'vf_frequency_full_mouth_pano_shared' => 'Full Mouth / Pano Shared Frequency',
                'vf_frequency_pas' => 'PA Frequency',
                'vf_frequency_sealants' => 'Sealants Frequency',
                'vf_frequency_sealants_guideline' => 'Sealants Guideline',
                'vf_frequency_caries_arresting' => 'Caries Arresting Frequency',
                'vf_frequency_fluoride' => 'Fluoride Frequency',
            ],
            'Basic / Major / Ortho' => [
                'vf_basic_scaling_root_planing' => 'Scaling & Root Planing',
                'vf_basic_all_quads_same_visit' => 'All Quads Same Visit',
                'vf_basic_perio_maintenance_share_freq' => 'Perio Maintenance Share Frequency',
                'vf_basic_fmd' => 'FMD',
                'vf_basic_root_canal' => 'Root Canal',
                'vf_basic_simple_extraction' => 'Simple Extraction',
                'vf_basic_surgical_extraction' => 'Surgical Extraction',
                'vf_basic_posterior_composites' => 'Posterior Composites',
                'vf_basic_composites_downgrade' => 'Composites Downgrade',
                'vf_basic_occlusal_guard' => 'Occlusal Guard',
                'vf_major_crowns_downgrade' => 'Crowns Downgrade',
                'vf_major_pf_high_noble' => 'PFM High Noble',
                'vf_major_hydroxyapatite' => 'Hydroxyapatite',
                'vf_major_dentures' => 'Dentures',
                'vf_major_implant' => 'Implant',
                'vf_major_implant_abutment' => 'Implant Abutment',
                'vf_major_implant_crown' => 'Implant Crown',
                'vf_major_bone_graft_same_time_implant' => 'Bone Graft with Implant',
                'vf_major_bone_grafts' => 'Bone Grafts',
                'vf_ortho_benefit' => 'Orthodontics Benefit',
                'vf_ortho_retention' => 'Orthodontic Retention',
                'vf_ortho_lifetime_maximum' => 'Ortho Lifetime Maximum',
                'vf_ortho_remaining_maximum' => 'Remaining Ortho Maximum',
                'vf_ortho_deductibles' => 'Ortho Deductibles',
                'vf_ortho_age_limit' => 'Ortho Age Limit',
                'vf_ortho_information' => 'Additional Ortho Information',
            ],
            'Verification Information' => [
                'vf_verification_date' => 'Verification Date',
                'vf_verified_by' => 'Verified By',
                'vf_insurance_representative_name' => 'Insurance Representative Name',
                'vf_quick_reference' => 'Quick Reference',
                'vf_verification_notes' => 'Verification Notes',
            ],
        ];

        $sectionMap = [
            'core_details' => ['Core Eligibility', 'Benefits & Deductibles'],
            'coverage_matrix' => ['Coverage Matrix'],
            'plan_provisions' => ['Plan Provisions'],
            'history' => ['History & Frequency'],
            'frequency_diagnostic_preventative' => ['History & Frequency'],
            'frequency_basic' => ['Basic / Major / Ortho'],
            'frequency_major' => ['Basic / Major / Ortho'],
            'frequency_orthodontics_benefit' => ['Basic / Major / Ortho'],
            'service_history' => ['History & Frequency'],
            'verification_information' => ['Verification Information'],
            'template_3_patient_subscriber' => ['Core Eligibility'],
            'template_3_insurance' => ['Core Eligibility'],
            'template_3_maximums_deductibles' => ['Benefits & Deductibles'],
            'template_3_coverage_category' => ['Coverage Matrix'],
            'template_3_plan_provisions' => ['Plan Provisions'],
            'template_3_service_history' => ['History & Frequency'],
            'template_3_frequency_percentage' => ['History & Frequency', 'Basic / Major / Ortho'],
            'template_3_frequency_general' => ['History & Frequency'],
            'template_3_frequency_basic' => ['Basic / Major / Ortho'],
            'template_3_frequency_major' => ['Basic / Major / Ortho'],
            'template_3_frequency_orthodontics' => ['Basic / Major / Ortho'],
            'template_3_verification_information' => ['Verification Information'],
        ];

        if (blank($sectionKey) || ! array_key_exists($sectionKey, $sectionMap)) {
            return $options;
        }

        $filtered = [];

        foreach ($sectionMap[$sectionKey] as $group) {
            if (array_key_exists($group, $options)) {
                $filtered[$group] = $options[$group];
            }
        }

        return $filtered;
    }

    public static function sectionOptionsForTemplate(?string $templateKey, ?int $clinicId = null, ?int $templateVersionId = null): array
    {
        $templateKey = static::normalizeTemplateKey($templateKey);

        $builtInOptions = match ($templateKey) {
            'template_3' => static::templateThreeLiveSectionOptions(),
            default => self::SECTION_OPTIONS,
        };

        $customSections = VerificationTemplateSection::query()
            ->visibleForClinic($clinicId)
            ->where('template_key', $templateKey ?: self::DEFAULT_TEMPLATE_KEY)
            ->when(
                blank($clinicId),
                fn (Builder $query) => $query->where('template_version_id', $templateVersionId ?: static::currentMasterWorkingTemplateVersionId())
            )
            ->when(
                filled($clinicId),
                fn (Builder $query) => $query->where('template_version_id', $templateVersionId ?: static::currentClinicWorkingTemplateVersionId($clinicId))
            )
            ->where('is_active', true)
            ->orderByRaw('parent_section_key is not null')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        if ($templateKey === 'template_3') {
            $customSections = $customSections
                ->reject(fn (VerificationTemplateSection $section): bool => static::isDeprecatedTemplateThreeSection($section))
                ->values();
        }

        $customLabels = $customSections
            ->pluck('label', 'section_key')
            ->all();

        $customSectionOptions = $customSections
            ->mapWithKeys(function (VerificationTemplateSection $section) use ($builtInOptions, $customLabels): array {
                $parentLabel = filled($section->parent_section_key)
                    ? ($builtInOptions[$section->parent_section_key] ?? $customLabels[$section->parent_section_key] ?? null)
                    : null;

                return [
                    $section->section_key => filled($parentLabel)
                        ? "{$parentLabel} / {$section->label}"
                        : $section->label,
                ];
            })
            ->all();

        return $builtInOptions + $customSectionOptions;
    }

    public static function templateThreeLiveSectionOptions(): array
    {
        return collect(self::TEMPLATE_3_SECTION_OPTIONS)
            ->only(self::TEMPLATE_3_LIVE_SECTION_KEYS)
            ->all();
    }

    protected static function isDeprecatedTemplateThreeSection(VerificationTemplateSection $section): bool
    {
        $key = (string) $section->section_key;
        $label = trim((string) $section->label);

        if (array_key_exists($key, self::SECTION_OPTIONS)) {
            return true;
        }

        if (in_array($label, self::TEMPLATE_3_DEPRECATED_SECTION_LABELS, true)) {
            return true;
        }

        if (str_starts_with($key, 'frequency_')) {
            return true;
        }

        if (str_starts_with($label, 'Template 3 ')) {
            return true;
        }

        return false;
    }

    public static function topLevelSectionOptionsForTemplate(?string $templateKey, ?int $clinicId = null, ?int $templateVersionId = null): array
    {
        $templateKey = static::normalizeTemplateKey($templateKey);

        $options = $templateKey === 'template_3'
            ? static::templateThreeBuilderSectionOptions($clinicId, $templateVersionId)
            : static::sectionOptionsForTemplate($templateKey, $clinicId, $templateVersionId);

        return collect($options)
            ->reject(fn (string $label): bool => str_contains($label, ' / '))
            ->all();
    }

    public static function templateThreeBuilderSectionOptions(?int $clinicId = null, ?int $templateVersionId = null): array
    {
        $builtInOptions = self::TEMPLATE_3_SECTION_OPTIONS;

        $customSections = VerificationTemplateSection::query()
            ->visibleForClinic($clinicId)
            ->where('template_key', self::DEFAULT_TEMPLATE_KEY)
            ->when(
                blank($clinicId),
                fn (Builder $query) => $query->where('template_version_id', $templateVersionId ?: static::currentMasterWorkingTemplateVersionId())
            )
            ->when(
                filled($clinicId),
                fn (Builder $query) => $query->where('template_version_id', $templateVersionId ?: static::currentClinicWorkingTemplateVersionId($clinicId))
            )
            ->where('is_active', true)
            ->orderByRaw('parent_section_key is not null')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->reject(fn (VerificationTemplateSection $section): bool => static::isDeprecatedTemplateThreeSection($section))
            ->values();

        $customLabels = $customSections
            ->pluck('label', 'section_key')
            ->all();

        $customSectionOptions = $customSections
            ->mapWithKeys(function (VerificationTemplateSection $section) use ($builtInOptions, $customLabels): array {
                $parentLabel = filled($section->parent_section_key)
                    ? ($builtInOptions[$section->parent_section_key] ?? $customLabels[$section->parent_section_key] ?? null)
                    : null;

                return [
                    $section->section_key => filled($parentLabel)
                        ? "{$parentLabel} / {$section->label}"
                        : $section->label,
                ];
            })
            ->all();

        return $builtInOptions + $customSectionOptions;
    }

    public static function childSectionOptionsForTemplate(?string $templateKey, ?int $clinicId, ?string $parentSectionKey, ?int $templateVersionId = null): array
    {
        if (blank($parentSectionKey)) {
            return [];
        }

        $builtInChildren = match ($parentSectionKey) {
            'template_3_frequency_percentage' => [
                'template_3_frequency_diagnostic_preventative' => 'Diagnostic & Preventative',
                'template_3_frequency_basic' => 'Basic',
                'template_3_frequency_major' => 'Major',
                'template_3_frequency_orthodontics' => 'Orthodontics',
            ],
            default => [],
        };

        $customChildren = VerificationTemplateSection::query()
            ->visibleForClinic($clinicId)
            ->where('template_key', $templateKey ?: self::DEFAULT_TEMPLATE_KEY)
            ->when(
                blank($clinicId),
                fn (Builder $query) => $query->where('template_version_id', $templateVersionId ?: static::currentMasterWorkingTemplateVersionId())
            )
            ->when(
                filled($clinicId),
                fn (Builder $query) => $query->where('template_version_id', $templateVersionId ?: static::currentClinicWorkingTemplateVersionId($clinicId))
            )
            ->where('parent_section_key', $parentSectionKey)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->when(
                static::normalizeTemplateKey($templateKey) === 'template_3',
                fn ($sections) => $sections->reject(fn (VerificationTemplateSection $section): bool => static::isDeprecatedTemplateThreeSection($section))->values()
            )
            ->pluck('label', 'section_key')
            ->all();

        return $builtInChildren + $customChildren;
    }

    public static function parentSectionKeyFor(?string $sectionKey, ?string $templateKey = null, ?int $clinicId = null): ?string
    {
        if (blank($sectionKey)) {
            return null;
        }

        $builtInParents = [
            'template_3_frequency_diagnostic_preventative' => 'template_3_frequency_percentage',
            'template_3_frequency_general' => 'template_3_frequency_percentage',
            'template_3_frequency_basic' => 'template_3_frequency_percentage',
            'template_3_frequency_major' => 'template_3_frequency_percentage',
            'template_3_frequency_orthodontics' => 'template_3_frequency_percentage',
        ];

        if (array_key_exists($sectionKey, $builtInParents)) {
            return $builtInParents[$sectionKey];
        }

        return VerificationTemplateSection::query()
            ->visibleForClinic($clinicId)
            ->where('template_key', $templateKey ?: self::DEFAULT_TEMPLATE_KEY)
            ->when(
                blank($clinicId),
                fn (Builder $query) => $query->where('template_version_id', static::currentMasterWorkingTemplateVersionId())
            )
            ->when(
                filled($clinicId),
                fn (Builder $query) => $query->where('template_version_id', static::currentClinicWorkingTemplateVersionId($clinicId))
            )
            ->where('section_key', $sectionKey)
            ->orderByRaw('clinic_id is null')
            ->value('parent_section_key');
    }

    public static function isFrequencyPercentageSection(?string $sectionKey): bool
    {
        return in_array($sectionKey, [
            'template_3_frequency_percentage',
            'template_3_frequency_diagnostic_preventative',
            'template_3_frequency_general',
            'template_3_frequency_basic',
            'template_3_frequency_major',
            'template_3_frequency_orthodontics',
        ], true);
    }

    public static function templateThreeFrequencyCategory(?string $sectionKey): string
    {
        return match ($sectionKey) {
            'template_3_frequency_diagnostic_preventative' => 'Diagnostic & Preventative',
            'template_3_frequency_basic' => 'Basic',
            'template_3_frequency_major' => 'Major',
            'template_3_frequency_orthodontics' => 'Orthodontics',
            default => 'General',
        };
    }

    public static function frequencyResponseFieldOptions(?string $mode): array
    {
        return $mode === 'advanced'
            ? self::FREQUENCY_ADVANCED_OPTIONAL_FIELDS
            : self::FREQUENCY_CURRENT_OPTIONAL_FIELDS;
    }

    public static function defaultFrequencyResponseFields(?string $mode): array
    {
        return $mode === 'advanced'
            ? ['coverage_status', 'service_history', 'pre_auth_required', 'downgrade_applies', 'age_limit', 'waiting_period', 'pre_auth_details', 'downgrade_to', 'payment_guideline', 'notes']
            : ['pre_auth_required', 'notes'];
    }

    public static function normalizeFrequencyResponseFields(mixed $fields, ?string $mode): array
    {
        if (! is_array($fields)) {
            return self::defaultFrequencyResponseFields($mode);
        }

        $allowedFields = array_keys(self::frequencyResponseFieldOptions($mode));

        return collect($fields)
            ->filter(fn ($field): bool => is_string($field) && in_array($field, $allowedFields, true))
            ->unique()
            ->values()
            ->all();
    }

    public static function parentQuestionOptionsFor(
        ?string $templateKey,
        ?int $clinicId,
        ?int $organizationId,
        ?string $sectionKey,
        ?int $ignoreQuestionId = null,
    ): array {
        if (blank($sectionKey)) {
            return [];
        }

        return static::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('template_key', static::normalizeTemplateKey($templateKey))
            ->when(
                blank($clinicId) && blank($organizationId),
                fn (Builder $query) => $query->where('template_version_id', static::currentMasterWorkingTemplateVersionId())
            )
            ->where('section_key', $sectionKey)
            ->where('is_active', true)
            ->where('input_type', 'yes_no')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('question_kind')
                    ->orWhere('question_kind', self::QUESTION_KIND_NORMAL);
            })
            ->when(filled($ignoreQuestionId), fn (Builder $query) => $query->whereKeyNot($ignoreQuestionId))
            ->orderByRaw('clinic_id is null')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('prompt', 'id')
            ->all();
    }

    protected static function currentMasterWorkingTemplateVersionId(): ?int
    {
        $templateKey = static::DEFAULT_TEMPLATE_KEY;

        $draftId = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
            ->where('template_key', $templateKey)
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->whereNull('clinic_id')
            ->latest('id')
            ->value('id');

        if ($draftId) {
            return (int) $draftId;
        }

        $publishedId = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
            ->where('template_key', $templateKey)
            ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->whereNull('clinic_id')
            ->value('id');

        return $publishedId ? (int) $publishedId : null;
    }

    protected static function currentClinicWorkingTemplateVersionId(?int $clinicId): ?int
    {
        if (! $clinicId) {
            return null;
        }

        $templateKey = static::DEFAULT_TEMPLATE_KEY;

        $draftId = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', $templateKey)
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->where('clinic_id', $clinicId)
            ->latest('id')
            ->value('id');

        if ($draftId) {
            return (int) $draftId;
        }

        $publishedId = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
            ->where('template_key', $templateKey)
            ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->where('clinic_id', $clinicId)
            ->value('id');

        return $publishedId ? (int) $publishedId : null;
    }

    public static function sectionLabel(?string $sectionKey, ?string $templateKey = null, ?int $clinicId = null): string
    {
        if (blank($sectionKey)) {
            return 'Unassigned Section';
        }

        $options = static::sectionOptionsForTemplate($templateKey, $clinicId);

        if (static::normalizeTemplateKey($templateKey) === 'template_3') {
            return $options[$sectionKey]
                ?? self::TEMPLATE_3_SECTION_OPTIONS[$sectionKey]
                ?? str($sectionKey)->headline()->toString();
        }

        return $options[$sectionKey]
            ?? self::SECTION_OPTIONS[$sectionKey]
            ?? str($sectionKey)->headline()->toString();
    }

    public function getSelectOptionValues(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->select_options) ?: [])
            ->map(fn (string $option): string => trim($option))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function codePrefixOptionsForSection(?string $sectionKey = null): array
    {
        if (filled($sectionKey) && array_key_exists($sectionKey, self::CODE_PREFIX_OPTIONS)) {
            return self::CODE_PREFIX_OPTIONS[$sectionKey];
        }

        $options = [];

        foreach (self::CODE_PREFIX_OPTIONS as $sectionOptions) {
            foreach ($sectionOptions as $value => $label) {
                $options[$value] = $label;
            }
        }

        asort($options);

        return $options;
    }
}
