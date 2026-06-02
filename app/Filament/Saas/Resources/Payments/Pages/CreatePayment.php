<?php

namespace App\Filament\Saas\Resources\Payments\Pages;

use App\Filament\Saas\Resources\Payments\PaymentResource;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $invoice = Invoice::query()->findOrFail($data['invoice_id']);

        $data['organization_id'] = $invoice->organization_id;
        $data['created_by'] = auth()->id();

        return $data;
    }
}
