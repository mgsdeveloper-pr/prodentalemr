<?php

namespace App\Filament\Saas\Resources\Invoices\Pages;

use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Support\SaasNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return static::normalizeInvoiceData($data);
    }

    protected function afterCreate(): void
    {
        $this->record->refreshFinancialSummary();
        SaasNotifications::invoiceCreated($this->record, auth()->user());
    }

    public static function normalizeInvoiceData(array $data): array
    {
        $subtotal = collect($data['items'] ?? [])
            ->sum(fn (array $item): float => (float) ($item['line_total'] ?? 0));

        $tax = (float) ($data['tax_amount'] ?? 0);
        $discount = (float) ($data['discount_amount'] ?? 0);
        $total = max($subtotal + $tax - $discount, 0);
        $amountPaid = (float) ($data['amount_paid'] ?? 0);

        $data['subtotal'] = $subtotal;
        $data['total_amount'] = $total;
        $data['balance_due'] = max($total - $amountPaid, 0);

        if (($data['status'] ?? null) === 'paid' && $data['balance_due'] > 0.01) {
            $data['status'] = $amountPaid > 0 ? 'partial' : 'sent';
        }

        if (($data['status'] ?? null) === 'paid' && blank($data['paid_at'] ?? null)) {
            $data['paid_at'] = now()->toDateString();
        }

        if (($data['status'] ?? null) !== 'paid') {
            $data['paid_at'] = null;
        }

        return $data;
    }
}
