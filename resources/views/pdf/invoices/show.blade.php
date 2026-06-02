<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 18px 22px 20px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.35;
        }

        .page {
            position: relative;
        }

        .top-rule {
            height: 8px;
            background: #f59e0b;
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .header,
        .summary-grid,
        .totals,
        .notes-grid,
        .closing-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .header td,
        .summary-grid td,
        .totals td,
        .notes-grid td,
        .closing-grid td {
            vertical-align: top;
        }

        .brand-block {
            width: 58%;
            padding-right: 14px;
        }

        .brand-chip {
            display: inline-block;
            font-size: 9px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 4px 8px;
            border-radius: 999px;
            margin-bottom: 8px;
        }

        .logo {
            height: 40px;
            width: auto;
            margin-bottom: 6px;
        }

        .brand-title {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }

        .company-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
            color: #92400e;
        }

        .meta-card {
            width: 42%;
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 16px;
            padding: 12px 14px;
        }

        .invoice-label {
            font-size: 10px;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            color: #9a3412;
            margin-bottom: 4px;
        }

        .invoice-title {
            font-size: 26px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 8px;
        }

        .meta-row {
            margin-bottom: 5px;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-key {
            width: 44%;
            color: #6b7280;
            font-size: 10px;
        }

        .meta-value {
            width: 56%;
            text-align: right;
            font-weight: bold;
            color: #111827;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
        }

        .status-sent {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-overdue {
            background: #fee2e2;
            color: #b91c1c;
        }

        .section {
            margin-top: 14px;
        }

        .section-title {
            font-size: 10px;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 10px 12px;
        }

        .billed-name {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }

        .callout {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
        }

        .callout-title {
            font-size: 10px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-weight: bold;
        }

        .callout-emphasis {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .callout-warning {
            background: #fff7ed;
            border-color: #fdba74;
            color: #9a3412;
        }

        .callout-danger {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #b91c1c;
        }

        .callout-success {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
        }

        .items-wrap {
            margin-top: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
        }

        .items thead th {
            background: #1f2937;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.3px;
            text-align: left;
            padding: 8px 10px;
        }

        .items tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items tbody tr:nth-child(even) td {
            background: #fcfcfd;
        }

        .items tbody tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .totals-wrap {
            margin-top: 10px;
            width: 100%;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .totals-layout {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-layout td {
            vertical-align: top;
        }

        .totals-side {
            width: 54%;
            padding-right: 12px;
        }

        .totals-main {
            width: 46%;
        }

        .totals-card {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 16px;
            padding: 10px 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .totals td {
            padding: 4px 0;
        }

        .totals-label {
            color: #6b7280;
        }

        .totals-value {
            text-align: right;
            color: #111827;
            font-weight: bold;
        }

        .total-divider td {
            border-top: 1px solid #fdba74;
            padding-top: 7px;
        }

        .grand-total td {
            font-size: 14px;
            font-weight: bold;
        }

        .balance-highlight {
            margin-top: 8px;
            padding: 8px 10px;
            background: #92400e;
            color: #ffffff;
            border-radius: 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .balance-label {
            font-size: 9px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            opacity: 0.75;
        }

        .balance-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 2px;
        }

        .notes-grid {
            margin-top: 12px;
        }

        .notes-grid td:first-child {
            padding-right: 8px;
        }

        .notes-grid td:last-child {
            padding-left: 8px;
        }

        .notes-box,
        .terms-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 12px;
        }

        .terms-box {
            background: #f8fafc;
            border-color: #dbeafe;
        }

        .payment-box {
            margin-top: 12px;
            background: #111827;
            color: #ffffff;
            border-radius: 16px;
            padding: 10px 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .payment-title,
        .terms-title {
            font-size: 10px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .payment-title {
            opacity: 0.7;
        }

        .payment-box a {
            color: #ffffff;
            text-decoration: none;
        }

        .closing-grid {
            margin-top: 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .closing-grid td {
            width: 50%;
            padding-right: 12px;
        }

        .closing-grid td:last-child {
            padding-right: 0;
        }

        .signature-box {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 12px;
            min-height: 56px;
            background: #ffffff;
        }

        .system-note {
            color: #4b5563;
            font-size: 10px;
            line-height: 1.5;
        }

        .signatory-image {
            max-height: 42px;
            width: auto;
            margin-top: 4px;
            margin-bottom: 8px;
        }

        .footer {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    @php
        $displayInvoiceNumber = match ($settings->invoice_number_style ?? 'prefixed_period_sequence') {
            'prefixed_period_sequence' => preg_replace('/^[A-Z]+-/', '', (string) $invoice->invoice_number) ?? $invoice->invoice_number,
            default => $invoice->invoice_number,
        };
        $displaySubtotal = (float) $invoice->items->sum('line_total');
        $displayTotal = max($displaySubtotal + (float) $invoice->tax_amount - (float) $invoice->discount_amount, 0);
        $displayAmountPaid = (float) $invoice->payments->sum('amount');
        $displayBalanceDue = max($displayTotal - $displayAmountPaid, 0);
        $quantityPrecision = max((int) ($settings->invoice_quantity_precision ?? 2), 0);
        $quantityLabel = $settings->invoice_unit_label ?: 'Qty';
        $taxMessage = trim((string) ($settings->invoice_tax_message_text ?? ''));
        $termsAndConditions = trim((string) ($settings->invoice_terms_conditions ?? ''));
        $bankingDetails = array_filter([
            'Account Holder' => $settings->bank_account_name,
            'Account Type' => filled($settings->bank_account_type) ? ucfirst((string) $settings->bank_account_type) : null,
            'Bank Name' => $settings->bank_name,
            'Account Number' => $settings->bank_account_number,
            'ABA Routing Number' => $settings->bank_routing_number,
            'SWIFT / BIC' => $settings->bank_swift_code,
            'Bank Branch / Address' => $settings->bank_branch,
        ], fn ($value) => filled($value));

        $effectiveStatus = match (true) {
            $invoice->status === 'cancelled' => 'cancelled',
            $displayTotal > 0 && $displayBalanceDue <= 0.01 => 'paid',
            $invoice->due_date && $invoice->due_date->isPast() && $displayBalanceDue > 0.01 => 'overdue',
            default => 'unpaid',
        };

        $isOverdue = $effectiveStatus === 'overdue';
        $isPaid = $effectiveStatus === 'paid';
        $clientPaymentLabel = $isPaid ? 'Paid' : 'Unpaid';
    @endphp

    <div class="page">
        <div class="top-rule"></div>

        <table class="header">
            <tr>
                <td class="brand-block">
                    <div class="brand-chip">Service Invoice</div>

                    @if ($logoPath)
                        <img src="{{ $logoPath }}" alt="{{ $settings->brandName() }}" class="logo">
                    @endif

                    <div class="brand-title">{{ $settings->brandName() }}</div>

                    @if ($settings->company_name)
                        <div class="company-name">{{ $settings->company_name }}</div>
                    @endif

                    @if ($settings->address)
                        <div>{!! nl2br(e($settings->address)) !!}</div>
                    @endif

                    @if ($settings->support_email || $settings->support_phone)
                        <div style="margin-top: 6px;">
                            @if ($settings->support_email)
                                <div>{{ $settings->support_email }}</div>
                            @endif
                            @if ($settings->support_phone)
                                <div>{{ $settings->support_phone }}</div>
                            @endif
                        </div>
                    @endif

                    @if ($settings->invoice_show_tax_number && filled($settings->invoice_tax_number_value))
                        <div style="margin-top: 6px;"><strong>Tax ID:</strong> {{ $settings->invoice_tax_number_value }}</div>
                    @endif
                </td>
                <td class="meta-card">
                    <div class="invoice-label">Billing Document</div>
                    <div class="invoice-title">Invoice</div>

                    <table class="summary-grid">
                        <tr class="meta-row">
                            <td class="meta-key">Invoice #</td>
                            <td class="meta-value">{{ $displayInvoiceNumber }}</td>
                        </tr>
                        <tr class="meta-row">
                            <td class="meta-key">Issue date</td>
                            <td class="meta-value">{{ optional($invoice->issue_date)->format('M d, Y') }}</td>
                        </tr>
                        <tr class="meta-row">
                            <td class="meta-key">Due date</td>
                            <td class="meta-value">{{ optional($invoice->due_date)->format('M d, Y') ?: '-' }}</td>
                        </tr>
                        @if ($settings->invoice_show_status)
                            <tr class="meta-row">
                                <td class="meta-key">Payment status</td>
                                <td class="meta-value">
                                    <span class="status-badge {{ $isPaid ? 'status-paid' : ($isOverdue ? 'status-overdue' : 'status-sent') }}">{{ $clientPaymentLabel }}</span>
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div class="section">
            <div class="section-title">Billed To</div>
            <div class="card">
                @if ($settings->invoice_show_client_name)
                    <div class="billed-name">{{ $invoice->organization?->name }}</div>
                    <div>{{ $invoice->organization?->owner_name ?: '-' }}</div>
                @endif
                @if ($settings->invoice_show_client_address)
                    @foreach (($invoice->organization?->billingAddressLines() ?? []) as $addressLine)
                        <div>{{ $addressLine }}</div>
                    @endforeach
                @endif
                @if ($settings->invoice_show_client_email)
                    <div>{{ $invoice->organization?->email ?: '-' }}</div>
                @endif
                @if ($settings->invoice_show_client_phone)
                    <div>{{ $invoice->organization?->phone ?: '-' }}</div>
                @endif
            </div>
        </div>

        @if ($isOverdue)
            <div class="callout callout-danger">
                <div class="callout-title">Payment Attention Required</div>
                <div class="callout-emphasis">This invoice is overdue.</div>
                <div>Payment of ${{ number_format($displayBalanceDue, 2) }} is outstanding past the due date of {{ $invoice->due_date->format('M d, Y') }}.</div>
            </div>
        @elseif ($isPaid)
            <div class="callout callout-success">
                <div class="callout-title">Payment Status</div>
                <div class="callout-emphasis">This invoice has been paid.</div>
                <div>Thank you. Your balance has been cleared in full.</div>
            </div>
        @elseif ($invoice->due_date)
            <div class="callout callout-warning">
                <div class="callout-title">Payment Due</div>
                <div class="callout-emphasis">${{ number_format($displayBalanceDue, 2) }} due by {{ $invoice->due_date->format('M d, Y') }}</div>
                <div>Please reference invoice {{ $displayInvoiceNumber }} when sending payment or support requests.</div>
            </div>
        @endif

        <div class="section">
            <div class="section-title">Service Breakdown</div>

            <div class="items-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th style="width: 8%;">#</th>
                            <th style="width: 44%;">Description</th>
                            <th style="width: 16%;" class="text-right">{{ $quantityLabel }}</th>
                            <th style="width: 16%;" class="text-right">Unit Price</th>
                            <th style="width: 16%;" class="text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-right">{{ number_format((float) $item->quantity, $quantityPrecision) }}</td>
                                <td class="text-right">${{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="text-right">${{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-right">No invoice items</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="totals-wrap">
            <table class="totals-layout">
                <tr>
                    <td class="totals-side">
                        @if (! empty($bankingDetails))
                            <div class="section-title">ACH / Wire Payment Details</div>
                            <div class="terms-box">
                                @foreach ($bankingDetails as $label => $value)
                                    <div><strong>{{ $label }}:</strong> {{ $value }}</div>
                                @endforeach
                                @if ($settings->bank_payment_notes)
                                    <div style="margin-top: 8px;">{!! nl2br(e($settings->bank_payment_notes)) !!}</div>
                                @endif
                                <div style="margin-top: 8px;">Please quote invoice number <strong>{{ $displayInvoiceNumber }}</strong> when remitting funds.</div>
                            </div>
                        @endif
                    </td>
                    <td class="totals-main">
                        <div class="totals-card">
                            <table class="totals">
                                <tr>
                                    <td class="totals-label">Subtotal</td>
                                    <td class="totals-value">${{ number_format($displaySubtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="totals-label">Tax</td>
                                    <td class="totals-value">${{ number_format((float) $invoice->tax_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="totals-label">Discount</td>
                                    <td class="totals-value">${{ number_format((float) $invoice->discount_amount, 2) }}</td>
                                </tr>
                                <tr class="total-divider grand-total">
                                    <td>Total</td>
                                    <td class="totals-value">${{ number_format($displayTotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="totals-label">Amount paid</td>
                                    <td class="totals-value">${{ number_format($displayAmountPaid, 2) }}</td>
                                </tr>
                            </table>

                            <div class="balance-highlight">
                                <div class="balance-label">Balance Due</div>
                                <div class="balance-value">${{ number_format($displayBalanceDue, 2) }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        @if ($invoice->notes || filled($termsAndConditions) || ($settings->invoice_show_tax_message && filled($taxMessage)))
            <table class="notes-grid">
                <tr>
                    <td>
                        @if ($invoice->notes)
                            <div class="section-title">Notes</div>
                            <div class="notes-box">{!! nl2br(e($invoice->notes)) !!}</div>
                        @endif
                    </td>
                    <td>
                        @if (filled($termsAndConditions))
                            <div class="section-title">Terms and Conditions</div>
                            <div class="terms-box">{!! nl2br(e($termsAndConditions)) !!}</div>
                        @elseif ($settings->invoice_show_tax_message && filled($taxMessage))
                            <div class="section-title">Tax Message</div>
                            <div class="terms-box">{!! nl2br(e($taxMessage)) !!}</div>
                        @endif
                    </td>
                </tr>
                @if ($settings->invoice_show_tax_message && filled($taxMessage) && filled($termsAndConditions))
                    <tr>
                        <td></td>
                        <td style="padding-top: 10px;">
                            <div class="section-title">Tax Message</div>
                            <div class="terms-box">{!! nl2br(e($taxMessage)) !!}</div>
                        </td>
                    </tr>
                @endif
            </table>
        @endif

        @if ($settings->invoice_show_payment_instructions)
            <div class="payment-box">
                <div class="payment-title">Payment Instructions</div>
                <div>Please include invoice number <strong>{{ $displayInvoiceNumber }}</strong> with your payment reference.</div>
                @if ($settings->support_email)
                    <div style="margin-top: 6px;">Billing help: {{ $settings->support_email }}</div>
                @endif
                @if ($settings->support_phone)
                    <div>Support phone: {{ $settings->support_phone }}</div>
                @endif
                @if ($settings->company_name)
                    <div>Remit to: {{ $settings->company_name }}</div>
                @endif
            </div>
        @endif

        <table class="closing-grid">
            <tr>
                <td>
                    @if ($settings->invoice_show_authorised_signatory && filled($signaturePath))
                        <div class="signature-box">
                            <div class="terms-title">Authorised Signatory</div>
                            <img src="{{ $signaturePath }}" alt="Authorised Signatory Signature" class="signatory-image">
                            @if ($settings->company_name)
                                <div>{{ $settings->company_name }}</div>
                            @endif
                            @if ($settings->invoice_show_invoice_notice)
                                <div class="system-note" style="margin-top: 6px;">This invoice is system generated and is valid without a signature.</div>
                            @endif
                        </div>
                    @elseif ($settings->invoice_show_invoice_notice)
                        <div class="signature-box">
                            <div class="terms-title">Invoice Notice</div>
                            <div class="system-note">This invoice is system generated and is valid without a signature.</div>
                        </div>
                    @endif
                </td>
                <td>
                    <div class="signature-box">
                        <div class="terms-title">Remittance Reference</div>
                        <div>Invoice #: <strong>{{ $displayInvoiceNumber }}</strong></div>
                        <div>Organization: <strong>{{ $invoice->organization?->name }}</strong></div>
                        <div>Due Date: <strong>{{ optional($invoice->due_date)->format('M d, Y') ?: '-' }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Thank you for choosing {{ $settings->brandName() }}. Retain this invoice for your payment and administrative records.
        </div>
    </div>
</body>
</html>
