<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $statement->statement_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #152238; font-size: 12px; margin: 24px; }
        .topbar { border-top: 8px solid #d97706; padding-top: 18px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { vertical-align: top; }
        .muted { color: #6b7280; }
        .card { border: 1px solid #dbe3ef; border-radius: 12px; padding: 14px 16px; }
        .summary td { padding: 6px 0; }
        .table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .table th { background: #172033; color: #fff; text-align: left; padding: 10px; font-size: 11px; }
        .table td { border-bottom: 1px solid #e5e7eb; padding: 10px; }
        .amount { text-align: right; white-space: nowrap; }
        .section-title { font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="topbar">
        <table class="grid">
            <tr>
                <td style="width: 56%; padding-right: 16px;">
                    <div style="font-size: 28px; font-weight: 700;">{{ $settings->platform_name ?: 'ProDental EMR' }}</div>
                    <div class="muted" style="margin-top: 6px;">Patient Statement</div>
                    <div style="margin-top: 18px;">
                        <div class="section-title">Patient</div>
                        <div style="font-size: 18px; font-weight: 700;">{{ $statement->patient?->full_name }}</div>
                        @if($statement->patient?->address)
                            <div class="muted" style="margin-top: 4px;">{{ $statement->patient->address }}</div>
                        @endif
                        @if($statement->patient?->email)
                            <div class="muted">{{ $statement->patient->email }}</div>
                        @endif
                        @if($statement->patient?->phone)
                            <div class="muted">{{ $statement->patient->phone }}</div>
                        @endif
                    </div>
                </td>
                <td style="width: 44%;">
                    <div class="card">
                        <div class="section-title">Statement Summary</div>
                        <table class="summary" style="width: 100%;">
                            <tr><td>Statement #</td><td class="amount"><strong>{{ $statement->statement_number }}</strong></td></tr>
                            <tr><td>Statement date</td><td class="amount"><strong>{{ $statement->statement_date?->format('M d, Y') }}</strong></td></tr>
                            <tr><td>Period</td><td class="amount"><strong>{{ $statement->period_from?->format('M d, Y') }} to {{ $statement->period_to?->format('M d, Y') }}</strong></td></tr>
                            <tr><td>Status</td><td class="amount"><strong>{{ str($statement->status)->replace('_', ' ')->title() }}</strong></td></tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-top: 18px;" class="grid">
            <tr>
                <td style="width: 50%; padding-right: 10px;">
                    <div class="card">
                        <div class="section-title">Balances</div>
                        <table class="summary" style="width: 100%;">
                            <tr><td>Opening balance</td><td class="amount">${{ number_format((float) $statement->opening_balance, 2) }}</td></tr>
                            <tr><td>Charges</td><td class="amount">${{ number_format((float) $statement->charges_total, 2) }}</td></tr>
                            <tr><td>Payments</td><td class="amount">${{ number_format((float) $statement->payments_total, 2) }}</td></tr>
                            <tr><td>Adjustments</td><td class="amount">${{ number_format((float) $statement->adjustments_total, 2) }}</td></tr>
                            <tr><td style="padding-top: 10px; font-weight: 700;">Closing balance</td><td class="amount" style="padding-top: 10px; font-weight: 700;">${{ number_format((float) $statement->closing_balance, 2) }}</td></tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%; padding-left: 10px;">
                    <div class="card">
                        <div class="section-title">Notes</div>
                        <div class="muted">{{ $statement->notes ?: 'Please contact the clinic billing team if you have any questions about this statement.' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th class="amount">Debit</th>
                    <th class="amount">Credit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->posted_on?->format('M d, Y') }}</td>
                        <td>{{ $entry->reference_number ?: '-' }}</td>
                        <td>{{ $entry->description ?: ($entry->serviceItem?->name ?: str($entry->entry_type)->replace('_', ' ')->title()) }}</td>
                        <td class="amount">${{ number_format((float) $entry->debit_amount, 2) }}</td>
                        <td class="amount">${{ number_format((float) $entry->credit_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted" style="text-align: center;">No ledger activity exists in this statement period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
