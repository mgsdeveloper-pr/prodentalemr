<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Verification Audit Trail</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; line-height: 1.45; }
        h1, h2, h3 { margin: 0; }
        .header { margin-bottom: 18px; }
        .eyebrow { font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; color: #475569; margin-bottom: 8px; }
        .title { font-size: 26px; font-weight: 700; margin-bottom: 6px; }
        .subtitle { color: #64748b; }
        .section { margin-top: 20px; }
        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 10px; }
        .summary-grid { width: 100%; border-collapse: collapse; }
        .summary-grid td { width: 50%; border: 1px solid #dbe4ee; padding: 10px 12px; vertical-align: top; }
        .label { font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .value { font-size: 13px; font-weight: 700; color: #111827; }
        .timeline-item { border: 1px solid #dbe4ee; border-radius: 12px; padding: 12px; margin-bottom: 10px; }
        .timeline-head { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .timeline-head td { vertical-align: top; }
        .timeline-type { font-size: 13px; font-weight: 700; color: #111827; }
        .timeline-time { text-align: right; color: #64748b; font-size: 11px; }
        .timeline-desc { margin-bottom: 6px; color: #334155; }
        .timeline-details { white-space: pre-line; color: #475569; font-size: 11px; border-top: 1px solid #edf2f7; padding-top: 8px; margin-top: 8px; }
        .timeline-author { margin-top: 8px; color: #64748b; font-size: 11px; }
        table.submissions { width: 100%; border-collapse: collapse; }
        table.submissions th, table.submissions td { border: 1px solid #dbe4ee; padding: 8px 10px; text-align: left; vertical-align: top; }
        table.submissions th { background: #f8fafc; font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; color: #475569; }
        .change-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; margin-top: 8px; }
        .change-head { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .change-head td { vertical-align: top; }
        .change-label { font-size: 12px; font-weight: 700; color: #111827; }
        .change-group { text-align: right; color: #64748b; font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; }
        .change-grid { width: 100%; border-collapse: separate; border-spacing: 0 6px; }
        .change-grid td { width: 50%; vertical-align: top; }
        .change-box { border: 1px solid #dbe4ee; border-radius: 8px; padding: 8px; }
        .change-caption { font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .change-value { font-size: 11px; line-height: 1.5; color: #334155; white-space: pre-line; }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">Verification Audit Trail</div>
        <div class="title">{{ $workItem->reference_number }}</div>
        <div class="subtitle">Full request history from intake through workflow activity, submission versions, and closure state.</div>
    </div>

    <div class="section">
        <div class="section-title">Request Summary</div>
        <table class="summary-grid">
            <tr>
                <td><div class="label">Patient</div><div class="value">{{ $summary['patient'] }}</div></td>
                <td><div class="label">Appointment Date</div><div class="value">{{ $summary['appointment_date'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Organization</div><div class="value">{{ $summary['organization'] }}</div></td>
                <td><div class="label">Clinic</div><div class="value">{{ $summary['clinic'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Status</div><div class="value">{{ $summary['status'] }}</div></td>
                <td><div class="label">Outcome</div><div class="value">{{ $summary['outcome'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Priority</div><div class="value">{{ $summary['priority'] }}</div></td>
                <td><div class="label">Due At</div><div class="value">{{ $summary['due_at'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Assigned To</div><div class="value">{{ $summary['assigned_to'] }}</div></td>
                <td><div class="label">Reviewed By</div><div class="value">{{ $summary['reviewed_by'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Closed By</div><div class="value">{{ $summary['closed_by'] }}</div></td>
                <td><div class="label">Reference</div><div class="value">{{ $summary['reference'] }}</div></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Workflow Timeline</div>
        @forelse ($timeline as $entry)
            <div class="timeline-item">
                <table class="timeline-head">
                    <tr>
                        <td class="timeline-type">{{ $entry['type'] }}</td>
                        <td class="timeline-time">{{ $entry['created_at'] }}</td>
                    </tr>
                </table>
                <div class="timeline-desc">{{ $entry['description'] }}</div>
                @if (filled($entry['details']))
                    <div class="timeline-details">{{ $entry['details'] }}</div>
                @endif
                <div class="timeline-author">{{ $entry['author'] }}</div>
            </div>
        @empty
            <div class="timeline-item">No workflow activity recorded yet.</div>
        @endforelse
    </div>

    <div class="section">
        <div class="section-title">Saved Submission Versions</div>
        @forelse ($submissions as $submission)
            <div class="timeline-item">
                <table class="timeline-head">
                    <tr>
                        <td class="timeline-type">Submission v{{ $submission['version'] }}</td>
                        <td class="timeline-time">{{ $submission['submitted_at'] }}</td>
                    </tr>
                </table>
                <div class="timeline-desc">
                    {{ $submission['submitted_by'] }} · {{ $submission['panel'] }} · {{ $submission['status'] }} · {{ $submission['outcome'] }} · {{ $submission['priority'] }}
                </div>
                <div class="timeline-details">Profile fields captured: {{ $submission['profile_fields'] }} | Question answers stored: {{ $submission['answered_questions'] }}</div>

                @if (! empty($submission['changes']))
                    @foreach ($submission['changes'] as $change)
                        <div class="change-item">
                            <table class="change-head">
                                <tr>
                                    <td class="change-label">{{ $change['label'] }}</td>
                                    <td class="change-group">{{ $change['group'] }}</td>
                                </tr>
                            </table>
                            <table class="change-grid">
                                <tr>
                                    <td>
                                        <div class="change-box">
                                            <div class="change-caption">Previous</div>
                                            <div class="change-value">{{ $change['before'] }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="change-box">
                                            <div class="change-caption">Current</div>
                                            <div class="change-value">{{ $change['after'] }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endforeach
                @else
                    <div class="timeline-details">No prior saved submission existed for this version, so no comparison diff is available.</div>
                @endif
            </div>
        @empty
            <div class="timeline-item">No saved submission versions yet.</div>
        @endforelse
    </div>
</body>
</html>
