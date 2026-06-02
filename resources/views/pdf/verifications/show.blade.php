<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $summary['reference_number'] }}</title>
    <style>
        @page { size: a4 landscape; margin: 7px 7px 8px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 8px; line-height: 1.02; }
        table { width: 100%; border-collapse: collapse; }
        .layout td { vertical-align: top; }
        .title-table td { border: 1px solid #111827; padding: 2px 4px; font-weight: bold; }
        .title-label { width: 68%; background: #fff7ed; }
        .title-value { width: 32%; text-align: center; background: #ffedd5; }
        .section-bar { background: #f59e0b; color: #111827; font-weight: bold; text-align: center; }
        .sheet { table-layout: fixed; }
        .sheet td, .sheet th { border: 1px solid #111827; padding: 2px 3px; vertical-align: top; }
        .sheet th { background: #fff7ed; text-align: left; font-size: 7px; letter-spacing: 0.02em; text-transform: uppercase; }
        .label { width: 27%; font-weight: bold; font-size: 7.5px; line-height: 1.0; }
        .value { width: 23%; font-size: 7.5px; line-height: 1.0; }
        .stack { margin-top: 4px; }
        .plain { white-space: normal; word-break: break-word; overflow-wrap: anywhere; }
        .tiny { font-size: 7px; }
    </style>
</head>
<body>
    @php
        $sectionsByTitle = collect($sections)->keyBy('title');
        $leftRows = [
            ['type' => 'pair', 'label' => 'Clinic name', 'value' => $state['context_clinic_name'] ?: '-'],
            ['type' => 'pair', 'label' => 'Is the provider in network with this plan?', 'value' => $state['vf_network_status'] ?: '-'],
            ['type' => 'pair', 'label' => 'Appointment Date', 'value' => $summary['appointment_date']],
            ['type' => 'pair', 'label' => 'Patient Name, DOB', 'value' => trim(($state['vf_patient_full_name'] ?: '-') . (!empty($state['vf_patient_dob']) ? ',' . \Illuminate\Support\Carbon::parse($state['vf_patient_dob'])->format('m/d/Y') : ''))],
            ['type' => 'pair', 'label' => "Subscriber's Name, DOB", 'value' => trim(($state['vf_subscriber_name'] ?: '-') . (!empty($state['vf_subscriber_dob']) ? ',' . \Illuminate\Support\Carbon::parse($state['vf_subscriber_dob'])->format('m/d/Y') : ''))],
            ['type' => 'pair', 'label' => 'Relation of the PT with the Subscriber', 'value' => $state['vf_insured_relation'] ?: '-'],
            ['type' => 'pair', 'label' => 'Alternate ID#', 'value' => $state['vf_patient_identifier'] ?: '-'],
            ['type' => 'pair', 'label' => 'Insurance Name & Tel#', 'value' => trim(($state['vf_insurance_provider_name'] ?: '-') . (!empty($state['vf_insurance_company_phone_number']) ? ' | ' . $state['vf_insurance_company_phone_number'] : ''))],
            ['type' => 'pair', 'label' => 'Insurance Claim Mailing Address', 'value' => $state['vf_insurance_claim_mailing_address'] ?: '-'],
            ['type' => 'pair', 'label' => 'Electronic Payer ID#', 'value' => $state['vf_payer_id'] ?: '-'],
            ['type' => 'pair', 'label' => 'Effective Date', 'value' => !empty($state['vf_effective_date']) ? \Illuminate\Support\Carbon::parse($state['vf_effective_date'])->format('m/d/Y') : '-'],
            ['type' => 'pair', 'label' => 'Plan renewal month?', 'value' => $state['vf_plan_renewal_month'] ?: '-'],
            ['type' => 'pair', 'label' => 'Future Termination Date', 'value' => !empty($state['vf_future_termination_date']) ? \Illuminate\Support\Carbon::parse($state['vf_future_termination_date'])->format('m/d/Y') : '-'],
            ['type' => 'pair', 'label' => 'Employer / Group Name?', 'value' => $state['vf_group_name'] ?: '-'],
            ['type' => 'pair', 'label' => 'Group Number?', 'value' => $state['vf_group_number'] ?: '-'],
            ['type' => 'pair', 'label' => 'Which Fee Schedule shall we use?', 'value' => $state['vf_fee_schedule'] ?: '-'],
            ['type' => 'pair', 'label' => 'Annual Maximum on the plan?', 'value' => filled($state['vf_annual_maximum'] ?? null) ? '$' . number_format((float) $state['vf_annual_maximum'], 2) : '-'],
            ['type' => 'pair', 'label' => 'Annual Maximum Remaining?', 'value' => filled($state['vf_annual_maximum_remaining'] ?? null) ? '$' . number_format((float) $state['vf_annual_maximum_remaining'], 2) : '-'],
            ['type' => 'pair', 'label' => 'Annual Deductible (Individual | Family)?', 'value' => (filled($state['vf_individual_deductible'] ?? null) ? '$' . number_format((float) $state['vf_individual_deductible'], 2) : '-') . ' | ' . (filled($state['vf_family_deductible'] ?? null) ? '$' . number_format((float) $state['vf_family_deductible'], 2) : '-')],
            ['type' => 'pair', 'label' => 'Deductible met (Individual | Family)?', 'value' => (filled($state['vf_individual_deductible_remaining'] ?? null) ? '$' . number_format((float) $state['vf_individual_deductible_remaining'], 2) : '-') . ' | ' . (filled($state['vf_family_deductible_remaining'] ?? null) ? '$' . number_format((float) $state['vf_family_deductible_remaining'], 2) : '-')],
            ['type' => 'section', 'label' => 'Category', 'value' => 'DED Applied? Yes/No | Category %'],
        ];

        foreach (($sectionsByTitle->get('Category Coverage')['rows'] ?? []) as $row) {
            $leftRows[] = [
                'type' => 'pair',
                'label' => $row['label'],
                'value' => ($row['deductible'] ?? '-') . ' | ' . ($row['percent'] ?? '-'),
            ];
        }

        $leftRows[] = ['type' => 'section', 'label' => 'Plan Provisions', 'value' => ''];
        foreach (($sectionsByTitle->get('Plan Provisions')['rows'] ?? []) as $row) {
            $leftRows[] = ['type' => 'pair', 'label' => $row['label'], 'value' => $row['value']];
        }

        $leftRows[] = ['type' => 'section', 'label' => 'History', 'value' => ''];
        foreach (($sectionsByTitle->get('History')['rows'] ?? []) as $row) {
            $leftRows[] = ['type' => 'pair', 'label' => $row['label'], 'value' => $row['value']];
        }

        $leftRows[] = ['type' => 'section', 'label' => 'Additional Comments', 'value' => ''];
        $leftRows[] = ['type' => 'pair', 'label' => 'Comments', 'value' => $state['vf_verification_notes'] ?: $state['internal_summary'] ?: $state['notes'] ?: '-'];

        $leftRows[] = ['type' => 'section', 'label' => 'Verification Information', 'value' => ''];
        foreach (($sectionsByTitle->get('Verification Information')['rows'] ?? []) as $row) {
            $leftRows[] = ['type' => 'pair', 'label' => $row['label'], 'value' => $row['value']];
        }

        $rightRows = [];
        $rightSections = [
            'Diagnostic & Preventative' => $sectionsByTitle->get('Frequency & Percentage / Diagnostic & Preventative'),
            'Basic' => $sectionsByTitle->get('Frequency & Percentage / Basic'),
            'Major' => $sectionsByTitle->get('Frequency & Percentage / Major'),
            'Orthodontics benefit' => $sectionsByTitle->get('Frequency & Percentage / Orthodontics Benefit'),
        ];
        $rightRows[] = ['type' => 'section', 'label' => 'Frequency and Percentage', 'value' => ''];
        foreach ($rightSections as $title => $section) {
            if (empty($section)) {
                continue;
            }
            $rightRows[] = ['type' => 'section', 'label' => $title, 'value' => ''];
            foreach ($section['rows'] as $row) {
                $rightRows[] = ['type' => 'pair', 'label' => $row['label'], 'value' => $row['value']];
            }
        }

        $rowCount = max(count($leftRows), count($rightRows));
    @endphp

    <table class="title-table">
        <tr>
            <td class="title-label">Sample Full Form Frequncy and Percentage</td>
            <td class="title-value">{{ $state['context_clinic_name'] ?: $summary['clinic_name'] }}</td>
        </tr>
    </table>

    <table class="sheet stack">
        <tbody>
            @for($i = 0; $i < $rowCount; $i++)
                @php
                    $left = $leftRows[$i] ?? ['type' => 'pair', 'label' => '', 'value' => ''];
                    $right = $rightRows[$i] ?? ['type' => 'pair', 'label' => '', 'value' => ''];
                @endphp
                <tr>
                    @if($left['type'] === 'section')
                        <td colspan="2" class="section-bar">{{ $left['label'] }}@if(filled($left['value'])) <span class="tiny"> {{ $left['value'] }}</span> @endif</td>
                    @else
                        <td class="label">{{ $left['label'] }}</td>
                        <td class="value plain">{{ $left['value'] }}</td>
                    @endif

                    @if($right['type'] === 'section')
                        <td colspan="2" class="section-bar">{{ $right['label'] }}</td>
                    @else
                        <td class="label">{{ $right['label'] }}</td>
                        <td class="value plain">{{ $right['value'] }}</td>
                    @endif
                </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
