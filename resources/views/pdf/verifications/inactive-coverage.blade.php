<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Inactive Coverage</title>
<style>@page { margin:28px; } body { font-family:DejaVu Sans,sans-serif;font-size:12px;color:#142e25; } h1 {font-size:20px;} p {line-height:1.5;} </style>
</head><body>
    <h1>Verification Result: Inactive Coverage</h1>
    <p>{{ $clinic }} | {{ $reference }}<br>Work status: {{ \App\Models\BillingWorkItem::STATUS_OPTIONS[$status] ?? $status }}</p>
    @if ($status !== \App\Models\BillingWorkItem::STATUS_DONE)<p><strong>Not yet approved. Review is required.</strong></p>@endif
    @include('pdf.verifications.coverage-assessment', ['coverageAssessment' => $assessment])
    <p>Benefit questions were not assessed because coverage was recorded as inactive for the checked date of service. Previously entered benefit answers are retained in the request history and are not presented as current coverage.</p>
    <p>This finding applies only to the identified insurance policy and service date.</p>
</body></html>
