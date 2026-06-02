<?php

namespace App\Filament\Saas\Resources\Invoices\Pages;

use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Support\BillingExport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportInvoices')
                ->label('Export CSV')
                ->action(fn () => response()->streamDownload(
                    fn () => print(BillingExport::invoicesCsv($this->getTableQueryForExport())),
                    'invoices-report.csv',
                    ['Content-Type' => 'text/csv'],
                )),
        ];
    }
}
