<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVerificationRequests extends ListRecords
{
    protected static string $resource = VerificationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create verification request'),
            Action::make('import')
                ->label('Import verification requests')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(VerificationRequestResource::getUrl('import')),
            Action::make('downloadSample')
                ->label('Download sample Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(url('/samples/verification-request-import-sample.xlsx'))
                ->openUrlInNewTab(),
        ];
    }
}
