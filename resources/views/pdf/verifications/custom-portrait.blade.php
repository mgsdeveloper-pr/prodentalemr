<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $summary['reference_number'] }}</title>
    <style>
        @page { size: a4 portrait; margin: 10px; }
        body { font-family: DejaVu Sans, sans-serif; color: #102033; font-size: 7.6px; line-height: 1.08; }
        h1, h2, p { margin: 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .pdf-head { width: 100%; border-bottom: 1.5px solid #d9e3ea; margin-bottom: 5px; padding-bottom: 4px; }
        .clinic-title { font-size: 13.5px; font-weight: 700; line-height: 1; color: #102033; }
        .report-title { text-align: right; font-size: 12.5px; font-weight: 700; line-height: 1; color: #102033; }
        .subtle { color: #66758a; font-size: 7.5px; line-height: 1; }
        .metadata { border: 1.2px solid #b8c6d1; background: #f8fbfa; margin-bottom: 6px; }
        .metadata td { border-right: 1px solid #d2dde5; border-bottom: 1px solid #d2dde5; padding: 3px 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .metadata tr:last-child td { border-bottom: none; }
        .metadata td:last-child { border-right: none; }
        .meta-label { color: #516579; font-size: 6.7px; text-transform: uppercase; letter-spacing: .02em; }
        .meta-value { font-weight: 700; color: #102033; }
        .section { margin-bottom: 4px; border: 1px solid #d2dde5; }
        .section-title { background: #dff3ee; color: #075f56; text-align: center; font-weight: 700; padding: 2.6px 4px; border-bottom: 1px solid #b8d8d2; }
        .compact { border: 1px solid #d2dde5; }
        .compact th { background: #dff3ee; color: #075f56; text-align: center; font-weight: 700; padding: 2.6px 4px; border-bottom: 1px solid #b8d8d2; }
        .compact td { padding: 2.4px 4px; border-right: 1px solid #edf2f5; border-bottom: 1px solid #edf2f5; vertical-align: top; }
        .compact td:nth-child(2),
        .compact td:nth-child(4) { color: #516579; }
        .compact td:nth-child(1),
        .compact td:nth-child(3) { font-weight: 700; color: #102033; }
        .compact td:last-child { border-right: none; }
        .rows td { padding: 2.4px 4px; border-bottom: 1px solid #edf2f5; vertical-align: top; }
        .rows tr:last-child td { border-bottom: none; }
        .label { width: 62%; font-weight: 700; color: #102033; }
        .value { width: 38%; color: #516579; }
        .empty { color: #8ca0af; }
    </style>
</head>
<body>
@php
    $formLabel = ($state['vf_form_type'] ?? 'full_form') === 'short_form' ? 'Short Form' : 'Full Form';
    $patientName = $state['vf_patient_full_name'] ?: $summary['patient_name'];
    $patientDob = filled($state['vf_patient_dob'] ?? null) ? \Illuminate\Support\Carbon::parse($state['vf_patient_dob'])->format('m/d/Y') : '-';
    $providerName = $workItem->provider?->display_name ?: $workItem->provider?->user?->name ?: '-';
    $insuranceName = $state['vf_insurance_provider_name'] ?: $summary['insurance_name'];
    $memberId = $state['vf_patient_identifier'] ?: $state['vf_subscriber_id'] ?: '-';
    $groupId = $state['vf_group_number'] ?: '-';
    $generatedDate = now()->format('M d, Y');
    $renderRow = function (array $row): array {
            $value = ($row['kind'] ?? null) === 'coverage_matrix'
                ? (($row['deductible'] ?? '-') . ' | ' . ($row['percent'] ?? '-'))
                : ($row['value'] ?? '-');

            return [
                'label' => $row['label'] ?? '-',
                'value' => $value,
            ];
    };
@endphp

<table class="pdf-head">
    <tr>
        <td><div class="clinic-title">{{ $summary['clinic_name'] }}</div></td>
        <td><div class="report-title">Insurance Verification</div></td>
    </tr>
    <tr>
        <td class="subtle">{{ $formLabel }} | {{ $patientName }} | {{ $summary['reference_number'] }}</td>
        <td class="subtle" style="text-align: right;">Generated {{ $generatedDate }}</td>
    </tr>
</table>

<table class="metadata">
    <tr>
        <td><span class="meta-label">Patient:</span> <span class="meta-value">{{ $patientName }}</span></td>
        <td><span class="meta-label">DOB:</span> <span class="meta-value">{{ $patientDob }}</span></td>
        <td><span class="meta-label">Provider:</span> <span class="meta-value">{{ $providerName }}</span></td>
    </tr>
    <tr>
        <td><span class="meta-label">Insurance:</span> <span class="meta-value">{{ $insuranceName }}</span></td>
        <td><span class="meta-label">Member ID:</span> <span class="meta-value">{{ $memberId }}</span></td>
        <td><span class="meta-label">Group ID:</span> <span class="meta-value">{{ $groupId }}</span></td>
    </tr>
</table>

<table class="compact">
    <tbody>
        @foreach ($sections as $section)
            <tr>
                <th colspan="4">{{ $section['title'] }}</th>
            </tr>
            @foreach (array_chunk($section['rows'], 2) as $rowPair)
                @php
                    $first = $renderRow($rowPair[0]);
                    $second = isset($rowPair[1]) ? $renderRow($rowPair[1]) : ['label' => '', 'value' => ''];
                @endphp
                <tr>
                    <td width="31%">{{ $first['label'] }}</td>
                    <td width="19%">{{ $first['value'] }}</td>
                    <td width="31%">{{ $second['label'] }}</td>
                    <td width="19%">{{ $second['value'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
</body>
</html>
