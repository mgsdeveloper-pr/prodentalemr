<?php

namespace App\Filament\Clinic\Resources\PatientInsurancePolicies\Pages;

use App\Filament\Clinic\Resources\PatientInsurancePolicies\PatientInsurancePolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatientInsurancePolicies extends ListRecords
{
    protected static string $resource = PatientInsurancePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
