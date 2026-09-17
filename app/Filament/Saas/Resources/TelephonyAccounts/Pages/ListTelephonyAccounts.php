<?php

namespace App\Filament\Saas\Resources\TelephonyAccounts\Pages;

use App\Filament\Saas\Pages\CallingProviders;
use App\Filament\Saas\Resources\TelephonyAccounts\TelephonyAccountResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTelephonyAccounts extends ListRecords
{
    protected static string $resource = TelephonyAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('providers')->label('Calling providers')->icon('heroicon-o-cog-6-tooth')
                ->url(CallingProviders::getUrl())
                ->visible(fn () => CallingProviders::canAccess()),
            CreateAction::make()->label('Connect MightyCall'),
        ];
    }
}
