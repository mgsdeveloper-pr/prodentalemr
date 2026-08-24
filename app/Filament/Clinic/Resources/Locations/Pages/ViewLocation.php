<?php

namespace App\Filament\Clinic\Resources\Locations\Pages;

use App\Filament\Clinic\Resources\Locations\LocationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLocation extends ViewRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->visible(fn (): bool => LocationResource::canEdit($this->record))];
    }
}
