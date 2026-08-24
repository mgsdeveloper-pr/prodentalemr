<?php

namespace App\Filament\Clinic\Resources\Locations\Pages;

use App\Filament\Clinic\Resources\Locations\LocationResource;
use App\Support\ClinicPanelScope;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['clinic_id'] = ClinicPanelScope::selectedClinicId();

        return $data;
    }
}
