@php
    $templateTwoBenefitGroups = collect([
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

        $groupRows = $templateTwoBenefitGroups->get($benefitGroup, []);
        $groupRows[] = ['index' => $coverageIndex, 'row' => $coverageRow];
        $templateTwoBenefitGroups->put($benefitGroup, $groupRows);
    }
    $templateTwoInput = 'width:100%;min-height:42px;border:1px solid #dce8e3;border-radius:12px;background:#fff;padding:10px 12px;font-size:14px;outline:none;color:#142e25;';
    $templateTwoReadonly = 'width:100%;min-height:42px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;padding:10px 12px;font-size:14px;font-weight:700;color:#334155;';
    $annualMaximum = (float) (data_get($this->data, 'vf_annual_maximum') ?: 0);
    $annualRemaining = (float) (data_get($this->data, 'vf_annual_maximum_remaining') ?: 0);
    $individualDeductible = (float) (data_get($this->data, 'vf_individual_deductible') ?: 0);
    $individualRemaining = (float) (data_get($this->data, 'vf_individual_deductible_remaining') ?: 0);
    $familyDeductible = (float) (data_get($this->data, 'vf_family_deductible') ?: 0);
    $familyRemaining = (float) (data_get($this->data, 'vf_family_deductible_remaining') ?: 0);
    $insuranceCarrierOptions = $this->getInsuranceCarrierOptions();
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
        gap: 20px;
        color: #142e25;
    }

    .uel2-section {
        overflow: hidden;
        border: 1px solid var(--uel2-line);
        border-radius: 22px;
        background: #ffffff;
        box-shadow: 0 14px 34px rgba(13, 58, 41, 0.07);
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

    .uel2-actions {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 12px;
        padding: 18px 20px;
        border-top: 1px solid var(--uel2-line);
        background: #fbfdfc;
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
        .uel2-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 720px) {
        .uel2-grid { grid-template-columns: 1fr; }
        .uel2-managed-questions { grid-template-columns: 1fr; }
        .uel2-half, .uel2-wide { grid-column: 1; }
        .uel2-header { align-items: flex-start; }
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
    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Patient & Subscriber Information</h2><p>Core eligibility identifiers</p></div>
            <span class="uel2-pill">Patient Details</span>
        </div>
        <div class="uel2-body uel2-grid">
            @foreach ([
                ['Patient Name', 'vf_patient_full_name', 'text'],
                ['Date of Birth', 'vf_patient_dob', 'date'],
                ['Member ID', 'vf_patient_identifier', 'text'],
                ['Relationship', 'vf_insured_relation', 'select', [
                    'Dependent' => 'Dependent',
                    'Self' => 'Self',
                    'Spouse' => 'Spouse',
                ]],
                ['Subscriber Name', 'vf_subscriber_name', 'text'],
                ['Subscriber DOB', 'vf_subscriber_dob', 'date'],
                ['Subscriber ID', 'vf_subscriber_id', 'text'],
                ['COB', 'vf_cob', 'select', [
                    'No COB' => 'No COB',
                    'Primary' => 'Primary',
                    'Secondary' => 'Secondary',
                ]],
            ] as $patientField)
                @php
                    [$label, $field, $type] = $patientField;
                    $options = $patientField[3] ?? [];
                @endphp
                <div class="uel2-field">
                    <label>{{ $label }}</label>
                    @if ($type === 'select')
                        <select wire:model.blur="data.{{ $field }}" style="{{ $templateTwoInput }}">
                            <option value="">Select</option>
                            @foreach ($options as $value => $optionLabel)
                                <option value="{{ $value }}">{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $type }}" wire:model.blur="data.{{ $field }}" style="{{ $templateTwoInput }}">
                    @endif
                </div>
            @endforeach
        </div>
        <div class="uel2-body" style="padding-top:0;">
            @include('filament.saas.resources.verifications.pages.partials.template-2-managed-questions', [
                'questions' => $this->getTemplateTwoQuestionsForSection('template_2_patient_subscriber'),
            ])
        </div>
    </section>

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Insurance Information</h2><p>Carrier, plan, network, and payer details</p></div>
            <span class="uel2-pill">Insurance</span>
        </div>
        <div class="uel2-body uel2-grid">
            @foreach ([
                ['Insurance Provider', 'vf_insurance_provider_name', 'text'],
                ['Plan Type', 'vf_plan_type', 'text'],
                ['Payer ID', 'vf_payer_id', 'text'],
                ['Effective Date', 'vf_effective_date', 'date'],
                ['Claims Address', 'vf_insurance_claim_mailing_address', 'text'],
                ['Phone Number', 'vf_insurance_company_phone_number', 'text'],
                ['Network Status', 'vf_network_status', 'select', [
                    'Yes' => 'In Network',
                    'No' => 'Out of Network',
                ]],
                ['Fee Schedule', 'vf_fee_schedule', 'text'],
                ['Plan Renewal Month', 'vf_plan_renewal_month', 'text'],
                ['Future Termination Date', 'vf_future_termination_date', 'date'],
                ['Employer / Group Name', 'vf_group_name', 'text'],
                ['Group Number', 'vf_group_number', 'text'],
            ] as $insuranceField)
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
                                style="{{ $templateTwoInput }};min-width:0;flex:1 1 auto;appearance:auto;"
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
                        <select wire:model.blur="data.{{ $field }}" style="{{ $templateTwoInput }}">
                            <option value="">Select</option>
                            @foreach ($options as $value => $optionLabel)
                                <option value="{{ $value }}">{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($field === 'vf_fee_schedule')
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input
                                type="text"
                                wire:model.blur="data.{{ $field }}"
                                style="{{ $templateTwoInput }};min-width:0;flex:1 1 auto;"
                            >
                            @if (filled($feeScheduleReference['url'] ?? null))
                                @php
                                    $templateTwoFeeSchedulePayload = json_encode([
                                        'url' => $feeScheduleReference['url'],
                                        'name' => $feeScheduleReference['name'],
                                        'label' => 'Fee Schedule Reference',
                                        'description' => 'Review the current fee schedule reference without leaving the verification workflow.',
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                                @endphp
                                <button
                                    type="button"
                                    onclick='openReferenceViewerModal({!! $templateTwoFeeSchedulePayload !!})'
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
                        <input
                            type="{{ $type }}"
                            wire:model.blur="data.{{ $field }}"
                            @if ($field === 'vf_plan_renewal_month') placeholder="MM/YYYY" inputmode="numeric" @endif
                            style="{{ $templateTwoInput }}"
                        >
                    @endif
                </div>
            @endforeach
        </div>
        <div class="uel2-body" style="padding-top:0;">
            @include('filament.saas.resources.verifications.pages.partials.template-2-managed-questions', [
                'questions' => $this->getTemplateTwoQuestionsForSection('template_2_insurance'),
            ])
        </div>
    </section>

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Maximums & Deductibles</h2><p>Annual maximum, remaining maximum, and deductible status</p></div>
            <span class="uel2-pill">Individual / Family</span>
        </div>
        <div class="uel2-body">
            <div class="uel2-grid">
                <div class="uel2-field"><label>Annual Maximum on the Plan?</label><input type="number" step="0.01" wire:model.blur="data.vf_annual_maximum" style="{{ $templateTwoInput }}"></div>
                <div class="uel2-field"><label>Annual Maximum Used?</label><div style="{{ $templateTwoReadonly }}">${{ number_format(max(0, $annualMaximum - $annualRemaining), 2) }}</div></div>
                <div class="uel2-field"><label>Annual Maximum Remaining?</label><input type="number" step="0.01" wire:model.blur="data.vf_annual_maximum_remaining" style="{{ $templateTwoInput }}"></div>
            </div>

            <div class="uel2-subsection">
                <h3>Individual Deductible</h3>
                <div class="uel2-grid">
                    <div class="uel2-field"><label>Annual Deductible - Individual</label><input type="number" step="0.01" wire:model.blur="data.vf_individual_deductible" style="{{ $templateTwoInput }}"></div>
                    <div class="uel2-field"><label>Deductible Met - Individual</label><div style="{{ $templateTwoReadonly }}">${{ number_format(max(0, $individualDeductible - $individualRemaining), 2) }}</div></div>
                    <div class="uel2-field"><label>Individual Deductible Remaining</label><input type="number" step="0.01" wire:model.blur="data.vf_individual_deductible_remaining" style="{{ $templateTwoInput }}"></div>
                </div>
            </div>

            <div class="uel2-subsection">
                <h3>Family Deductible</h3>
                <div class="uel2-grid">
                    <div class="uel2-field"><label>Annual Deductible - Family</label><input type="number" step="0.01" wire:model.blur="data.vf_family_deductible" style="{{ $templateTwoInput }}"></div>
                    <div class="uel2-field"><label>Deductible Met - Family</label><div style="{{ $templateTwoReadonly }}">${{ number_format(max(0, $familyDeductible - $familyRemaining), 2) }}</div></div>
                    <div class="uel2-field"><label>Family Deductible Remaining</label><input type="number" step="0.01" wire:model.blur="data.vf_family_deductible_remaining" style="{{ $templateTwoInput }}"></div>
                </div>
            </div>

            @include('filament.saas.resources.verifications.pages.partials.template-2-managed-questions', [
                'questions' => $this->getTemplateTwoQuestionsForSection('template_2_maximums_deductibles'),
            ])

            <div class="uel2-subsection">
                <h3>Deductible & Coverage Category</h3>
                <table class="uel2-table">
                    <thead><tr><th>Category</th><th>DED Applied?</th><th>Category %</th></tr></thead>
                    <tbody>
                        @foreach ([
                            ['Diagnostic & Preventive', 'vf_coverage_diagnostic_deductible_applies', 'vf_coverage_diagnostic'],
                            ['Basic Restorative', 'vf_coverage_basic_restorative_deductible_applies', 'vf_coverage_basic_restorative'],
                            ['Endodontics', 'vf_coverage_endodontics_deductible_applies', 'vf_coverage_endodontics'],
                            ['Periodontics', 'vf_coverage_periodontics_deductible_applies', 'vf_coverage_periodontics'],
                            ['Oral Surgery', 'vf_coverage_oral_surgery_deductible_applies', 'vf_coverage_oral_surgery'],
                            ['Major Restorative', 'vf_coverage_major_restorative_deductible_applies', 'vf_coverage_major_restorative'],
                            ['Orthodontics', 'vf_coverage_orthodontics_deductible_applies', 'vf_ortho_benefit'],
                        ] as [$label, $deductibleField, $coverageField])
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
                    <textarea wire:model.blur="data.vf_deductible_applies_notes" placeholder="Add note" style="{{ $templateTwoInput }}"></textarea>
                </div>
            </div>

            <div class="uel2-subsection">
                <h3>Plan Provisions</h3>
                <table class="uel2-table">
                    <thead><tr><th style="width:68%;">Question</th><th style="width:32%;" aria-label="Response"></th></tr></thead>
                    <tbody>
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
                        @if ($this->waitingPeriodAnswer === 'yes')
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
                        <tr>
                            <td data-label="Question"><b>Prosthetic Replacement Year / Month</b></td>
                            <td data-label="Response">
                                <input wire:model.blur="data.vf_prosthetic_replacement_period" placeholder="MM/YYYY or replacement period">
                            </td>
                        </tr>
                        <tr>
                            <td data-label="Question"><b>Coordination of Benefits</b></td>
                            <td data-label="Response">
                                <select wire:model.blur="data.vf_cob">
                                    <option value="">Select</option>
                                    <option value="Standard">Standard</option>
                                    <option value="Non-Dup">Non-Dup</option>
                                    <option value="Birthday Rule">Birthday Rule</option>
                                    <option value="No COB">No COB</option>
                                    <option value="Other">Other</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="uel2-field" style="margin-top:14px;">
                    <label>Plan Provision Notes</label>
                    <textarea wire:model.blur="data.vf_plan_provisions" placeholder="Add any other plan provision note" style="{{ $templateTwoInput }}"></textarea>
                </div>
                @include('filament.saas.resources.verifications.pages.partials.template-2-managed-questions', [
                    'questions' => $this->getTemplateTwoQuestionsForSection('template_2_plan_provisions'),
                ])
            </div>
        </div>
    </section>

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Service History</h2><p>Last service dates and next eligibility</p></div>
            <span class="uel2-pill">Eligibility History</span>
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
                <textarea wire:model.blur="data.vf_history_basic_or_major" placeholder="Add any major history that may affect eligibility, frequency, downgrade, replacement, or waiting-period decisions." style="{{ $templateTwoInput }}"></textarea>
            </div>
            @include('filament.saas.resources.verifications.pages.partials.template-2-managed-questions', [
                'questions' => $this->getTemplateTwoQuestionsForSection('template_2_service_history'),
            ])
        </div>
    </section>

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Frequency and Percentage</h2><p>Code-level coverage configured through the clinic template builder</p></div>
            <span class="uel2-pill">{{ $codeCoverageSection['completed'] }}/{{ $codeCoverageSection['total'] }} Completed</span>
        </div>
        <div class="uel2-body">
            @foreach ($templateTwoBenefitGroups as $benefitGroupName => $benefitRows)
                <div class="uel2-subsection" style="{{ $loop->first ? 'margin-top:0;' : '' }}">
                    <h3>{{ $benefitGroupName }}</h3>
                    <table class="uel2-table">
                        <thead><tr><th>Code</th><th>Description</th><th>%</th><th>Frequency</th><th>Pre-Auth</th><th>Notes</th></tr></thead>
                        <tbody>
                            @forelse ($benefitRows as $benefitRow)
                                @php
                                    $rowIndex = $benefitRow['index'];
                                    $row = $benefitRow['row'];
                                @endphp
                                    <tr>
                                        <td data-label="Code"><b>{{ data_get($this->codeCoverageData, $rowIndex . '.code') }}</b></td>
                                        <td data-label="Description">{{ data_get($this->codeCoverageData, $rowIndex . '.description') }}</td>
                                        <td data-label="%"><input type="number" min="0" max="100" wire:model.blur="codeCoverageData.{{ $rowIndex }}.coverage_percent" placeholder="%"></td>
                                        <td data-label="Frequency"><input wire:model.blur="codeCoverageData.{{ $rowIndex }}.frequency" placeholder="Frequency"></td>
                                        <td data-label="Pre-Auth"><select wire:model.blur="codeCoverageData.{{ $rowIndex }}.pre_auth_required"><option value="">Select</option><option>Yes</option><option>No</option></select></td>
                                        <td data-label="Notes"><input wire:model.blur="codeCoverageData.{{ $rowIndex }}.notes" placeholder="Add note"></td>
                                    </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding:16px;color:#6d7d77;font-size:13px;">
                                        No questions have been added to this benefit group yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @include('filament.saas.resources.verifications.pages.partials.template-2-managed-questions', [
                        'questions' => $this->getTemplateTwoQuestionsForSection(match ($benefitGroupName) {
                            'Basic' => 'template_2_frequency_basic',
                            'Major' => 'template_2_frequency_major',
                            'Orthodontics' => 'template_2_frequency_orthodontics',
                            default => 'template_2_frequency_general',
                        }),
                    ])
                </div>
            @endforeach
        </div>
    </section>

    <section class="uel2-section">
        <div class="uel2-header">
            <div><h2>Verification Information</h2><p>Representative, reference number, and final notes</p></div>
            <span class="uel2-pill">Final Review</span>
        </div>
        <div class="uel2-body uel2-grid">
            <div class="uel2-field"><label>Reference Number</label><div style="{{ $templateTwoReadonly }}">{{ $record->reference_number }}</div></div>
            <div class="uel2-field"><label>Insurance Representative</label><input wire:model.blur="data.vf_insurance_representative_name" style="{{ $templateTwoInput }}"></div>
            <div class="uel2-field"><label>Verified By</label><div style="{{ $templateTwoReadonly }}">{{ data_get($this->data, 'vf_verified_by') ?: auth()->user()?->name ?: '-' }}</div></div>
            <div class="uel2-field"><label>Verification Date</label><div style="{{ $templateTwoReadonly }}">{{ data_get($this->data, 'vf_verification_date') ?: now()->format('Y-m-d') }}</div></div>
            <div class="uel2-field uel2-wide"><label>Additional Information</label><textarea wire:model.blur="data.vf_verification_notes" placeholder="Add final verification notes" style="{{ $templateTwoInput }}"></textarea></div>
        </div>
        <div class="uel2-body" style="padding-top:0;">
            @include('filament.saas.resources.verifications.pages.partials.template-2-managed-questions', [
                'questions' => $this->getTemplateTwoQuestionsForSection('template_2_verification_information'),
            ])
        </div>
        <div class="uel2-actions">
            @if ($canSubmitForm)
                @if ($this->auditReady)
                    <button type="submit" class="uel-button uel-button-primary" style="border:0;border-radius:12px;padding:11px 18px;background:#0b6b4f;color:#fff;font-weight:900;cursor:pointer;">{{ $this->getSaveButtonLabel() }}</button>
                @else
                    <button type="button" wire:click="auditVerification" style="border:1px solid #b8d4c9;border-radius:12px;padding:11px 18px;background:#eaf6f1;color:#0b6b4f;font-weight:900;cursor:pointer;">{{ $this->getSaveButtonLabel() }}</button>
                @endif
                @if ($canRequestClinicInfo)
                    <button type="button" onclick="openWorkflowModal('info-request-modal')" style="border:1px solid #fed7aa;border-radius:12px;padding:11px 18px;background:#fff7ed;color:#c2410c;font-weight:900;cursor:pointer;">Request to Clinic</button>
                @endif
                <button type="button" wire:click="saveAndBack" style="border:1px solid #dce8e3;border-radius:12px;padding:11px 18px;background:#fff;color:#334155;font-weight:900;cursor:pointer;">Back</button>
                <button type="button" onclick="if (! confirm('Clear the verification answers and reset this form?')) return false;" wire:click="clearVerificationForm" style="border:1px solid #fecdd3;border-radius:12px;padding:11px 18px;background:#fff1f2;color:#be123c;font-weight:900;cursor:pointer;">Clear Form</button>
            @endif
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
                    <input wire:model.blur="newInsuranceCarrier.insurance_name" placeholder="Enter insurance carrier name" style="{{ $templateTwoInput }}">
                    @error('newInsuranceCarrier.insurance_name')
                        <div style="margin-top:6px;color:#be123c;font-size:12px;font-weight:700;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="uel2-field">
                    <label>Payer ID</label>
                    <input wire:model.blur="newInsuranceCarrier.payer_id" placeholder="Enter payer ID" style="{{ $templateTwoInput }}">
                </div>
                <div class="uel2-field">
                    <label>Phone Number</label>
                    <input wire:model.blur="newInsuranceCarrier.payer_phone" placeholder="Enter payer phone" style="{{ $templateTwoInput }}">
                </div>
                <div class="uel2-field" style="grid-column:1/-1;">
                    <label>Claims Address</label>
                    <textarea wire:model.blur="newInsuranceCarrier.claims_address" placeholder="Enter claims mailing address" style="{{ $templateTwoInput }}"></textarea>
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
