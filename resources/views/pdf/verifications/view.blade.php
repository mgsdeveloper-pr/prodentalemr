<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $summary['reference_number'] }}</title>
    <style>
        @page { size: a4 portrait; margin: 20px 18px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 10px; line-height: 1.45; }
        h1, h2, h3, p { margin: 0; }
        .hero { border: 1px solid #dbe4ee; border-radius: 18px; overflow: hidden; background: linear-gradient(135deg, #0f172a 0%, #12263a 56%, #0f3a4a 100%); color: #fff; margin-bottom: 18px; }
        .hero-inner { padding: 18px 20px; }
        .eyebrow { font-size: 9px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #a5f3fc; }
        .title { margin-top: 8px; font-size: 22px; font-weight: 700; color: #fff; }
        .subtitle { margin-top: 8px; font-size: 11px; color: #cbd5e1; }
        .pill-grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin: 12px -10px 0; }
        .pill-card { border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; background: rgba(255,255,255,0.06); padding: 10px 12px; }
        .pill-label { font-size: 8px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #cbd5e1; }
        .pill-value { margin-top: 6px; font-size: 12px; font-weight: 700; color: #fff; }
        .panel { border: 1px solid #dbe4ee; border-radius: 16px; overflow: hidden; margin-bottom: 16px; background: #fff; }
        .panel-head { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; font-size: 13px; font-weight: 700; color: #0f172a; }
        .panel-body { padding: 12px 14px; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin: -10px; }
        .grid-card { border: 1px solid #e5e7eb; border-radius: 14px; background: #f8fafc; padding: 10px 12px; }
        .grid-label { font-size: 8px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; }
        .grid-value { margin-top: 5px; font-size: 11px; font-weight: 700; color: #0f172a; }
        .note { border: 1px solid #e5e7eb; border-radius: 14px; background: #f8fafc; padding: 12px; font-size: 10px; line-height: 1.65; color: #334155; white-space: pre-line; }
        .section { border-top: 1px dashed #cbd5e1; padding-top: 12px; margin-top: 12px; }
        .section:first-child { border-top: none; padding-top: 0; margin-top: 0; }
        .section-title { margin-bottom: 8px; font-size: 11px; font-weight: 700; color: #0f172a; }
        .rows { width: 100%; border-collapse: collapse; }
        .rows td { padding: 8px 0; border-bottom: 1px solid #eef2f7; vertical-align: top; }
        .rows tr:last-child td { border-bottom: none; }
        .label { width: 42%; font-size: 9px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; }
        .value { font-size: 10px; color: #0f172a; }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-inner">
            <div class="eyebrow">Verification Details</div>
            <div class="title">{{ $summary['reference_number'] }}</div>
            <div class="subtitle">{{ $summary['patient_name'] }} | {{ $summary['insurance_name'] }} | {{ $summary['clinic_name'] }}</div>
            <table class="pill-grid">
                <tr>
                    <td><div class="pill-card"><div class="pill-label">Status</div><div class="pill-value">{{ $summary['status'] }}</div></div></td>
                    <td><div class="pill-card"><div class="pill-label">Result</div><div class="pill-value">{{ $summary['result'] }}</div></div></td>
                    <td><div class="pill-card"><div class="pill-label">Priority</div><div class="pill-value">{{ $summary['priority'] }}</div></div></td>
                    <td><div class="pill-card"><div class="pill-label">Assigned To</div><div class="pill-value">{{ $summary['assigned_to'] }}</div></div></td>
                </tr>
            </table>
        </div>
    </div>

    @foreach ($panels as $panel)
        <div class="panel">
            <div class="panel-head">{{ $panel['title'] }}</div>
            <div class="panel-body">
                @if (!empty($panel['items']))
                    <table class="grid">
                        @foreach (array_chunk($panel['items'], 2) as $itemRow)
                            <tr>
                                @foreach ($itemRow as $item)
                                    <td width="50%">
                                        <div class="grid-card">
                                            <div class="grid-label">{{ $item['label'] }}</div>
                                            <div class="grid-value">{{ $item['value'] }}</div>
                                        </div>
                                    </td>
                                @endforeach
                                @if (count($itemRow) === 1)
                                    <td width="50%"></td>
                                @endif
                            </tr>
                        @endforeach
                    </table>
                @endif

                @if (!empty($panel['notes']))
                    <div class="section">
                        <div class="section-title">{{ $panel['notes']['label'] }}</div>
                        <div class="note">{{ $panel['notes']['value'] }}</div>
                    </div>
                @endif

                @if (!empty($panel['rich']))
                    <div class="section">
                        @foreach ($panel['rich'] as $block)
                            <div style="margin-bottom: 10px;">
                                <div class="section-title">{{ $block['label'] }}</div>
                                <div class="note">{{ $block['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    @foreach ($sections as $section)
        <div class="panel">
            <div class="panel-head">{{ $section['title'] }}</div>
            <div class="panel-body">
                <table class="rows" width="100%">
                    @foreach ($section['rows'] as $row)
                        <tr>
                            <td class="label">{{ $row['label'] }}</td>
                            <td class="value">{{ $row['kind'] === 'coverage_matrix' ? (($row['deductible'] ?? '-') . ' | ' . ($row['percent'] ?? '-')) : $row['value'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endforeach
</body>
</html>
