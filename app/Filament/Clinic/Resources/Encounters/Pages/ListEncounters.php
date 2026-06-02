<?php

namespace App\Filament\Clinic\Resources\Encounters\Pages;

use App\Filament\Clinic\Resources\Encounters\EncounterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEncounters extends ListRecords
{
    protected static string $resource = EncounterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New encounter')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicEncounters() ?? false),
        ];
    }
}
