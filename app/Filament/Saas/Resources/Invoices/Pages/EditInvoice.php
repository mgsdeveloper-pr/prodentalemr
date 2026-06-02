<?php

namespace App\Filament\Saas\Resources\Invoices\Pages;

use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Filament\Saas\Resources\Invoices\Support\InvoiceRecordActions;
use App\Support\SaasNotifications;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return CreateInvoice::normalizeInvoiceData($data);
    }

    protected function afterSave(): void
    {
        $this->record->refreshFinancialSummary();
        SaasNotifications::invoiceUpdated($this->record, auth()->user());
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ActionGroup::make([
                Action::make('editInvoice')
                    ->label('Edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->disabled(),
                InvoiceRecordActions::pageDownloadPdf($this->record),
                InvoiceRecordActions::pageViewPdf($this->record),
                InvoiceRecordActions::pageSend($this->record),
                InvoiceRecordActions::pageCopyPaymentLink($this->record),
                InvoiceRecordActions::pagePaymentPage($this->record),
                InvoiceRecordActions::pageCopyPayPalLink($this->record),
                InvoiceRecordActions::pagePayPalPage($this->record),
                InvoiceRecordActions::pageAddPayment($this->record),
                InvoiceRecordActions::pagePaymentReminder($this->record),
                InvoiceRecordActions::pageCancel($this->record),
                InvoiceRecordActions::pageDuplicate($this->record),
                InvoiceRecordActions::pageSoftDelete($this->record),
            ])
                ->label('Actions')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->button(),
        ];
    }
}
