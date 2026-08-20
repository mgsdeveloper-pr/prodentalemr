<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $summary['reference_number'] }}</title>
    <style>
        @page { size: a4 landscape; margin: 7px; }
        body { font-family: DejaVu Sans, sans-serif; color: #102033; font-size: 6.9px; line-height: 1.02; }
        h1, h2, p { margin: 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .pdf-head { width: 100%; border-bottom: 1px solid #cbd8e2; margin-bottom: 3px; padding-bottom: 2px; }
        .clinic-title { font-size: 11.5px; font-weight: 700; line-height: 1; color: #102033; }
        .report-title { text-align: right; font-size: 10.5px; font-weight: 700; line-height: 1; color: #102033; }
        .subtle { color: #66758a; font-size: 6.8px; line-height: 1; }
        .metadata { border: 1px solid #aebdca; background: #f8fbfa; margin-bottom: 4px; }
        .metadata td { border-right: 1px solid #d2dde5; border-bottom: 1px solid #d2dde5; padding: 2px 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .metadata tr:last-child td { border-bottom: none; }
        .metadata td:last-child { border-right: none; }
        .meta-label { color: #516579; font-size: 6.2px; text-transform: uppercase; letter-spacing: .01em; }
        .meta-value { font-weight: 700; color: #102033; }
        .worksheet td { vertical-align: top; }
        .pane-cell { width: 50%; }
        .pane-pad-left { padding-right: 3px; }
        .pane-pad-right { padding-left: 3px; }
        .sheet { border: 1px solid #aebdca; }
        .sheet th { background: #dff3ee; color: #075f56; text-align: center; font-weight: 700; padding: 1.8px 3px; border-bottom: 1px solid #b8d8d2; }
        .sheet td { padding: 1.5px 3px; border-right: 1px solid #e1e8ee; border-bottom: 1px solid #e1e8ee; vertical-align: top; }
        .sheet tr:last-child td { border-bottom: none; }
        .sheet td:last-child { border-right: none; }
        .label { width: 60%; font-weight: 700; color: #102033; }
        .value { width: 40%; color: #334155; }
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
    $leftKeys = [
        'template_3_patient_subscriber',
        'template_3_insurance',
        'template_3_maximums_deductibles',
        'template_3_coverage_category',
        'template_3_plan_provisions',
    ];
    $leftSections = collect($sections)->filter(fn ($section) => in_array($section['key'], $leftKeys, true))->values();
    $rightSections = collect($sections)->reject(fn ($section) => in_array($section['key'], $leftKeys, true))->values();
    $renderSections = function ($sections): string {
        return collect($sections)->map(function (array $section): string {
            $rows = collect($section['rows'])->map(function (array $row): string {
            $value = ($row['kind'] ?? null) === 'coverage_matrix'
                ? (($row['deductible'] ?? '-') . ' | ' . ($row['percent'] ?? '-'))
                : ($row['value'] ?? '-');
            $valueClass = in_array($value, ['-', '- | -'], true) ? 'value empty' : 'value';

            return '<tr><td class="label">' . e($row['label'] ?? '-') . '</td><td class="' . $valueClass . '">' . e($value) . '</td></tr>';
            })->implode('');

            return '<tr><th colspan="2">' . e($section['title']) . '</th></tr>' . $rows;
        })->implode('');
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

<table class="worksheet">
    <tr>
        <td class="pane-cell pane-pad-left">
            <table class="sheet">{!! $renderSections($leftSections) !!}</table>
        </td>
        <td class="pane-cell pane-pad-right">
            <table class="sheet">{!! $renderSections($rightSections) !!}</table>
        </td>
    </tr>
</table>
</body>
</html>
