<?php

namespace App\Filament\Clinic\Resources\PatientInsurancePolicies\Pages;

use App\Filament\Clinic\Resources\PatientInsurancePolicies\PatientInsurancePolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatientInsurancePolicy extends EditRecord
{
    protected static string $resource = PatientInsurancePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
