<?php

namespace App\Filament\Saas\Resources\TelephonyAccounts\Pages;

use App\Filament\Saas\Resources\TelephonyAccounts\TelephonyAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTelephonyAccounts extends ListRecords
{
    protected static string $resource = TelephonyAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Connect MightyCall')];
    }
}
