<?php

namespace App\Filament\Clinic\Resources\PatientInsurancePolicies\Pages;

use App\Filament\Clinic\Resources\PatientInsurancePolicies\PatientInsurancePolicyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientInsurancePolicy extends ViewRecord
{
    protected static string $resource = PatientInsurancePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
