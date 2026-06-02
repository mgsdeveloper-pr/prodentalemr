<?php

namespace App\Filament\Saas\Resources\Invoices\Pages;

use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Filament\Saas\Resources\Invoices\Support\InvoiceRecordActions;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewInvoice extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('editInvoice')
                    ->label('Edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(InvoiceRecordActions::editUrl($this->record)),
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
