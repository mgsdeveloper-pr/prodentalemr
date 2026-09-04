<?php

namespace App\Filament\Saas\Resources\TelephonyAccounts\Pages;

use App\Filament\Saas\Resources\TelephonyAccounts\TelephonyAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTelephonyAccount extends EditRecord
{
    protected static string $resource = TelephonyAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
