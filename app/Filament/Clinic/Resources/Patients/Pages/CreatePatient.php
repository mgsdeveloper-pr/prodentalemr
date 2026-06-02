<?php

namespace App\Filament\Clinic\Resources\Patients\Pages;

use App\Filament\Clinic\Resources\Patients\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;

        return $data;
    }
}
