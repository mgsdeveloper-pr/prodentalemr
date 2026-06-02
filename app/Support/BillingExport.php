<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class BillingExport
{
    public static function invoicesCsv(Builder $query): string
    {
        $rows = [['Invoice Number', 'Organization', 'Status', 'Issue Date', 'Due Date', 'Total Amount', 'Amount Paid', 'Balance Due']];

        /** @var iterable<int, Invoice> $invoices */
        $invoices = (clone $query)
            ->with('organization')
            ->get();

        foreach ($invoices as $invoice) {
            $rows[] = [
                $invoice->invoice_number,
                $invoice->organization?->name,
                $invoice->status,
                optional($invoice->issue_date)->format('Y-m-d'),
                optional($invoice->due_date)->format('Y-m-d'),
                number_format((float) $invoice->total_amount, 2, '.', ''),
                number_format((float) $invoice->amount_paid, 2, '.', ''),
                number_format((float) $invoice->balance_due, 2, '.', ''),
            ];
        }

        return static::toCsv($rows);
    }

    public static function paymentsCsv(Builder $query): string
    {
        $rows = [['Payment Date', 'Invoice', 'Organization', 'Method', 'Amount', 'Reference']];

        /** @var iterable<int, Payment> $payments */
        $payments = (clone $query)
            ->with(['invoice', 'organization'])
            ->get();

        foreach ($payments as $payment) {
            $rows[] = [
                optional($payment->payment_date)->format('Y-m-d'),
                $payment->invoice?->invoice_number,
                $payment->organization?->name,
                $payment->payment_method,
                number_format((float) $payment->amount, 2, '.', ''),
                $payment->reference_number,
            ];
        }

        return static::toCsv($rows);
    }

    protected static function toCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);

        return (string) stream_get_contents($stream);
    }
}
