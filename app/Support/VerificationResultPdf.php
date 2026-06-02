<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\VerificationFormQuestion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class VerificationResultPdf
{
    public const OUTPUT_MODE_OPTIONS = [
        'standard' => 'Standard',
        'selected' => 'Current with Selected Output',
        'view' => 'View Form Output',
    ];

    protected const SECTION_ORDER = [
        'core_details',
        'coverage_matrix',
        'plan_provisions',
        'history',
        'frequency_diagnostic_preventative',
        'frequency_basic',
        'frequency_major',
        'frequency_orthodontics_benefit',
        'service_history',
        'verification_information',
    ];

    public static function fileName(BillingWorkItem $workItem, string $mode = 'standard'): string
    {
        $base = $workItem->reference_number ?: 'verification-result';

        return match ($mode) {
            'selected' => "{$base}-selected.pdf",
            'view' => "{$base}-view-form.pdf",
            default => "{$base}.pdf",
        };
    }

    public static function output(BillingWorkItem $workItem, string $mode = 'standard', array $selectedSections = [], array $selectedQuestionIds = []): string
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
            'verificationFormAnswers.question',
            'assignedTo',
            'reviewedBy',
            'activities.user',
            'notes.user',
            'attachments',
        ]);

        $state = static::buildState($workItem);
        $sections = static::buildSections($workItem, $state);
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

        if ($mode === 'selected') {
            $sections = static::filterSections($sections, $selectedSections, $selectedQuestionIds);
        }

        $view = match ($mode) {
            'selected' => 'pdf.verifications.show',
            'view' => 'pdf.verifications.view',
            default => 'pdf.verifications.standard',
        };

        return Pdf::loadView($view, [
            'workItem' => $workItem,
            'state' => $state,
            'summary' => static::buildSummary($workItem, $state),
            'sections' => $sections,
            'panels' => static::buildPanels($workItem, $state),
            'selectedSectionTitles' => collect($selectedSections)
                ->map(fn (string $key): string => VerificationFormQuestion::SECTION_OPTIONS[$key] ?? str($key)->headline()->toString())
                ->all(),
            'selectedQuestionTitles' => VerificationFormQuestion::query()
                ->whereIn('id', $selectedQuestionIds)
                ->orderBy('section_key')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('prompt')
                ->all(),
        ])
            ->setPaper('a4', $mode === 'selected' ? 'landscape' : 'portrait')
            ->output();
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
            'internal_summary' => $workItem->internal_summary ?: static::buildInternalSummary($workItem, $patient, $clinic?->clinic_name ?: $location?->location_name ?: $workItem->organization?->name),
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

    protected static function buildSummary(BillingWorkItem $workItem, array $state): array
    {
        return [
            'reference_number' => $workItem->reference_number,
            'title' => $workItem->title,
            'patient_name' => $state['vf_patient_full_name'] ?? '-',
            'clinic_name' => $state['context_clinic_name'] ?? '-',
            'status' => BillingWorkItem::STATUS_OPTIONS[$workItem->normalized_status] ?? str($workItem->normalized_status)->headline()->toString(),
            'result' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$workItem->outcome_status] ?? str($workItem->outcome_status)->headline()->toString(),
            'priority' => BillingWorkItem::PRIORITY_OPTIONS[$workItem->priority] ?? str($workItem->priority)->headline()->toString(),
            'insurance_name' => $state['vf_insurance_provider_name'] ?? '-',
            'appointment_date' => static::displayValue($state['vf_appointment_date'] ?? null, 'date'),
            'assigned_to' => $workItem->assignedTo?->name ?: 'Unassigned',
        ];
    }

    protected static function buildSections(BillingWorkItem $workItem, array $state): array
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
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key');

        $sections = [];

        foreach (self::SECTION_ORDER as $sectionKey) {
            $sectionQuestions = $questions->get($sectionKey, collect());

            if ($sectionQuestions->isEmpty()) {
                continue;
            }

            $rows = $sectionQuestions
                ->map(fn (VerificationFormQuestion $question): ?array => static::mapQuestionRow($question, $state))
                ->filter()
                ->values()
                ->all();

            if (empty($rows)) {
                continue;
            }

            $sections[] = [
                'key' => $sectionKey,
                'title' => VerificationFormQuestion::SECTION_OPTIONS[$sectionKey] ?? str($sectionKey)->headline()->toString(),
                'rows' => $rows,
            ];
        }

        return $sections;
    }

    protected static function filterSections(array $sections, array $selectedSections, array $selectedQuestionIds = []): array
    {
        if (empty($selectedSections)) {
            return $sections;
        }

        $selectedLookup = array_flip($selectedSections);
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

    protected static function buildPanels(BillingWorkItem $workItem, array $state): array
    {
        $panels = [];

        $panels[] = [
            'title' => 'Request Snapshot',
            'items' => [
                ['label' => 'Reference', 'value' => $workItem->reference_number ?: '-'],
                ['label' => 'Status', 'value' => BillingWorkItem::STATUS_OPTIONS[$workItem->normalized_status] ?? '-'],
                ['label' => 'Result', 'value' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$workItem->outcome_status] ?? '-'],
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

        if ($question->section_key === 'coverage_matrix' && filled($question->secondary_field_key)) {
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
