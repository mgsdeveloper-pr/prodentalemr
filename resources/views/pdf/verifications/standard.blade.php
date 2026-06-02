<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $summary['reference_number'] }}</title>
    <style>
        @page { size: a4 portrait; margin: 22px 20px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.45; }
        h1, h2, h3, p { margin: 0; }
        .header { margin-bottom: 20px; }
        .eyebrow { font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e; }
        .title { margin-top: 8px; font-size: 24px; font-weight: 700; color: #0f172a; }
        .subtitle { margin-top: 6px; font-size: 12px; color: #64748b; }
        .summary-grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin: 0 -10px 18px; }
        .summary-card { border: 1px solid #dbe4ee; border-radius: 14px; padding: 12px 14px; background: #f8fafc; }
        .summary-label { font-size: 9px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; }
        .summary-value { margin-top: 6px; font-size: 13px; font-weight: 700; color: #0f172a; }
        .section { border: 1px solid #dbe4ee; border-radius: 16px; margin-bottom: 16px; overflow: hidden; }
        .section-head { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 14px; font-size: 13px; font-weight: 700; color: #0f172a; }
        .rows { width: 100%; border-collapse: collapse; }
        .rows td { padding: 10px 14px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
        .rows tr:last-child td { border-bottom: none; }
        .label { width: 40%; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .value { font-size: 11px; color: #0f172a; }
        .matrix th, .matrix td { padding: 10px 12px; border-bottom: 1px solid #eef2f7; text-align: left; }
        .matrix th { background: #f8fafc; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .matrix tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">Verification Result</div>
        <div class="title">{{ $summary['reference_number'] }}</div>
        <div class="subtitle">{{ $summary['patient_name'] }} | {{ $summary['insurance_name'] }} | {{ $summary['clinic_name'] }}</div>
    </div>

    <table class="summary-grid">
        <tr>
            <td><div class="summary-card"><div class="summary-label">Status</div><div class="summary-value">{{ $summary['status'] }}</div></div></td>
            <td><div class="summary-card"><div class="summary-label">Result</div><div class="summary-value">{{ $summary['result'] }}</div></div></td>
            <td><div class="summary-card"><div class="summary-label">Priority</div><div class="summary-value">{{ $summary['priority'] }}</div></div></td>
            <td><div class="summary-card"><div class="summary-label">Assigned To</div><div class="summary-value">{{ $summary['assigned_to'] }}</div></div></td>
        </tr>
    </table>

    @foreach ($sections as $section)
        <div class="section">
            <div class="section-head">{{ $section['title'] }}</div>
            @if ($section['key'] === 'coverage_matrix')
                <table class="rows matrix" width="100%">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Deductible Applied</th>
                            <th>Coverage %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section['rows'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['deductible'] ?? '-' }}</td>
                                <td>{{ $row['percent'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <table class="rows" width="100%">
                    @foreach ($section['rows'] as $row)
                        <tr>
                            <td class="label">{{ $row['label'] }}</td>
                            <td class="value">{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    @endforeach
</body>
</html>
