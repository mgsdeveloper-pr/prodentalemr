<?php

namespace App\Filament\Saas\Resources\Locations\Pages;

use App\Filament\Saas\Resources\Locations\LocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
