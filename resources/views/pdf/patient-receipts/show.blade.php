<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $entry->reference_number ?: 'Patient Receipt' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #152238; font-size: 12px; margin: 28px; }
        .header { border-top: 8px solid #d97706; padding-top: 18px; }
        .card { border: 1px solid #dbe3ef; border-radius: 12px; padding: 16px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { vertical-align: top; }
        .muted { color: #6b7280; }
        .amount { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="header">
        <table class="grid">
            <tr>
                <td style="width: 55%; padding-right: 18px;">
                    <div style="font-size: 28px; font-weight: 700;">{{ $settings->platform_name ?: 'ProDental EMR' }}</div>
                    <div class="muted" style="margin-top: 6px;">Patient Payment Receipt</div>
                    <div style="margin-top: 22px;">
                        <div style="font-size: 17px; font-weight: 700;">{{ $entry->patient?->full_name }}</div>
                        @if($entry->patient?->email)
                            <div class="muted" style="margin-top: 4px;">{{ $entry->patient->email }}</div>
                        @endif
                        @if($entry->patient?->phone)
                            <div class="muted">{{ $entry->patient->phone }}</div>
                        @endif
                    </div>
                </td>
                <td style="width: 45%;">
                    <div class="card">
                        <table style="width: 100%;">
                            <tr><td>Receipt ref</td><td class="amount"><strong>{{ $entry->reference_number ?: 'PAY-' . $entry->id }}</strong></td></tr>
                            <tr><td>Payment date</td><td class="amount"><strong>{{ $entry->posted_on?->format('M d, Y') }}</strong></td></tr>
                            <tr><td>Recorded by</td><td class="amount"><strong>{{ $entry->creator?->name ?: 'Clinic team' }}</strong></td></tr>
                            <tr><td>Amount received</td><td class="amount" style="font-size: 18px; padding-top: 10px;"><strong>${{ number_format((float) $entry->credit_amount, 2) }}</strong></td></tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-top: 18px;" class="grid">
            <tr>
                <td style="width: 50%; padding-right: 10px;">
                    <div class="card">
                        <div style="font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Payment Details</div>
                        <div style="margin-top: 10px;"><strong>Description:</strong> {{ $entry->description ?: ($entry->serviceItem?->name ?: 'Patient payment') }}</div>
                        <div style="margin-top: 8px;"><strong>Service / Reference:</strong> {{ $entry->serviceItem?->name ?: ($entry->reference_number ?: '-') }}</div>
                        @if($entry->provider?->display_name)
                            <div style="margin-top: 8px;"><strong>Provider:</strong> {{ $entry->provider->display_name }}</div>
                        @endif
                        @if($entry->location?->location_name)
                            <div style="margin-top: 8px;"><strong>Location:</strong> {{ $entry->location->location_name }}</div>
                        @endif
                    </div>
                </td>
                <td style="width: 50%; padding-left: 10px;">
                    <div class="card">
                        <div style="font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Notes</div>
                        <div class="muted" style="margin-top: 10px;">{{ $entry->notes ?: 'Thank you for your payment. Please retain this receipt for your records.' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
