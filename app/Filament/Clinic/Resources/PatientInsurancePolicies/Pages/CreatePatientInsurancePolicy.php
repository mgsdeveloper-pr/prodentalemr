<?php

namespace App\Filament\Clinic\Resources\PatientInsurancePolicies\Pages;

use App\Filament\Clinic\Resources\PatientInsurancePolicies\PatientInsurancePolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientInsurancePolicy extends CreateRecord
{
    protected static string $resource = PatientInsurancePolicyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
