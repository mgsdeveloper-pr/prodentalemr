<?php

namespace App\Filament\Clinic\Resources\Providers\Pages;

use App\Filament\Clinic\Resources\Providers\ProviderResource;
use App\Support\ClinicPanelScope;
use Filament\Resources\Pages\CreateRecord;

class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = ClinicPanelScope::selectedOrganizationId();
        $data['clinic_id'] = ClinicPanelScope::selectedClinicId();

        return $data;
    }
}
