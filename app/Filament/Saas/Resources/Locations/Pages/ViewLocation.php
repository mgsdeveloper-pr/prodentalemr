<?php

namespace App\Filament\Saas\Resources\Locations\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\Locations\LocationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLocation extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
