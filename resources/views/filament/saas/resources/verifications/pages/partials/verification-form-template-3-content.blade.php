@php
    $templateThreeBenefitGroups = collect([
        'General' => [],
        'Basic' => [],
        'Major' => [],
        'Orthodontics' => [],
    ]);

    foreach ($this->codeCoverageData as $coverageIndex => $coverageRow) {
        $category = strtolower(trim((string) ($coverageRow['category'] ?? '')));

        $benefitGroup = match (true) {
            str_contains($category, 'ortho') => 'Orthodontics',
            str_contains($category, 'prostho'),
            str_contains($category, 'implant'),
            str_contains($category, 'major') => 'Major',
            str_contains($category, 'restor'),
            str_contains($category, 'endo'),
            str_contains($category, 'perio'),
            str_contains($category, 'oral surg'),
            str_contains($category, 'basic') => 'Basic',
            default => 'General',
        };

        $groupRows = $templateThreeBenefitGroups->get($benefitGroup, []);
        $groupRows[] = ['index' => $coverageIndex, 'row' => $coverageRow];
        $templateThreeBenefitGroups->put($benefitGroup, $groupRows);
    }

    $templateThreeVisibleBenefitGroups = $templateThreeBenefitGroups
        ->filter(fn (array $benefitRows): bool => count($benefitRows) > 0);

    $templateThreePatientQuestions = $this->getTemplateThreeQuestionsForSection('template_3_patient_subscriber');
    $templateThreeInsuranceQuestions = $this->getTemplateThreeQuestionsForSection('template_3_insurance');
    $templateThreeMaximumQuestions = $this->getTemplateThreeQuestionsForSection('template_3_maximums_deductibles');
    $templateThreePlanProvisionQuestions = $this->getTemplateThreeQuestionsForSection('template_3_plan_provisions');
    $templateThreeServiceHistoryQuestions = $this->getTemplateThreeQuestionsForSection('template_3_service_history');
    $templateThreeVerificationSection = $this->getTemplateThreeVerificationInformationSection();
    $templateThreeSectionProgress = collect($this->getVerificationSectionProgress());
    $templateThreeContextRows = $this->getContextRows();
    $templateThreeSidebarBlocks = [
        'Practice' => $templateThreeContextRows['practice'] ?? [],
        'Provider' => [
            ['label' => 'Doctor', 'value' => $quickReference['provider_name'] ?? '-'],
            ['label' => 'Provider NPI', 'value' => $quickReference['provider_npi'] ?? '-'],
            ['label' => 'Insurance Phone', 'value' => $quickReference['phone'] ?? '-'],
        ],
        'Patient' => $templateThreeContextRows['patient'] ?? [],
    ];

    $templateThreeInput = 'width:100%;min-height:42px;border:1px solid #dce8e3;border-radius:12px;background:#fff;padding:10px 12px;font-size:14px;outline:none;color:#142e25;';
    $templateThreeReadonly = 'width:100%;min-height:42px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;padding:10px 12px;font-size:14px;font-weight:700;color:#334155;';
    $templateThreeFrequencyFieldLabels = array_merge(
        \App\Models\VerificationFormQuestion::FREQUENCY_BASE_RESPONSE_FIELDS,
        \App\Models\VerificationFormQuestion::FREQUENCY_CURRENT_OPTIONAL_FIELDS,
        \App\Models\VerificationFormQuestion::FREQUENCY_ADVANCED_OPTIONAL_FIELDS,
    );
    $templateThreeFrequencySelectFields = [
        'coverage_status' => ['' => 'Select status', 'Covered' => 'Covered', 'Not Covered' => 'Not Covered', 'Conditional' => 'Conditional'],
        'pre_auth_required' => ['' => 'Select pre-auth', 'Yes' => 'Yes', 'No' => 'No'],
        'downgrade_applies' => ['' => 'Select downgrade', 'Yes' => 'Yes', 'No' => 'No'],
    ];
    $templateThreeFrequencyTextareaFields = ['payment_guideline', 'notes'];
    $templateThreeFrequencyPlaceholders = [
        'coverage_percent' => '%',
        'frequency' => 'Frequency',
        'service_history' => 'No history / date',
        'age_limit' => 'Age limit',
        'waiting_period' => 'Waiting period',
        'pre_auth_details' => 'Pre-auth detail',
        'downgrade_to' => 'Downgrade detail',
        'payment_guideline' => 'Payment guideline or payer rule',
        'notes' => 'Additional notes',
    ];
    $annualMaximum = (float) (data_get($this->data, 'vf_annual_maximum') ?: 0);
    $annualRemaining = (float) (data_get($this->data, 'vf_annual_maximum_remaining') ?: 0);
    $individualDeductible = (float) (data_get($this->data, 'vf_individual_deductible') ?: 0);
    $individualRemaining = (float) (data_get($this->data, 'vf_individual_deductible_remaining') ?: 0);
    $familyDeductible = (float) (data_get($this->data, 'vf_family_deductible') ?: 0);
    $familyRemaining = (float) (data_get($this->data, 'vf_family_deductible_remaining') ?: 0);
    $insuranceCarrierOptions = $this->getInsuranceCarrierOptions();
    $templateThreeInsuranceGroups = [
        [
            ['Insurance Provider', 'vf_insurance_provider_name', 'text'],
            ['Group Number', 'vf_group_number', 'text'],
            ['Plan Type', 'vf_plan_type', 'select', [
                'PPO' => 'PPO',
                'DHMO' => 'DHMO',
                'Indemnity' => 'Indemnity',
            ]],
            ['Network Status', 'vf_network_status', 'select', [
                'Yes' => 'In Network',
                'No' => 'Out of Network',
            ]],
            ['Effective Date', 'vf_effective_date', 'date'],
            ['Future Termination Date', 'vf_future_termination_date', 'date'],
            ['Plan Renewal Month', 'vf_plan_renewal_month', 'text'],
        ],
        [
            ['Claims Address', 'vf_insurance_claim_mailing_address', 'text'],
            ['Payer ID', 'vf_payer_id', 'text'],
            ['Phone Number', 'vf_insurance_company_phone_number', 'text'],
            ['Fee Schedule', 'vf_fee_schedule', 'text'],
            ['Employer / Group Name', 'vf_group_name', 'text'],
        ],
    ];
    $templateThreeCountFilled = function (array $fields): int {
        return collect($fields)
            ->filter(fn ($field): bool => filled(data_get($this->data, $field)))
            ->count();
    };
    $templateThreeSectionCounts = [
        'patient' => [
            'completed' => $templateThreeCountFilled([
                'vf_patient_full_name',
                'vf_patient_dob',
                'vf_patient_identifier',
                'vf_insured_relation',
                'vf_subscriber_name',
                'vf_subscriber_dob',
                'vf_subscriber_id',
                'vf_coverage_role',
            ]),
            'total' => 8,
        ],
        'insurance' => [
            'completed' => $templateThreeCountFilled([
                'vf_insurance_provider_name',
                'vf_group_number',
                'vf_plan_type',
                'vf_network_status',
                'vf_effective_date',
                'vf_future_termination_date',
                'vf_plan_renewal_month',
                'vf_insurance_claim_mailing_address',
                'vf_payer_id',
                'vf_insurance_company_phone_number',
                'vf_fee_schedule',
                'vf_group_name',
            ]),
            'total' => 12,
        ],
        'maximums' => [
            'completed' => $templateThreeCountFilled([
                'vf_annual_maximum',
                'vf_annual_maximum_remaining',
                'vf_individual_deductible',
                'vf_individual_deductible_remaining',
                'vf_family_deductible',
                'vf_family_deductible_remaining',
            ]),
            'total' => 6,
        ],
        'service_history' => [
            'completed' => $templateThreeCountFilled([
                'vf_history_exams',
                'vf_history_prophylaxis',
                'vf_history_bitewings',
                'vf_history_full_mouth_xray',
                'vf_history_basic_or_major',
            ]),
            'total' => 5,
        ],
        'verification' => [
            'completed' => $templateThreeVerificationSection['completed'],
            'total' => $templateThreeVerificationSection['total'],
        ],
    ];
    $templateThreeCoverageCategoryRows = [
        ['Diagnostic & Preventive', 'vf_coverage_diagnostic_deductible_applies', 'vf_coverage_diagnostic'],
        ['Basic Restorative', 'vf_coverage_basic_restorative_deductible_applies', 'vf_coverage_basic_restorative'],
        ['Endodontics', 'vf_coverage_endodontics_deductible_applies', 'vf_coverage_endodontics'],
        ['Periodontics', 'vf_coverage_periodontics_deductible_applies', 'vf_coverage_periodontics'],
        ['Oral Surgery', 'vf_coverage_oral_surgery_deductible_applies', 'vf_coverage_oral_surgery'],
        ['Major Restorative', 'vf_coverage_major_restorative_deductible_applies', 'vf_coverage_major_restorative'],
        ['Orthodontics', 'vf_coverage_orthodontics_deductible_applies', 'vf_ortho_benefit'],
    ];
    $templateThreeCoverageCategoryCompleted = collect($templateThreeCoverageCategoryRows)->reduce(
        fn (int $carry, array $row): int => $carry + ((filled(data_get($this->data, $row[1])) || filled(data_get($this->data, $row[2]))) ? 1 : 0),
        0
    );
    $templateThreeCoverageCategoryTotal = count($templateThreeCoverageCategoryRows);
    $templateThreeFormatDateValue = static function ($value): ?string {
        if (blank($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    };

    $templateThreeStoredDates = [
        'vf_patient_dob' => $templateThreeFormatDateValue(optional($record->verificationProfile)->patient_dob ?: optional($record->patient)->dob),
        'vf_subscriber_dob' => $templateThreeFormatDateValue(
            optional($record->verificationProfile)->subscriber_dob
            ?: optional($record->insurancePolicy)->subscriber_dob
            ?: optional(optional($record->verificationPlanSnapshots)->first())->subscriber_dob
        ),
        'vf_effective_date' => $templateThreeFormatDateValue(
            optional($record->verificationProfile)->effective_date
            ?: optional($record->insurancePolicy)->effective_date
        ),
        'vf_future_termination_date' => $templateThreeFormatDateValue(
            optional($record->verificationProfile)->future_termination_date
            ?: optional($record->insurancePolicy)->termination_date
        ),
        'vf_verification_date' => $templateThreeFormatDateValue(optional($record->verificationProfile)->verification_date),
    ];
    $templateThreeDateValue = function (string $field) use ($templateThreeFormatDateValue, $templateThreeStoredDates): ?string {
        $relationship = strtolower(trim((string) data_get($this->data, 'vf_insured_relation')));

        $candidates = $field === 'vf_subscriber_dob' && $relationship === 'self'
            ? [
                data_get($this->data, 'vf_patient_dob'),
                $templateThreeStoredDates['vf_patient_dob'] ?? null,
                data_get($this->data, 'vf_subscriber_dob'),
                $templateThreeStoredDates['vf_subscriber_dob'] ?? null,
            ]
            : [
                data_get($this->data, $field),
                $templateThreeStoredDates[$field] ?? null,
            ];

        foreach ($candidates as $candidate) {
            $formatted = $templateThreeFormatDateValue($candidate);

            if (filled($formatted)) {
                return $formatted;
            }
        }

        return null;
    };
    $templateThreePlanProvisionVisibility = [
        'vf_waiting_periods' => $this->templateThreeFieldIsVisible('vf_waiting_periods', false),
        'vf_missing_tooth_clause' => $this->templateThreeFieldIsVisible('vf_missing_tooth_clause', false),
        'vf_crowns_paid_on' => $this->templateThreeFieldIsVisible('vf_crowns_paid_on', false),
        'vf_prosthetic_replacement_period' => $this->templateThreeFieldIsVisible('vf_prosthetic_replacement_period', false),
        'vf_coordination_of_benefits' => $this->templateThreeFieldIsVisible('vf_coordination_of_benefits', false),
        'vf_plan_provisions' => $this->templateThreeFieldIsVisible('vf_plan_provisions', false),
    ];
    $templateThreePlanProvisionFields = array_values(array_filter([
        $templateThreePlanProvisionVisibility['vf_missing_tooth_clause'] ? data_get($this->data, 'vf_missing_tooth_clause') : null,
        $templateThreePlanProvisionVisibility['vf_crowns_paid_on'] ? data_get($this->data, 'vf_crowns_paid_on') : null,
        $templateThreePlanProvisionVisibility['vf_prosthetic_replacement_period'] ? data_get($this->data, 'vf_prosthetic_replacement_period') : null,
        $templateThreePlanProvisionVisibility['vf_coordination_of_benefits'] ? data_get($this->data, 'vf_coordination_of_benefits') : null,
        $templateThreePlanProvisionVisibility['vf_plan_provisions'] ? data_get($this->data, 'vf_plan_provisions') : null,
        $templateThreePlanProvisionVisibility['vf_waiting_periods']
            ? ($this->waitingPeriodAnswer === 'yes'
                ? collect($this->waitingPeriodDetails ?? [])->contains(
                    fn ($detail): bool => filled(data_get($detail, 'period'))
                        || filled(data_get($detail, 'notes'))
                        || filled(data_get($detail, 'unit'))
                )
                : filled($this->waitingPeriodAnswer ?? null))
            : null,
    ], fn ($value): bool => $value !== null));
    $templateThreePlanProvisionCompleted = collect($templateThreePlanProvisionFields)
        ->filter(fn ($value): bool => filled($value))
        ->count();
    $templateThreePlanProvisionTotal = count($templateThreePlanProvisionFields);
    $templateThreeProgressSections = collect([
        [
            'label' => 'Patient & Subscriber',
            'completed' => $templateThreeSectionCounts['patient']['completed'],
            'total' => $templateThreeSectionCounts['patient']['total'],
        ],
        [
            'label' => 'Insurance Information',
            'completed' => $templateThreeSectionCounts['insurance']['completed'],
            'total' => $templateThreeSectionCounts['insurance']['total'],
        ],
        [
            'label' => 'Maximums & Deductibles',
            'completed' => $templateThreeSectionCounts['maximums']['completed'],
            'total' => $templateThreeSectionCounts['maximums']['total'],
        ],
        [
            'label' => 'Coverage Category',
            'completed' => $templateThreeCoverageCategoryCompleted,
            'total' => $templateThreeCoverageCategoryTotal,
        ],
        [
            'label' => 'Plan Provisions',
            'completed' => $templateThreePlanProvisionCompleted,
            'total' => $templateThreePlanProvisionTotal,
        ],
        [
            'label' => 'Service History',
            'completed' => $templateThreeSectionCounts['service_history']['completed'],
            'total' => $templateThreeSectionCounts['service_history']['total'],
        ],
        [
            'label' => 'Frequency & Percentage',
            'completed' => (int) ($codeCoverageSection['completed'] ?? 0),
            'total' => (int) ($codeCoverageSection['total'] ?? 0),
            'visible' => $templateThreeVisibleBenefitGroups->isNotEmpty(),
        ],
        [
            'label' => 'Verification Information',
            'completed' => $templateThreeSectionCounts['verification']['completed'],
            'total' => $templateThreeSectionCounts['verification']['total'],
        ],
    ])->filter(fn (array $section): bool => ($section['visible'] ?? true) === true)->values();
    $templateThreeProgressCompleted = (int) $templateThreeProgressSections->sum('completed');
    $templateThreeProgressTotal = max(1, (int) $templateThreeProgressSections->sum('total'));
    $templateThreeProgressPercent = (int) round(($templateThreeProgressCompleted / $templateThreeProgressTotal) * 100);
@endphp

<style>
    .uel2-page {
        --uel2-brand: #0b6b4f;
        --uel2-dark: #063f30;
        --uel2-soft: #eaf6f1;
        --uel2-line: #dce8e3;
        --uel2-muted: #6d7d77;
        display: flex;
        flex-direction: column;
        gap: 0;
        color: #142e25;
    }

    .uel2-shell {
        border: 1px solid #d6e6df;
        border-radius: 30px;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(247, 252, 249, 0.98) 100%);
        box-shadow: 0 18px 40px rgba(13, 58, 41, 0.08);
        overflow: hidden;
    }

    .uel2-shell__inner {
        padding: 20px;
        background:
            linear-gradient(180deg, rgba(238, 247, 243, 0.92) 0%, rgba(248, 252, 250, 0.96) 100%);
    }

    .uel2-layout {
        display: grid;
        grid-template-columns: 300px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }

    .uel2-sidebar {
        position: sticky;
        top: 24px;
        display: flex;
        flex-direction: column;
    }

    .uel2-content {
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-width: 0;
    }

    .uel2-sidebar-rail {
        border: 1px solid var(--uel2-line);
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff, #f9fcfb);
        box-shadow: 0 10px 24px rgba(13, 58, 41, 0.05);
        max-height: calc(100vh - 32px);
        overflow-y: auto;
        padding-right: 6px;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #edf2f7;
    }

    .uel2-sidebar-rail::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .uel2-sidebar-rail::-webkit-scrollbar-track {
        background: #edf2f7;
        border-radius: 999px;
    }

    .uel2-sidebar-rail::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 999px;
    }

    .uel2-sidebar-rail:hover::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .uel2-sidebar-rail__section {
        padding: 18px;
        border-bottom: 1px solid #e8f0ec;
    }

    .uel2-sidebar-rail__section:last-child {
        border-bottom: 0;
    }

    .uel2-sidebar-rail__title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }

    .uel2-sidebar-rail__title h2,
    .uel2-sidebar-rail__title h3 {
        margin: 0;
        color: var(--uel2-dark);
        font-weight: 900;
    }

    .uel2-sidebar-rail__title h2 { font-size: 18px; }
    .uel2-sidebar-rail__title h3 {
        font-size: 12px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--uel2-brand);
    }

    .uel2-sidebar-rail__copy {
        color: var(--uel2-muted);
        font-size: 12px;
        line-height: 1.55;
    }

    .uel2-section {
        overflow: hidden;
        border: 1px solid var(--uel2-line);
        border-radius: 22px;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(13, 58, 41, 0.05);
    }

    .uel2-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--uel2-line);
        background: linear-gradient(180deg, #ffffff, #f8fbfa);
    }

    .uel2-header h2, .uel2-subsection h3 {
        margin: 0;
        color: var(--uel2-dark);
        font-weight: 900;
    }

    .uel2-header h2 { font-size: 19px; }
    .uel2-header p { margin: 4px 0 0; color: var(--uel2-muted); font-size: 13px; }

    .uel2-pill {
        padding: 7px 11px;
        border-radius: 999px;
        background: var(--uel2-soft);
        color: var(--uel2-brand);
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .uel2-body { padding: 20px; }

    .uel2-quick-reference {
        display: grid;
        gap: 10px;
    }

    .uel2-progress-card {
        padding: 16px;
        border: 1px solid var(--uel2-line);
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff, #f7fbf9);
    }

    .uel2-progress-bar {
        overflow: hidden;
        height: 8px;
        border-radius: 999px;
        background: #e8f1ed;
    }

    .uel2-progress-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #2bb673, #0b6b4f);
    }

    .uel2-progress-total {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 10px;
        color: var(--uel2-muted);
        font-size: 12px;
        font-weight: 700;
    }

    .uel2-progress-list {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }

    .uel2-progress-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 14px;
        background: #f8fbfa;
        border: 1px solid #e4eeea;
    }

    .uel2-progress-item__meta {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .uel2-progress-item__dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        flex: 0 0 auto;
        background: #c8d7d1;
    }

    .uel2-progress-item--done .uel2-progress-item__dot {
        background: #2bb673;
        box-shadow: 0 0 0 3px rgba(43, 182, 115, 0.14);
    }

    .uel2-progress-item__label {
        min-width: 0;
        color: var(--uel2-dark);
        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;
    }

    .uel2-progress-item__count {
        color: var(--uel2-muted);
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .uel2-quick-reference__grid {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .uel2-quick-reference__item {
        padding: 0 0 12px;
    }

    .uel2-quick-reference__label {
        margin-bottom: 3px;
        color: var(--uel2-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .uel2-quick-reference__value {
        color: var(--uel2-dark);
        font-size: 14px;
        font-weight: 800;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .uel2-sidebar-blocks {
        display: grid;
        gap: 0;
    }

    .uel2-sidebar-block {
        padding: 14px 0;
        border-bottom: 1px solid #e8f0ec;
        background: transparent;
    }

    .uel2-sidebar-block:first-child {
        padding-top: 0;
    }

    .uel2-sidebar-block:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .uel2-sidebar-block__title {
        margin-bottom: 12px;
        color: var(--uel2-brand);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .uel2-sidebar-block__rows {
        display: grid;
        gap: 10px;
    }

    .uel2-sidebar-block__row {
        display: grid;
        gap: 2px;
    }

    .uel2-sidebar-block__label {
        color: var(--uel2-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .uel2-sidebar-block__value {
        color: var(--uel2-dark);
        font-size: 14px;
        font-weight: 800;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .uel2-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 15px;
    }

    .uel2-field label {
        display: block;
        margin-bottom: 7px;
        color: var(--uel2-muted);
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .uel2-field textarea {
        min-height: 96px;
        resize: vertical;
    }

    .uel2-wide { grid-column: 1 / -1; }
    .uel2-half { grid-column: span 2; }

    .uel2-insurance-groups {
        display: grid;
        gap: 16px;
    }

    .uel2-insurance-group {
        padding: 16px;
        border: 1px solid var(--uel2-line);
        border-radius: 18px;
        background: #fbfdfc;
    }

    .uel2-subsection {
        margin-top: 16px;
        padding: 16px;
        border: 1px solid var(--uel2-line);
        border-radius: 18px;
        background: #fbfdfc;
    }

    .uel2-subsection h3 {
        margin-bottom: 14px;
        font-size: 15px;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .uel2-subsection__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .uel2-subsection__header h3 {
        margin-bottom: 0;
    }

    .uel2-table {
        width: 100%;
        border: 1px solid var(--uel2-line);
        border-spacing: 0;
        border-collapse: separate;
        table-layout: fixed;
        border-radius: 16px;
        overflow: hidden;
    }

    .uel2-table th {
        padding: 13px 14px;
        border-bottom: 1px solid var(--uel2-line);
        background: #f6faf8;
        color: #50655d;
        text-align: left;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .uel2-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #edf3f0;
        vertical-align: middle;
        font-size: 14px;
        overflow-wrap: anywhere;
    }

    .uel2-table tr:last-child td { border-bottom: 0; }

    .uel2-table input, .uel2-table select, .uel2-table textarea {
        width: 100%;
        min-height: 38px;
        padding: 8px 10px;
        border: 1px solid var(--uel2-line);
        border-radius: 10px;
        background: #ffffff;
        color: #142e25;
        font-size: 13px;
    }

    .uel2-managed-questions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px dashed var(--uel2-line);
    }

    .uel2-managed-question {
        display: grid;
        gap: 12px;
        padding: 15px;
        border: 1px solid var(--uel2-line);
        border-radius: 16px;
        background: #fbfdfc;
    }

    .uel2-question-help {
        margin: -2px 0 8px;
        color: var(--uel2-muted);
        font-size: 12px;
        line-height: 1.55;
    }

    @media (max-width: 1050px) {
        .uel2-shell__inner {
            padding: 16px;
        }
        .uel2-layout { grid-template-columns: minmax(0, 1fr); }
        .uel2-sidebar {
            position: static;
        }
        .uel2-sidebar-rail { max-height: none; padding-right: 0; }
        .uel2-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 720px) {
        .uel2-grid { grid-template-columns: 1fr; }
        .uel2-managed-questions { grid-template-columns: 1fr; }
        .uel2-half, .uel2-wide { grid-column: 1; }
        .uel2-header { align-items: flex-start; }
        .uel2-pill { align-self: flex-start; }
        .uel2-subsection__header {
            align-items: flex-start;
            flex-direction: column;
        }
        .uel2-sidebar-rail__title {
            align-items: flex-start;
            flex-direction: column;
        }
        .uel2-progress-total {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }
        .uel2-progress-item {
            align-items: flex-start;
            flex-direction: column;
        }
        .uel2-progress-item__meta {
            width: 100%;
        }
        .uel2-progress-item__count {
            padding-left: 20px;
        }
        .uel2-table, .uel2-table thead, .uel2-table tbody, .uel2-table tr, .uel2-table th, .uel2-table td {
            display: block;
            width: 100%;
        }
        .uel2-table thead { display: none; }
        .uel2-table tr { padding: 10px; border-bottom: 1px solid var(--uel2-line); }
        .uel2-table td { padding: 8px 0; border: 0; }
        .uel2-table td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 5px;
            color: var(--uel2-muted);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .07em;
            text-transform: uppercase;
        }
    }
</style>

<div class="uel2-page">
    <section class="uel2-shell">
        <div class="uel2-shell__inner">
            <div class="uel2-layout">
                <aside class="uel2-sidebar">
                    <div class="uel2-sidebar-rail">
                <section class="uel2-sidebar-rail__section">
                    <div class="uel2-sidebar-rail__title">
                        <h3>Quick Reference</h3>
                    </div>
                    <div class="uel2-quick-reference">
                        <div class="uel2-quick-reference__grid">
                            @foreach ([
                                ['Patient Name', $quickReference['patient'] ?? '-'],
                                ['Patient DOB', $quickReference['dob'] ?? '-'],
                                ['Member ID', $quickReference['member_id'] ?? '-'],
                                ['Relationship', $quickReference['relationship'] ?? '-'],
                                ['Subscriber Name', $quickReference['subscriber_name'] ?? '-'],
                                ['Subscriber DOB', $quickReference['subscriber_dob'] ?? '-'],
                                ['Coverage Role', $quickReference['coverage_role'] ?? '-'],
                                ['Insurance / TPA', $quickReference['insurance_name'] ?? '-'],
                                ['Insurance / TPA Phone', $quickReference['phone'] ?? '-'],
                                ['Group Number', $quickReference['group_number'] ?? '-'],
                                ['Appointment Date', $quickReference['appointment_date'] ?? '-'],
                                ['Doctor Name', $quickReference['provider_name'] ?? '-'],
                                ['Provider NPI', $quickReference['provider_npi'] ?? '-'],
                            ] as [$quickReferenceLabel, $quickReferenceValue])
                                <div class="uel2-quick-reference__item">
                                    <div class="uel2-quick-reference__label">{{ $quickReferenceLabel }}</div>
                                    <div class="uel2-quick-reference__value">{{ filled($quickReferenceValue) ? $quickReferenceValue : '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="uel2-sidebar-rail__section">
                    <div class="uel2-sidebar-rail__title">
                        <div>
                            <h2>Verification Progress</h2>
                            <div class="uel2-sidebar-rail__copy">Track completion across the workup</div>
                        </div>
                        <span class="uel2-pill">{{ $templateThreeProgressCompleted }}/{{ $templateThreeProgressSections->sum('total') }}</span>
                    </div>
                    <div class="uel2-progress-card">
                        <div class="uel2-progress-bar">
                            <span style="width: {{ min(100, max(0, $templateThreeProgressPercent)) }}%;"></span>
                        </div>
                        <div class="uel2-progress-total">
                            <span>{{ $templateThreeProgressPercent }}% complete</span>
                            <span>{{ $templateThreeProgressCompleted }} / {{ $templateThreeProgressSections->sum('total') }} fields</span>
                        </div>
                    </div>

                    <div class="uel2-progress-list">
                        @foreach ($templateThreeProgressSections as $templateThreeProgressItem)
                            @php
                                $templateThreeProgressDone = (int) ($templateThreeProgressItem['completed'] ?? 0) >= (int) ($templateThreeProgressItem['total'] ?? 0)
                                    && (int) ($templateThreeProgressItem['total'] ?? 0) > 0;
                            @endphp
                            <div class="uel2-progress-item {{ $templateThreeProgressDone ? 'uel2-progress-item--done' : '' }}">
                                <div class="uel2-progress-item__meta">
                                    <span class="uel2-progress-item__dot"></span>
                                    <span class="uel2-progress-item__label">{{ $templateThreeProgressItem['label'] ?? 'Section' }}</span>
                                </div>
                                <span class="uel2-progress-item__count">{{ (int) ($templateThreeProgressItem['completed'] ?? 0) }}/{{ (int) ($templateThreeProgressItem['total'] ?? 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="uel2-sidebar-rail__section">
                    <div class="uel2-sidebar-blocks">
                        @foreach ($templateThreeSidebarBlocks as $templateThreeBlockTitle => $templateThreeBlockRows)
                            <div class="uel2-sidebar-block">
                                <div class="uel2-sidebar-block__title">{{ $templateThreeBlockTitle }}</div>
                                <div class="uel2-sidebar-block__rows">
                                    @foreach ($templateThreeBlockRows as $templateThreeBlockRow)
                                        <div class="uel2-sidebar-block__row">
                                            <div class="uel2-sidebar-block__label">{{ $templateThreeBlockRow['label'] ?? '' }}</div>
                                            <div class="uel2-sidebar-block__value">{{ filled($templateThreeBlockRow['value'] ?? null) ? $templateThreeBlockRow['value'] : '-' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
                    </div>
                </aside>

                <div class="uel2-content">

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Patient & Subscriber Information</h2><p>Core eligibility identifiers</p></div>
            <span class="uel2-pill">{{ $templateThreeSectionCounts['patient']['completed'] }}/{{ $templateThreeSectionCounts['patient']['total'] }} Completed</span>
        </div>
        <div class="uel2-body uel2-grid">
            @foreach ([
                ['Patient Name', 'vf_patient_full_name', 'text'],
                ['Date of Birth', 'vf_patient_dob', 'date'],
                ['Member ID', 'vf_patient_identifier', 'text'],
                ['Relationship', 'vf_insured_relation', 'select', [
                    'dependent' => 'Dependent',
                    'self' => 'Self',
                    'spouse' => 'Spouse',
                ]],
                ['Subscriber Name', 'vf_subscriber_name', 'text'],
                ['Subscriber DOB', 'vf_subscriber_dob', 'date'],
                ['Subscriber ID', 'vf_subscriber_id', 'text'],
                ['COB', 'vf_coverage_role', 'select', [
                    'No COB' => 'No COB',
                    'Primary' => 'Primary',
                    'Secondary' => 'Secondary',
                    'Unknown' => 'Unknown',
                ]],
            ] as $patientField)
                @php
                    [$label, $field, $type] = $patientField;
                    $options = $patientField[3] ?? [];
                @endphp
                <div class="uel2-field">
                    <label>{{ $label }}</label>
                    @if ($type === 'select')
                        <select
                            @if ($field === 'vf_insured_relation')
                                wire:model.live="data.{{ $field }}"
                            @else
                                wire:model.blur="data.{{ $field }}"
                            @endif
                            style="{{ $templateThreeInput }}"
                        >
                            <option value="">Select</option>
                            @foreach ($options as $value => $optionLabel)
                                <option value="{{ $value }}">{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'date')
                        @php
                            $templateThreeRelationship = strtolower(trim((string) data_get($this->data, 'vf_insured_relation')));
                            $templateThreeResolvedDateValue = $templateThreeDateValue($field);
                        @endphp
                        <input
                            type="date"
                            wire:key="template3-patient-date-{{ $field }}-{{ $templateThreeResolvedDateValue ?: 'blank' }}"
                            @if ($field === 'vf_patient_dob')
                                wire:change="$set('data.vf_patient_dob', $event.target.value)"
                            @elseif ($field === 'vf_subscriber_dob' && $templateThreeRelationship !== 'self')
                                wire:change="$set('data.vf_subscriber_dob', $event.target.value)"
                            @else
                                wire:change="$set('data.{{ $field }}', $event.target.value)"
                            @endif
                            value="{{ $templateThreeResolvedDateValue }}"
                            @if ($field === 'vf_subscriber_dob' && $templateThreeRelationship === 'self') readonly @endif
                            style="{{ $templateThreeInput }}"
                        >
                    @else
                        <input
                            type="{{ $type }}"
                            @if (in_array($field, ['vf_patient_full_name', 'vf_subscriber_name'], true))
                                wire:model.live="data.{{ $field }}"
                            @else
                                wire:model.blur="data.{{ $field }}"
                            @endif
                            @if ($field === 'vf_subscriber_name' && strtolower(trim((string) data_get($this->data, 'vf_insured_relation'))) === 'self') readonly @endif
                            style="{{ $templateThreeInput }}"
                        >
                    @endif
                </div>
            @endforeach
        </div>
        @php
            $templateThreeRelationship = strtolower(trim((string) data_get($this->data, 'vf_insured_relation')));
        @endphp
        @if (in_array($templateThreeRelationship, ['spouse', 'dependent'], true))
            <div class="uel2-body" style="padding-top:0;">
                <div style="border:1px solid #dbe8e2;border-radius:14px;background:#f8fbfa;padding:12px 14px;color:#5f7469;font-size:13px;line-height:1.55;">
                    Enter subscriber details separately because the policy holder is different from the patient.
                </div>
            </div>
        @endif
        @if (! empty($templateThreePatientQuestions))
            <div class="uel2-body" style="padding-top:0;">
                @include('filament.saas.resources.verifications.pages.partials.template-3-managed-questions', [
                    'questions' => $templateThreePatientQuestions,
                ])
            </div>
        @endif
    </section>

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Insurance Information</h2><p>Carrier, plan, network, and payer details</p></div>
            <span class="uel2-pill">{{ $templateThreeSectionCounts['insurance']['completed'] }}/{{ $templateThreeSectionCounts['insurance']['total'] }} Completed</span>
        </div>
        <div class="uel2-body uel2-insurance-groups">
            @foreach ($templateThreeInsuranceGroups as $insuranceGroup)
                <div class="uel2-insurance-group uel2-grid">
                @foreach ($insuranceGroup as $insuranceField)
                @php
                    [$label, $field, $type] = $insuranceField;
                    $options = $insuranceField[3] ?? [];
                @endphp
                <div class="uel2-field {{ $field === 'vf_insurance_claim_mailing_address' ? 'uel2-half' : '' }}">
                    <label>{{ $label }}</label>
                    @if ($field === 'vf_insurance_provider_name')
                        <div style="display:flex;align-items:center;gap:8px;">
                            <select
                                wire:model.live="data.{{ $field }}"
                                style="{{ $templateThreeInput }};min-width:0;flex:1 1 auto;appearance:auto;"
                            >
                                <option value="">Select insurance</option>
                                @if (filled(data_get($this->data, $field)) && ! array_key_exists((string) data_get($this->data, $field), $insuranceCarrierOptions))
                                    <option value="{{ data_get($this->data, $field) }}">{{ data_get($this->data, $field) }}</option>
                                @endif
                                @foreach ($insuranceCarrierOptions as $value => $optionLabel)
                                    <option value="{{ $value }}">{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                            @if ($this->canAddInsuranceCarrier())
                                <button
                                    type="button"
                                    wire:click="openAddInsuranceModal"
                                    title="Add insurance not listed"
                                    aria-label="Add insurance"
                                    style="display:inline-flex;flex:0 0 42px;width:42px;height:42px;align-items:center;justify-content:center;border:1px solid #b8d4c9;border-radius:12px;background:#eaf6f1;color:#0b6b4f;font-size:22px;font-weight:800;cursor:pointer;"
                                >
                                    +
                                </button>
                            @endif
                        </div>
                    @elseif ($type === 'select')
                        <select
                            wire:model.blur="data.{{ $field }}"
                            style="{{ $templateThreeInput }}"
                        >
                            <option value="">Select</option>
                            @if (filled(data_get($this->data, $field)) && ! array_key_exists((string) data_get($this->data, $field), $options))
                                <option value="{{ data_get($this->data, $field) }}">{{ data_get($this->data, $field) }}</option>
                            @endif
                            @foreach ($options as $value => $optionLabel)
                                <option value="{{ $value }}">{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($field === 'vf_fee_schedule')
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input
                                type="text"
                                wire:model.blur="data.{{ $field }}"
                                style="{{ $templateThreeInput }};min-width:0;flex:1 1 auto;"
                            >
                            @if (filled($feeScheduleReference['url'] ?? null))
                                @php
                                    $templateThreeFeeSchedulePayload = json_encode([
                                        'url' => $feeScheduleReference['url'],
                                        'name' => $feeScheduleReference['name'],
                                        'label' => 'Fee Schedule Reference',
                                        'description' => 'Review the current fee schedule reference without leaving the verification workflow.',
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                                @endphp
                                <button
                                    type="button"
                                    onclick='openReferenceViewerModal({!! $templateThreeFeeSchedulePayload !!})'
                                    title="{{ $feeScheduleReference['name'] }}"
                                    aria-label="View fee schedule reference"
                                    style="display:inline-flex;flex:0 0 42px;width:42px;height:42px;align-items:center;justify-content:center;border:1px solid #b8d4c9;border-radius:12px;background:#ffffff;color:#0b6b4f;font-size:18px;font-weight:900;cursor:pointer;"
                                >
                                    &#9432;
                                </button>
                            @else
                                <button
                                    type="button"
                                    title="No fee schedule reference added"
                                    aria-label="No fee schedule reference added"
                                    disabled
                                    style="display:inline-flex;flex:0 0 42px;width:42px;height:42px;align-items:center;justify-content:center;border:1px solid #dbe4ee;border-radius:12px;background:#f8fafc;color:#94a3b8;font-size:18px;font-weight:900;cursor:not-allowed;opacity:.9;"
                                >
                                    &#9432;
                                </button>
                            @endif
                        </div>
                    @else
                        @if ($type === 'date')
                            @php
                                $templateThreeInsuranceDateValue = $templateThreeDateValue($field);
                            @endphp
                            <input
                                type="date"
                                wire:key="template3-insurance-date-{{ $field }}-{{ $templateThreeInsuranceDateValue ?: 'blank' }}"
                                wire:change="$set('data.{{ $field }}', $event.target.value)"
                                value="{{ $templateThreeInsuranceDateValue }}"
                                style="{{ $templateThreeInput }}"
                            >
                        @else
                            <input
                                type="{{ $type }}"
                                wire:model.blur="data.{{ $field }}"
                                @if ($field === 'vf_plan_renewal_month') placeholder="MM/YYYY" inputmode="numeric" @endif
                                style="{{ $templateThreeInput }}"
                            >
                        @endif
                    @endif
                </div>
                @endforeach
                </div>
            @endforeach
        </div>
        @if (! empty($templateThreeInsuranceQuestions))
            <div class="uel2-body" style="padding-top:0;">
                @include('filament.saas.resources.verifications.pages.partials.template-3-managed-questions', [
                    'questions' => $templateThreeInsuranceQuestions,
                ])
            </div>
        @endif
    </section>

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Maximums & Deductibles</h2><p>Annual maximum, remaining maximum, and deductible status</p></div>
            <span class="uel2-pill">{{ $templateThreeSectionCounts['maximums']['completed'] }}/{{ $templateThreeSectionCounts['maximums']['total'] }} Completed</span>
        </div>
        <div class="uel2-body">
            <div class="uel2-grid">
                <div class="uel2-field"><label>Annual Maximum on the Plan?</label><input type="number" step="0.01" wire:model.blur="data.vf_annual_maximum" style="{{ $templateThreeInput }}"></div>
                @if ($this->templateThreeFieldIsVisible('vf_annual_maximum_used_display', false))
                    <div class="uel2-field"><label>Annual Maximum Used?</label><div style="{{ $templateThreeReadonly }}">${{ number_format(max(0, $annualMaximum - $annualRemaining), 2) }}</div></div>
                @endif
                <div class="uel2-field"><label>Annual Maximum Remaining?</label><input type="number" step="0.01" wire:model.blur="data.vf_annual_maximum_remaining" style="{{ $templateThreeInput }}"></div>
            </div>

            <div class="uel2-subsection">
                <h3>Individual Deductible</h3>
                <div class="uel2-grid">
                    <div class="uel2-field"><label>Annual Deductible - Individual</label><input type="number" step="0.01" wire:model.blur="data.vf_individual_deductible" style="{{ $templateThreeInput }}"></div>
                    @if ($this->templateThreeFieldIsVisible('vf_individual_deductible_met_display', false))
                        <div class="uel2-field"><label>Deductible Met - Individual</label><div style="{{ $templateThreeReadonly }}">${{ number_format(max(0, $individualDeductible - $individualRemaining), 2) }}</div></div>
                    @endif
                    <div class="uel2-field"><label>Individual Deductible Remaining</label><input type="number" step="0.01" wire:model.blur="data.vf_individual_deductible_remaining" style="{{ $templateThreeInput }}"></div>
                </div>
            </div>

            <div class="uel2-subsection">
                <h3>Family Deductible</h3>
                <div class="uel2-grid">
                    <div class="uel2-field"><label>Annual Deductible - Family</label><input type="number" step="0.01" wire:model.blur="data.vf_family_deductible" style="{{ $templateThreeInput }}"></div>
                    @if ($this->templateThreeFieldIsVisible('vf_family_deductible_met_display', false))
                        <div class="uel2-field"><label>Deductible Met - Family</label><div style="{{ $templateThreeReadonly }}">${{ number_format(max(0, $familyDeductible - $familyRemaining), 2) }}</div></div>
                    @endif
                    <div class="uel2-field"><label>Family Deductible Remaining</label><input type="number" step="0.01" wire:model.blur="data.vf_family_deductible_remaining" style="{{ $templateThreeInput }}"></div>
                </div>
            </div>

            @include('filament.saas.resources.verifications.pages.partials.template-3-managed-questions', [
                'questions' => $templateThreeMaximumQuestions,
            ])

            <div class="uel2-subsection">
                <div class="uel2-subsection__header">
                    <h3>Deductible & Coverage Category</h3>
                    <span class="uel2-pill">{{ $templateThreeCoverageCategoryCompleted }}/{{ $templateThreeCoverageCategoryTotal }} Completed</span>
                </div>
                <table class="uel2-table">
                    <thead><tr><th>Category</th><th>DED Applied?</th><th>Category %</th></tr></thead>
                    <tbody>
                        @foreach ($templateThreeCoverageCategoryRows as [$label, $deductibleField, $coverageField])
                            <tr>
                                <td data-label="Category"><b>{{ $label }}</b></td>
                                <td data-label="DED Applied?"><select wire:model.blur="data.{{ $deductibleField }}"><option value="">Select</option><option>Yes</option><option>No</option></select></td>
                                <td data-label="Category %"><input wire:model.blur="data.{{ $coverageField }}" placeholder="Coverage"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="uel2-field" style="margin-top:14px;">
                    <label>Coverage Notes</label>
                    <textarea wire:model.blur="data.vf_deductible_applies_notes" placeholder="Add note" style="{{ $templateThreeInput }}"></textarea>
                </div>
            </div>

            <div class="uel2-subsection">
                <div class="uel2-subsection__header">
                    <h3>Plan Provisions</h3>
                    <span class="uel2-pill">{{ $templateThreePlanProvisionCompleted }}/{{ $templateThreePlanProvisionTotal }} Completed</span>
                </div>
                <table class="uel2-table">
                    <thead><tr><th style="width:68%;">Question</th><th style="width:32%;" aria-label="Response"></th></tr></thead>
                    <tbody>
                        @if ($templateThreePlanProvisionVisibility['vf_waiting_periods'])
                            <tr>
                                <td data-label="Question">
                                    <b>Is there any Waiting Period on this plan?</b>
                                    <div style="margin-top:4px;color:#6d7d77;font-size:12px;">If Yes, waiting period details will appear below.</div>
                                </td>
                                <td data-label="Response">
                                    <select wire:model.live="waitingPeriodAnswer">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                    </select>
                                </td>
                            </tr>
                        @endif
                        @if ($templateThreePlanProvisionVisibility['vf_waiting_periods'] && $this->waitingPeriodAnswer === 'yes')
                            <tr>
                                <td colspan="2" style="padding:14px;">
                                    <div style="padding:16px;border:1px solid #bfe3d5;border-radius:16px;background:#f7fcfa;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
                                            <strong style="color:#063f30;font-size:15px;">Waiting Period Details</strong>
                                            <span class="uel2-pill">Shown only when answer is Yes</span>
                                        </div>
                                        <table class="uel2-table">
                                            <thead>
                                                <tr>
                                                    <th>Service Category</th>
                                                    <th>Waiting Period</th>
                                                    <th>Unit</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($this->waitingPeriodDetails as $waitingIndex => $waitingRow)
                                                    <tr wire:key="waiting-period-{{ $waitingIndex }}">
                                                        <td data-label="Service Category"><b>{{ $waitingRow['category'] }}</b></td>
                                                        <td data-label="Waiting Period">
                                                            <input type="number" min="0" wire:model.blur="waitingPeriodDetails.{{ $waitingIndex }}.period" placeholder="0">
                                                        </td>
                                                        <td data-label="Unit">
                                                            <select wire:model.blur="waitingPeriodDetails.{{ $waitingIndex }}.unit">
                                                                <option value="Months">Months</option>
                                                                <option value="Years">Years</option>
                                                                <option value="None">None</option>
                                                            </select>
                                                        </td>
                                                        <td data-label="Notes">
                                                            <input wire:model.blur="waitingPeriodDetails.{{ $waitingIndex }}.notes" placeholder="Details">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                        @if ($templateThreePlanProvisionVisibility['vf_missing_tooth_clause'])
                            <tr>
                                <td data-label="Question"><b>Missing Tooth Clause</b></td>
                                <td data-label="Response">
                                    <select wire:model.blur="data.vf_missing_tooth_clause">
                                        <option value="">Select</option>
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </td>
                            </tr>
                        @endif
                        @if ($templateThreePlanProvisionVisibility['vf_crowns_paid_on'])
                            <tr>
                                <td data-label="Question"><b>Crowns are paid on Prep Date or Seat Date?</b></td>
                                <td data-label="Response">
                                    <select wire:model.blur="data.vf_crowns_paid_on">
                                        <option value="">Select</option>
                                        <option value="Prep">Prep</option>
                                        <option value="Seat">Seat</option>
                                        <option value="Either-Or">Either-Or</option>
                                    </select>
                                </td>
                            </tr>
                        @endif
                        @if ($templateThreePlanProvisionVisibility['vf_prosthetic_replacement_period'])
                            <tr>
                                <td data-label="Question"><b>Prosthetic Replacement Year / Month</b></td>
                                <td data-label="Response">
                                    <input wire:model.blur="data.vf_prosthetic_replacement_period" placeholder="MM/YYYY or replacement period">
                                </td>
                            </tr>
                        @endif
                        @if ($templateThreePlanProvisionVisibility['vf_coordination_of_benefits'])
                            <tr>
                                <td data-label="Question"><b>Coordination of Benefits</b></td>
                                <td data-label="Response">
                                    <select wire:model.blur="data.vf_coordination_of_benefits">
                                        <option value="">Select</option>
                                        <option value="Standard">Standard</option>
                                        <option value="Non-Dup">Non-Dup</option>
                                        <option value="Birthday Rule">Birthday Rule</option>
                                        <option value="No COB">No COB</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                @if ($templateThreePlanProvisionVisibility['vf_plan_provisions'])
                    <div class="uel2-field" style="margin-top:14px;">
                        <label>Plan Provision Notes</label>
                        <textarea wire:model.blur="data.vf_plan_provisions" placeholder="Add any other plan provision note" style="{{ $templateThreeInput }}"></textarea>
                    </div>
                @endif
                @include('filament.saas.resources.verifications.pages.partials.template-3-managed-questions', [
                    'questions' => $templateThreePlanProvisionQuestions,
                ])
            </div>
        </div>
    </section>

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Service History</h2><p>Last service dates and next eligibility</p></div>
            <span class="uel2-pill">{{ $templateThreeSectionCounts['service_history']['completed'] }}/{{ $templateThreeSectionCounts['service_history']['total'] }} Completed</span>
        </div>
        <div class="uel2-body">
            <table class="uel2-table">
                <thead><tr><th>Service</th><th>Specific Code / Service History / Date</th></tr></thead>
                <tbody>
                    @foreach ([
                        ['Exams', 'vf_history_exams', 'e.g., D0120 - 01/15/2026'],
                        ['Prophylaxis', 'vf_history_prophylaxis', 'e.g., D1110 - 01/15/2026'],
                        ['Bitewings', 'vf_history_bitewings', 'e.g., D0274 - 01/15/2026'],
                        ['Full Mouth X-Ray / Panoramic X-Ray', 'vf_history_full_mouth_xray', 'e.g., D0210 or D0330 - 01/15/2026'],
                    ] as [$label, $field, $placeholder])
                        <tr><td data-label="Service"><b>{{ $label }}</b></td><td data-label="History"><input wire:model.blur="data.{{ $field }}" placeholder="{{ $placeholder }}"></td></tr>
                    @endforeach
                </tbody>
            </table>
            <div class="uel2-field" style="margin-top:16px;">
                <label>Other Major History Affecting Eligibility</label>
                <textarea wire:model.blur="data.vf_history_basic_or_major" placeholder="Add any major history that may affect eligibility, frequency, downgrade, replacement, or waiting-period decisions." style="{{ $templateThreeInput }}"></textarea>
            </div>
            @include('filament.saas.resources.verifications.pages.partials.template-3-managed-questions', [
                'questions' => $templateThreeServiceHistoryQuestions,
            ])
        </div>
    </section>

    @if ($templateThreeVisibleBenefitGroups->isNotEmpty())
        <section class="uel2-section">
            <div class="uel2-header">
                <div><h2>Frequency and Percentage</h2><p>Code-level coverage configured through the clinic template builder</p></div>
                <span class="uel2-pill">{{ $codeCoverageSection['completed'] }}/{{ $codeCoverageSection['total'] }} Completed</span>
            </div>
            <div class="uel2-body">
                @foreach ($templateThreeVisibleBenefitGroups as $benefitGroupName => $benefitRows)
                    <div class="uel2-subsection" style="{{ $loop->first ? 'margin-top:0;' : '' }}">
                        <h3>{{ $benefitGroupName }}</h3>
                        <table class="uel2-table">
                            <thead><tr><th style="width: 140px;">Code</th><th>Description</th><th style="width: 140px;">%</th><th style="width: 220px;">Frequency</th><th style="width: 48%;">Response Details</th></tr></thead>
                            <tbody>
                                @foreach ($benefitRows as $benefitRow)
                                    @php
                                        $rowIndex = $benefitRow['index'];
                                        $row = $benefitRow['row'];
                                        $responseMode = data_get($this->codeCoverageData, $rowIndex . '.frequency_response_mode') ?: data_get($row, 'frequency_response_mode', 'current');
                                        $configuredFields = data_get($this->codeCoverageData, $rowIndex . '.frequency_response_fields');
                                        $configuredFields = is_array($configuredFields)
                                            ? $configuredFields
                                            : data_get($row, 'frequency_response_fields');
                                        $configuredFields = is_array($configuredFields)
                                            ? $configuredFields
                                            : \App\Models\VerificationFormQuestion::defaultFrequencyResponseFields($responseMode);
                                        $fieldOrder = [
                                            'coverage_status',
                                            'service_history',
                                            'pre_auth_required',
                                            'pre_auth_details',
                                            'downgrade_applies',
                                            'downgrade_to',
                                            'age_limit',
                                            'waiting_period',
                                            'payment_guideline',
                                            'notes',
                                        ];
                                        $detailFields = collect($configuredFields)
                                            ->reject(fn (string $field): bool => in_array($field, ['coverage_percent', 'frequency'], true))
                                            ->sortBy(fn (string $field): int => array_search($field, $fieldOrder, true) === false ? 999 : array_search($field, $fieldOrder, true))
                                            ->values()
                                            ->all();
                                        $preAuthRequiredForRow = data_get($this->codeCoverageData, $rowIndex . '.pre_auth_required') === 'Yes';
                                        $downgradeAppliesForRow = data_get($this->codeCoverageData, $rowIndex . '.downgrade_applies') === 'Yes';

                                        if (in_array('pre_auth_required', $detailFields, true) && ! in_array('pre_auth_details', $detailFields, true)) {
                                            $detailFields[] = 'pre_auth_details';
                                        }

                                        if (in_array('downgrade_applies', $detailFields, true) && ! in_array('downgrade_to', $detailFields, true)) {
                                            $detailFields[] = 'downgrade_to';
                                        }

                                        $detailFields = collect($detailFields)
                                            ->sortBy(fn (string $field): int => array_search($field, $fieldOrder, true) === false ? 999 : array_search($field, $fieldOrder, true))
                                            ->values()
                                            ->all();
                                    @endphp
                                        <tr>
                                            <td data-label="Code"><b>{{ data_get($this->codeCoverageData, $rowIndex . '.code') }}</b></td>
                                            <td data-label="Description">{{ data_get($this->codeCoverageData, $rowIndex . '.description') }}</td>
                                            <td data-label="%"><input type="number" min="0" max="100" wire:model.blur="codeCoverageData.{{ $rowIndex }}.coverage_percent" placeholder="%"></td>
                                            <td data-label="Frequency"><input wire:model.blur="codeCoverageData.{{ $rowIndex }}.frequency" placeholder="Frequency"></td>
                                            <td data-label="Response Details">
                                                @if (empty($detailFields))
                                                    <div style="border:1px dashed #dce8e3;border-radius:12px;background:#f8fafc;color:#64748b;padding:10px 12px;font-size:13px;font-weight:700;">
                                                        No extra response fields selected.
                                                    </div>
                                                @else
                                                    <div
                                                        x-data="{ preAuth: @js(data_get($this->codeCoverageData, $rowIndex . '.pre_auth_required')), downgrade: @js(data_get($this->codeCoverageData, $rowIndex . '.downgrade_applies')) }"
                                                        style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;"
                                                    >
                                                        @foreach ($detailFields as $field)
                                                            @php
                                                                $label = $templateThreeFrequencyFieldLabels[$field] ?? str($field)->headline()->toString();
                                                                $placeholder = $templateThreeFrequencyPlaceholders[$field] ?? $label;
                                                            @endphp
                                                            <div
                                                                @if ($field === 'pre_auth_details')
                                                                    x-show="preAuth === 'Yes'"
                                                                    x-cloak
                                                                @elseif ($field === 'downgrade_to')
                                                                    x-show="downgrade === 'Yes'"
                                                                    x-cloak
                                                                @endif
                                                            >
                                                                <label style="display:block;margin:0 0 5px;color:#50655d;font-size:10px;font-weight:900;letter-spacing:.07em;text-transform:uppercase;">{{ $label }}</label>
                                                                @if (isset($templateThreeFrequencySelectFields[$field]))
                                                                    <select
                                                                        @if ($field === 'pre_auth_required')
                                                                            x-model="preAuth"
                                                                            wire:model.live="codeCoverageData.{{ $rowIndex }}.{{ $field }}"
                                                                        @elseif ($field === 'downgrade_applies')
                                                                            x-model="downgrade"
                                                                            wire:model.live="codeCoverageData.{{ $rowIndex }}.{{ $field }}"
                                                                        @else
                                                                            wire:model.blur="codeCoverageData.{{ $rowIndex }}.{{ $field }}"
                                                                        @endif
                                                                    >
                                                                        @foreach ($templateThreeFrequencySelectFields[$field] as $optionValue => $optionLabel)
                                                                            <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @elseif (in_array($field, $templateThreeFrequencyTextareaFields, true))
                                                                    <textarea wire:model.blur="codeCoverageData.{{ $rowIndex }}.{{ $field }}" placeholder="{{ $placeholder }}" rows="2"></textarea>
                                                                @else
                                                                    <input wire:model.blur="codeCoverageData.{{ $rowIndex }}.{{ $field }}" placeholder="{{ $placeholder }}">
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Verification Information</h2><p>Representative, reference number, and final notes</p></div>
            <span class="uel2-pill">{{ $templateThreeSectionCounts['verification']['completed'] }}/{{ $templateThreeSectionCounts['verification']['total'] }} Completed</span>
        </div>
        <div class="uel2-body uel2-grid">
            @foreach ($templateThreeVerificationSection['rows'] as $question)
                @include('filament.saas.resources.verifications.pages.partials.template-3-verification-information-row', [
                    'question' => $question,
                    'templateThreeInput' => $templateThreeInput,
                    'templateThreeReadonly' => $templateThreeReadonly,
                ])
            @endforeach
        </div>
    </section>
                </div>
            </div>
        </div>
    </section>
</div>

@if ($this->showAddInsuranceModal)
    <div
        style="position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.58);backdrop-filter:blur(4px);"
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-insurance-title"
    >
        <div style="width:min(680px,100%);max-height:calc(100vh - 40px);overflow:auto;border:1px solid #dce8e3;border-radius:24px;background:#fff;box-shadow:0 28px 80px rgba(15,23,42,.28);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px;border-bottom:1px solid #e8efec;">
                <div>
                    <div style="margin-bottom:6px;color:#0b6b4f;font-size:11px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;">Insurance Directory</div>
                    <h3 id="add-insurance-title" style="margin:0;color:#0f172a;font-size:24px;font-weight:900;">Add Insurance</h3>
                    <p style="margin:7px 0 0;color:#64748b;font-size:13px;line-height:1.6;">Create the missing carrier and use it immediately in this verification.</p>
                </div>
                <button
                    type="button"
                    wire:click="closeAddInsuranceModal"
                    aria-label="Close"
                    style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:1px solid #dbe4ee;border-radius:999px;background:#fff;color:#475569;font-size:22px;cursor:pointer;"
                >
                    &times;
                </button>
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:22px;">
                <div class="uel2-field" style="grid-column:1/-1;">
                    <label>Insurance Name</label>
                    <input wire:model.blur="newInsuranceCarrier.insurance_name" placeholder="Enter insurance carrier name" style="{{ $templateThreeInput }}">
                    @error('newInsuranceCarrier.insurance_name')
                        <div style="margin-top:6px;color:#be123c;font-size:12px;font-weight:700;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="uel2-field">
                    <label>Payer ID</label>
                    <input wire:model.blur="newInsuranceCarrier.payer_id" placeholder="Enter payer ID" style="{{ $templateThreeInput }}">
                </div>
                <div class="uel2-field">
                    <label>Phone Number</label>
                    <input wire:model.blur="newInsuranceCarrier.payer_phone" placeholder="Enter payer phone" style="{{ $templateThreeInput }}">
                </div>
                <div class="uel2-field" style="grid-column:1/-1;">
                    <label>Claims Address</label>
                    <textarea wire:model.blur="newInsuranceCarrier.claims_address" placeholder="Enter claims mailing address" style="{{ $templateThreeInput }}"></textarea>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding:16px 22px;border-top:1px solid #e8efec;background:#fbfdfc;">
                <button
                    type="button"
                    wire:click="closeAddInsuranceModal"
                    style="padding:11px 18px;border:1px solid #dbe4ee;border-radius:12px;background:#fff;color:#475569;font-weight:800;cursor:pointer;"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="addInsuranceCarrier"
                    wire:loading.attr="disabled"
                    wire:target="addInsuranceCarrier"
                    style="padding:11px 18px;border:0;border-radius:12px;background:#0b6b4f;color:#fff;font-weight:900;cursor:pointer;"
                >
                    <span wire:loading.remove wire:target="addInsuranceCarrier">Add & Select</span>
                    <span wire:loading wire:target="addInsuranceCarrier">Adding...</span>
                </button>
            </div>
        </div>
    </div>
@endif
