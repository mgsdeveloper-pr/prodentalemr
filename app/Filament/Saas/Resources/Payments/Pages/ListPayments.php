<?php

namespace App\Filament\Saas\Resources\Payments\Pages;

use App\Filament\Saas\Resources\Payments\PaymentResource;
use App\Support\BillingExport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportPayments')
                ->label('Export CSV')
                ->action(fn () => response()->streamDownload(
                    fn () => print(BillingExport::paymentsCsv($this->getTableQueryForExport())),
                    'payments-report.csv',
                    ['Content-Type' => 'text/csv'],
                )),
        ];
    }
}
