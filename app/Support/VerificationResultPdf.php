<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationFormSubmission;
use App\Services\Verification\VerificationResultService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class VerificationResultPdf
{
    public const OUTPUT_MODE_OPTIONS = [
        'standard' => 'Standard',
        'custom_landscape' => 'Custom Landscape',
        'custom_portrait' => 'Custom Portrait',
    ];

    protected const LEGACY_OUTPUT_MODE_ALIASES = [
        'selected' => 'custom_landscape',
        'view' => 'standard',
    ];

    protected const SECTION_ORDER = [
        'template_3_patient_subscriber',
        'template_3_insurance',
        'template_3_maximums_deductibles',
        'template_3_coverage_category',
        'template_3_plan_provisions',
        'template_3_service_history',
        'template_3_frequency_diagnostic_preventative',
        'template_3_frequency_basic',
        'template_3_frequency_major',
        'template_3_frequency_orthodontics',
        'template_3_verification_information',
    ];

    protected const SECTION_KEY_ALIASES = [
        'core_details' => 'template_3_patient_subscriber',
        'coverage_matrix' => 'template_3_coverage_category',
        'plan_provisions' => 'template_3_plan_provisions',
        'history' => 'template_3_service_history',
        'service_history' => 'template_3_service_history',
        'frequency_diagnostic_preventative' => 'template_3_frequency_diagnostic_preventative',
        'frequency_basic' => 'template_3_frequency_basic',
        'frequency_major' => 'template_3_frequency_major',
        'frequency_orthodontics_benefit' => 'template_3_frequency_orthodontics',
        'verification_information' => 'template_3_verification_information',
        'template_3_frequency_general' => 'template_3_frequency_diagnostic_preventative',
    ];

    public static function fileName(BillingWorkItem $workItem, string $mode = 'standard', ?VerificationFormSubmission $submission = null): string
    {
        $base = $workItem->reference_number ?: 'verification-result';
        $base .= $submission ? '-result-v' . $submission->version : '';

        return match ($mode) {
            'custom_landscape' => "{$base}-custom-landscape.pdf",
            'custom_portrait' => "{$base}-custom-portrait.pdf",
            'selected' => "{$base}-custom-landscape.pdf",
            default => "{$base}.pdf",
        };
    }

    public static function output(BillingWorkItem $workItem, string $mode = 'standard', array $selectedSections = [], array $selectedQuestionIds = [], ?bool $showBlankRows = null, ?VerificationFormSubmission $submission = null): string
    {
        $workItem->loadMissing([
            'organization',
            'clinic',
            'location',
            'appointment',
            'patient.insurancePolicies',
            'provider.user',
            'insurancePolicy',
            'verificationPlanSnapshots',
            'verificationProfile',
            'verificationCoverageCodes',
            'verificationFormAnswers.question',
            'assignedTo',
            'reviewedBy',
            'activities.user',
            'workNotes.user',
            'attachments',
        ]);

        $mode = static::normalizeOutputMode($mode);
        $showBlankRows ??= ! static::isCustomOutputMode($mode);

        $resultService = app(VerificationResultService::class);
        $state = $resultService->applyRecordedDataToPdfState($workItem, static::buildState($workItem), $submission);
        $sections = static::buildSections($workItem, $state, $showBlankRows);
        $selectedSections = collect($selectedSections)
            ->filter(fn ($section): bool => filled($section))
            ->values()
            ->all();
        $selectedQuestionIds = collect($selectedQuestionIds)
            ->filter(fn ($questionId): bool => filled($questionId))
            ->map(fn ($questionId): int => (int) $questionId)
            ->filter(fn (int $questionId): bool => $questionId > 0)
            ->values()
            ->all();

        if (static::isCustomOutputMode($mode)) {
            $sections = static::filterSections($sections, $selectedSections, $selectedQuestionIds);
        }

        $view = match ($mode) {
            'custom_landscape' => 'pdf.verifications.custom-landscape',
            'custom_portrait' => 'pdf.verifications.custom-portrait',
            default => 'pdf.verifications.standard',
        };

        return Pdf::loadView($view, [
            'workItem' => $workItem,
            'state' => $state,
            'summary' => static::buildSummary($workItem, $state, $resultService, $submission),
            'sections' => $sections,
            'panels' => static::buildPanels($workItem, $state, $submission),
            'selectedSectionTitles' => collect($selectedSections)
                ->map(fn (string $key): string => VerificationFormQuestion::sectionLabel(static::normalizeSectionKey($key), VerificationFormQuestion::DEFAULT_TEMPLATE_KEY))
                ->all(),
            'selectedQuestionTitles' => VerificationFormQuestion::query()
                ->whereIn('id', $selectedQuestionIds)
                ->orderBy('section_key')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('prompt')
                ->all(),
        ])
            ->setPaper('a4', $mode === 'custom_landscape' ? 'landscape' : 'portrait')
            ->output();
    }

    public static function normalizeOutputMode(?string $mode): string
    {
        $mode = (string) ($mode ?: 'standard');
        $mode = self::LEGACY_OUTPUT_MODE_ALIASES[$mode] ?? $mode;

        return array_key_exists($mode, self::OUTPUT_MODE_OPTIONS) ? $mode : 'standard';
    }

    public static function isCustomOutputMode(?string $mode): bool
    {
        return in_array(static::normalizeOutputMode($mode), ['custom_landscape', 'custom_portrait'], true);
    }

    protected static function buildState(BillingWorkItem $workItem): array
    {
        $profile = $workItem->verificationProfile;
        $patient = $workItem->patient;
        $provider = $workItem->provider;
        $clinic = $workItem->clinic;
        $location = $workItem->location;
        $policy = $workItem->insurancePolicy ?: $patient?->insurancePolicies?->sortByDesc('coverage_priority')->first();
        $primaryPlan = $workItem->verificationPlanSnapshots
            ->sortBy(fn ($plan) => array_search($plan->plan_priority, ['primary', 'secondary', 'tertiary'], true))
            ->first();

        $state = [
            'context_clinic_name' => $clinic?->clinic_name ?: $location?->location_name ?: $workItem->organization?->name,
            'vf_patient_full_name' => $profile?->patient_full_name ?: $patient?->full_name,
            'vf_patient_dob' => static::formatDateForInput($profile?->patient_dob ?: $patient?->dob),
            'vf_patient_identifier' => $profile?->patient_identifier ?: $policy?->member_id ?: $primaryPlan?->member_id ?: $patient?->insurance_number,
            'vf_patient_zip' => $profile?->patient_zip,
            'vf_appointment_date' => static::formatDateForInput($profile?->appointment_date ?: $workItem->appointment?->appointment_date),
            'vf_appointment_time' => $profile?->appointment_time ?: $workItem->appointment?->start_time,
            'vf_subscriber_name' => $profile?->subscriber_name ?: $policy?->subscriber_name ?: $primaryPlan?->subscriber_name,
            'vf_subscriber_dob' => static::formatDateForInput($profile?->subscriber_dob ?: $policy?->subscriber_dob ?: $primaryPlan?->subscriber_dob),
            'vf_subscriber_id' => $profile?->subscriber_id ?: $primaryPlan?->member_id ?: $policy?->member_id ?: $patient?->insurance_number,
            'vf_insured_relation' => $profile?->insured_relation ?: $policy?->subscriber_relationship,
            'vf_insurance_provider_name' => $profile?->insurance_provider_name ?: $policy?->insurance_company ?: $primaryPlan?->payer_name ?: $patient?->insurance_provider,
            'vf_insurance_claim_mailing_address' => $profile?->insurance_claim_mailing_address ?: $policy?->claims_address,
            'vf_insurance_company_phone_number' => $profile?->insurance_company_phone_number ?: $policy?->payer_phone,
            'vf_payer_id' => $profile?->payer_id,
            'vf_effective_date' => static::formatDateForInput($profile?->effective_date ?: $policy?->effective_date),
            'vf_group_name' => $profile?->group_name ?: $policy?->subscriber_employer ?: $policy?->plan_name,
            'vf_group_number' => $profile?->group_number ?: $policy?->group_number ?: $primaryPlan?->group_number,
            'vf_plan_renewal_month' => $profile?->plan_renewal_month,
            'vf_future_termination_date' => static::formatDateForInput($profile?->future_termination_date ?: $policy?->termination_date),
            'vf_fee_schedule' => $profile?->fee_schedule,
            'vf_network_status' => static::resolveNetworkStatus($profile?->network_status, $profile?->is_provider_in_network),
            'vf_verification_date' => static::formatDateForInput($profile?->verification_date ?: $workItem->started_at ?: $workItem->updated_at ?: $workItem->created_at),
            'vf_verified_by' => $profile?->verified_by,
            'vf_insurance_representative_name' => $profile?->insurance_representative_name,
            'vf_quick_reference' => $profile?->quick_reference ?: static::buildQuickReference($workItem, $patient, $policy, $primaryPlan, $provider),
            'vf_verification_notes' => $profile?->verification_notes,
            'notes' => $workItem->notes,
            'internal_summary' => $workItem->internal_summary,
        ];

        if ($profile) {
            foreach ($profile->getAttributes() as $key => $value) {
                if (in_array($key, ['id', 'billing_work_item_id', 'created_at', 'updated_at'], true)) {
                    continue;
                }

                $state['vf_' . $key] ??= $value;
            }
        }

        $workItem->verificationFormAnswers()
            ->with('question')
            ->get()
            ->each(function ($answer) use (&$state): void {
                if (! $answer->question) {
                    return;
                }

                $state['custom_question_' . $answer->verification_form_question_id] = $answer->answer_value;
            });

        return $state;
    }

    protected static function buildSummary(BillingWorkItem $workItem, array $state, ?VerificationResultService $resultService = null, ?VerificationFormSubmission $submission = null): array
    {
        return [
            'reference_number' => $workItem->reference_number,
            'title' => $workItem->title,
            'patient_name' => $state['vf_patient_full_name'] ?? '-',
            'clinic_name' => $state['context_clinic_name'] ?? '-',
            'status' => BillingWorkItem::STATUS_OPTIONS[$submission?->status ?? $workItem->normalized_status] ?? str($submission?->status ?? $workItem->normalized_status)->headline()->toString(),
            'result' => ($resultService ?? app(VerificationResultService::class))->outcomeLabel($workItem, $submission),
            'priority' => BillingWorkItem::PRIORITY_OPTIONS[$workItem->priority] ?? str($workItem->priority)->headline()->toString(),
            'insurance_name' => $state['vf_insurance_provider_name'] ?? '-',
            'appointment_date' => static::displayValue($state['vf_appointment_date'] ?? null, 'date'),
            'assigned_to' => $workItem->assignedTo?->name ?: 'Unassigned',
        ];
    }

    protected static function buildSections(BillingWorkItem $workItem, array $state, bool $showBlankRows = true): array
    {
        $formType = $state['vf_form_type'] ?? $workItem->verificationProfile?->form_type ?? 'full_form';
        $clinicId = $workItem->clinic_id;

        if (! filled($clinicId)) {
            return [];
        }

        $questions = VerificationFormQuestion::query()
            ->where('is_active', true)
            ->where('clinic_id', $clinicId)
            ->whereIn('form_type', ['both', $formType])
            ->when(
                filled($workItem->verification_template_version_id),
                fn ($query) => $query->where('template_version_id', $workItem->verification_template_version_id),
                fn ($query) => $query->whereNull('template_version_id')
            )
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $hasLiveTemplateThreeSections = $questions->contains(
            fn (VerificationFormQuestion $question): bool => in_array(
                (string) $question->section_key,
                VerificationFormQuestion::TEMPLATE_3_LIVE_SECTION_KEYS,
                true
            )
        );

        if ($hasLiveTemplateThreeSections) {
            $questions = $questions
                ->filter(fn (VerificationFormQuestion $question): bool => in_array(
                    (string) $question->section_key,
                    VerificationFormQuestion::TEMPLATE_3_LIVE_SECTION_KEYS,
                    true
                ))
                ->values();
        }

        $questions = $questions
            ->groupBy(fn (VerificationFormQuestion $question): string => static::normalizeSectionKey($question->section_key));

        $sections = [];

        foreach (self::SECTION_ORDER as $sectionKey) {
            $sectionQuestions = $questions->get($sectionKey, collect());

            if ($sectionQuestions->isEmpty()) {
                continue;
            }

            $rows = $sectionQuestions
                ->map(fn (VerificationFormQuestion $question): ?array => static::mapQuestionRow($question, $state))
                ->filter()
                ->merge(static::mapCoverageCodeRowsForSection($workItem, $sectionKey))
                ->when(! $showBlankRows, fn (Collection $rows): Collection => $rows->filter(
                    fn (array $row): bool => static::rowHasPrintableValue($row)
                ))
                ->unique(fn (array $row): string => strtolower(trim((string) ($row['label'] ?? ''))))
                ->values()
                ->all();

            if (empty($rows)) {
                continue;
            }

            $sections[] = [
                'key' => $sectionKey,
                'title' => VerificationFormQuestion::sectionLabel($sectionKey, VerificationFormQuestion::DEFAULT_TEMPLATE_KEY),
                'rows' => $rows,
            ];
        }

        return $sections;
    }

    protected static function mapCoverageCodeRowsForSection(BillingWorkItem $workItem, string $sectionKey): Collection
    {
        $category = match ($sectionKey) {
            'template_3_frequency_diagnostic_preventative' => 'Diagnostic & Preventative',
            'template_3_frequency_basic' => 'Basic',
            'template_3_frequency_major' => 'Major',
            'template_3_frequency_orthodontics' => 'Orthodontics',
            default => null,
        };

        if (! filled($category)) {
            return collect();
        }

        return $workItem->verificationCoverageCodes
            ->filter(fn ($row): bool => strcasecmp((string) $row->category, $category) === 0)
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->map(function ($row): array {
                $parts = collect([
                    filled($row->coverage_percent) ? number_format((float) $row->coverage_percent, 0) . '%' : null,
                    filled($row->frequency) ? 'Freq: ' . $row->frequency : null,
                    filled($row->coverage_status) ? $row->coverage_status : null,
                    filled($row->age_limit) ? 'Age: ' . $row->age_limit : null,
                    filled($row->waiting_period) ? 'WP: ' . $row->waiting_period : null,
                    filled($row->service_history) ? 'History: ' . $row->service_history : null,
                    filled($row->pre_auth_required) ? 'Pre-auth: ' . $row->pre_auth_required : null,
                    filled($row->downgrade_applies) ? 'Downgrade: ' . $row->downgrade_applies : null,
                    filled($row->notes) ? 'Notes: ' . $row->notes : null,
                ])->filter()->implode(' | ');

                return [
                    'question_id' => null,
                    'kind' => 'frequency_code',
                    'label' => trim(collect([$row->code, $row->description])->filter()->implode(' - ')) ?: 'Frequency row',
                    'value' => $parts !== '' ? $parts : '-',
                ];
            })
            ->values();
    }

    protected static function normalizeSectionKey(?string $sectionKey): string
    {
        $sectionKey = (string) $sectionKey;

        return self::SECTION_KEY_ALIASES[$sectionKey] ?? $sectionKey;
    }

    protected static function filterSections(array $sections, array $selectedSections, array $selectedQuestionIds = []): array
    {
        if (empty($selectedSections)) {
            return $sections;
        }

        $selectedLookup = array_flip(array_map(
            fn (string $sectionKey): string => static::normalizeSectionKey($sectionKey),
            $selectedSections
        ));
        $questionLookup = empty($selectedQuestionIds) ? [] : array_flip($selectedQuestionIds);

        return array_values(array_filter(array_map(
            function (array $section) use ($selectedLookup, $questionLookup): ?array {
                if (! isset($selectedLookup[$section['key']])) {
                    return null;
                }

                if (! empty($questionLookup)) {
                    $section['rows'] = array_values(array_filter(
                        $section['rows'],
                        fn (array $row): bool => isset($questionLookup[$row['question_id'] ?? 0])
                    ));
                }

                return empty($section['rows']) ? null : $section;
            },
            $sections
        )));
    }

    protected static function rowHasPrintableValue(array $row): bool
    {
        if (($row['kind'] ?? null) === 'coverage_matrix') {
            return static::isPrintableValue($row['deductible'] ?? null)
                || static::isPrintableValue($row['percent'] ?? null);
        }

        return static::isPrintableValue($row['value'] ?? null);
    }

    protected static function isPrintableValue(mixed $value): bool
    {
        if ($value === 0 || $value === 0.0 || $value === '0') {
            return true;
        }

        $value = trim((string) $value);

        return $value !== '' && $value !== '-' && $value !== '- | -';
    }

    protected static function buildPanels(BillingWorkItem $workItem, array $state, ?VerificationFormSubmission $submission = null): array
    {
        $panels = [];

        $panels[] = [
            'title' => 'Request Snapshot',
            'items' => [
                ['label' => 'Reference', 'value' => $workItem->reference_number ?: '-'],
                ['label' => 'Status', 'value' => BillingWorkItem::STATUS_OPTIONS[$submission?->status ?? $workItem->normalized_status] ?? '-'],
                ['label' => 'Result', 'value' => app(VerificationResultService::class)->outcomeLabel($workItem, $submission)],
                ['label' => 'Priority', 'value' => BillingWorkItem::PRIORITY_OPTIONS[$workItem->priority] ?? '-'],
                ['label' => 'Assigned To', 'value' => $workItem->assignedTo?->name ?: 'Unassigned'],
                ['label' => 'Reviewer', 'value' => $workItem->reviewedBy?->name ?: '-'],
            ],
        ];

        $panels[] = [
            'title' => 'Patient & Insurance',
            'items' => [
                ['label' => 'Patient', 'value' => $state['vf_patient_full_name'] ?: '-'],
                ['label' => 'DOB', 'value' => static::displayValue($state['vf_patient_dob'] ?? null, 'date')],
                ['label' => 'Member ID', 'value' => $state['vf_patient_identifier'] ?: '-'],
                ['label' => 'Insurance', 'value' => $state['vf_insurance_provider_name'] ?: '-'],
                ['label' => 'Subscriber', 'value' => $state['vf_subscriber_name'] ?: '-'],
                ['label' => 'Relationship', 'value' => $state['vf_insured_relation'] ?: '-'],
            ],
            'notes' => [
                'label' => 'Quick Reference',
                'value' => $state['vf_quick_reference'] ?: '-',
            ],
        ];

        $notes = collect([
            ['label' => 'Verification Notes', 'value' => $state['vf_verification_notes'] ?: '-'],
            ['label' => 'Queue Notes', 'value' => $state['notes'] ?: '-'],
            ['label' => 'Internal Summary', 'value' => $state['internal_summary'] ?: '-'],
        ])->filter(fn (array $row): bool => filled($row['value']) && $row['value'] !== '-')->values()->all();

        if (! empty($notes)) {
            $panels[] = [
                'title' => 'Notes & Handoff',
                'items' => [],
                'rich' => $notes,
            ];
        }

        return $panels;
    }

    protected static function mapQuestionRow(VerificationFormQuestion $question, array $state): ?array
    {
        $field = static::resolveField($question);
        $value = static::extractValue($field, $state);

        if (static::normalizeSectionKey($question->section_key) === 'template_3_coverage_category' && filled($question->secondary_field_key)) {
            $deductible = static::displayValue($value, $question->input_type);
            $percent = static::displayValue(static::extractValue($question->secondary_field_key, $state), $question->secondary_input_type ?: 'percent');

            return [
                'question_id' => $question->id,
                'kind' => 'coverage_matrix',
                'label' => $question->prompt,
                'deductible' => $deductible,
                'percent' => $percent,
                'value' => trim("Deductible: {$deductible} | Coverage: {$percent}", ' |'),
            ];
        }

        return [
            'question_id' => $question->id,
            'kind' => 'standard',
            'label' => $question->prompt,
            'value' => static::displayValue($value, static::resolveInputType($question)),
        ];
    }

    protected static function resolveField(VerificationFormQuestion $question): ?string
    {
        if (! $question->is_builtin) {
            return 'custom_question_' . $question->id;
        }

        return match ($question->prompt) {
            'Clinic name' => 'context_clinic_name',
            default => $question->field_key,
        };
    }

    protected static function resolveInputType(VerificationFormQuestion $question): string
    {
        if (! $question->is_builtin) {
            return $question->input_type;
        }

        return match ($question->prompt) {
            'Is the provider in network with this plan?' => 'yes_no',
            default => $question->input_type,
        };
    }

    protected static function extractValue(?string $field, array $state): mixed
    {
        if (! filled($field)) {
            return null;
        }

        return $state[$field] ?? null;
    }

    protected static function displayValue(mixed $value, ?string $type = null): string
    {
        if ($value === 0 || $value === 0.0 || $value === '0') {
            return $type === 'currency' ? '$0.00' : ($type === 'percent' ? '0%' : '0');
        }

        if (blank($value)) {
            return '-';
        }

        return match ($type) {
            'date' => static::displayDate($value),
            'currency' => '$' . number_format((float) $value, 2),
            'percent' => number_format((float) $value, 0) . '%',
            'yes_no' => match ((string) $value) {
                '1', 'Yes', 'yes', 'true', 'True' => 'Yes',
                '0', 'No', 'no', 'false', 'False' => 'No',
                default => (string) $value,
            },
            default => trim((string) $value) !== '' ? (string) $value : '-',
        };
    }

    protected static function displayDate(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('M d, Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected static function formatDateForInput(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof \Illuminate\Support\Carbon || $value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function resolveNetworkStatus(?string $networkStatus, mixed $providerInNetwork): ?string
    {
        if (filled($networkStatus)) {
            return str_contains(strtolower($networkStatus), 'out') ? 'No' : (str_contains(strtolower($networkStatus), 'in') ? 'Yes' : $networkStatus);
        }

        if (is_bool($providerInNetwork)) {
            return $providerInNetwork ? 'Yes' : 'No';
        }

        return null;
    }

    protected static function buildQuickReference(BillingWorkItem $record, mixed $patient, mixed $policy, mixed $primaryPlan, mixed $provider): ?string
    {
        $parts = collect([
            $record->reference_number,
            $patient?->full_name,
            $policy?->insurance_company ?: $primaryPlan?->payer_name,
            $policy?->member_id ?: $primaryPlan?->member_id ?: $patient?->insurance_number,
            optional($record->appointment?->appointment_date)->format('M d, Y'),
            $provider?->display_name,
        ])->filter(fn ($value): bool => filled($value));

        return $parts->isNotEmpty() ? $parts->implode(' | ') : null;
    }

    protected static function buildInternalSummary(BillingWorkItem $record, mixed $patient, ?string $clinicDisplayName): ?string
    {
        $segments = collect([
            filled($patient?->full_name) ? 'Verification request for ' . $patient->full_name : null,
            filled($clinicDisplayName) ? 'Clinic: ' . $clinicDisplayName : null,
            optional($record->appointment?->appointment_date)->format('M d, Y') ? 'Appointment: ' . optional($record->appointment?->appointment_date)->format('M d, Y') : null,
            $record->priority ? 'Priority: ' . (BillingWorkItem::PRIORITY_OPTIONS[$record->priority] ?? str($record->priority)->headline()->toString()) : null,
        ])->filter(fn ($value): bool => filled($value));

        return $segments->isNotEmpty() ? $segments->implode(' | ') : null;
    }
}
