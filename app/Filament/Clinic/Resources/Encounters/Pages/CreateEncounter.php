<?php

namespace App\Filament\Clinic\Resources\Encounters\Pages;

use App\Filament\Clinic\Resources\Encounters\EncounterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEncounter extends CreateRecord
{
    protected static string $resource = EncounterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;

        return $data;
    }
}
