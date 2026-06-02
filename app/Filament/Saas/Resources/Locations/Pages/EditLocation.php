<?php

namespace App\Filament\Saas\Resources\Locations\Pages;

use App\Filament\Saas\Resources\Locations\LocationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
