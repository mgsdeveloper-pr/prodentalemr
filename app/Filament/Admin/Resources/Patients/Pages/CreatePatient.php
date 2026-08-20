<?php

namespace App\Filament\Admin\Resources\Patients\Pages;

use App\Filament\Admin\Resources\Patients\PatientResource;
use App\Models\Clinic;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filled($data['clinic_id']) && blank($data['organization_id'])) {
            $data['organization_id'] = Clinic::query()->whereKey($data['clinic_id'])->value('organization_id');
        }

        $data['created_by'] = auth()->id();

        return $data;
    }
}
