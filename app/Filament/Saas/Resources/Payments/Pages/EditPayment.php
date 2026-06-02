<?php

namespace App\Filament\Saas\Resources\Payments\Pages;

use App\Filament\Saas\Resources\Payments\PaymentResource;
use App\Models\Invoice;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $invoice = Invoice::query()->findOrFail($data['invoice_id']);
        $data['organization_id'] = $invoice->organization_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
