@if (!empty($coverageAssessment))
<section style="border:1px solid #dce8e3;padding:12px;margin-bottom:14px;">
    <h2 style="font-size:14px;">{{ \App\Services\Verification\CoverageAssessmentService::OUTCOMES[$coverageAssessment['outcome'] ?? ''] ?? 'Coverage not confirmed' }}</h2>
    <table style="width:100%;font-size:11px;border-collapse:collapse;table-layout:fixed;">
        @foreach (\App\Services\Verification\CoverageAssessmentService::FIELDS as $key => $label)
            @if (filled($coverageAssessment[$key] ?? null))
                <tr><td style="width:35%;padding:5px;vertical-align:top;">{{ $label }}</td><td style="padding:5px;word-wrap:break-word;">{{ $coverageAssessment[$key] }}</td></tr>
            @endif
        @endforeach
        <tr><td style="padding:5px;">Recorded at</td><td style="padding:5px;">{{ $coverageAssessment['verified_at'] ?? 'Not recorded' }}</td></tr>
    </table>
</section>
@endif
