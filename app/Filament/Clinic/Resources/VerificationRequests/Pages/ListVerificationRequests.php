<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVerificationRequests extends ListRecords
{
    protected static string $resource = VerificationRequestResource::class;

    public function getTitle(): string
    {
        return 'Verification Requests';
    }

    public function getSubheading(): ?string
    {
        return 'Track request status, results, and clinic follow-up.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(VerificationRequestResource::getUrl('import')),
            CreateAction::make()
                ->label('New Request')
                ->icon('heroicon-o-plus'),
        ];
    }
}
