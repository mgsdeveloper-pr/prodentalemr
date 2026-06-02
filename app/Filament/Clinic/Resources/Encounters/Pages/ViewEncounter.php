<?php

namespace App\Filament\Clinic\Resources\Encounters\Pages;

use App\Filament\Clinic\Resources\Encounters\EncounterResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEncounter extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = EncounterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicEncounters() ?? false),
        ];
    }
}
