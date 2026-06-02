<?php

namespace App\Filament\Clinic\Resources\PatientInsuranceClaims\Pages;

use App\Filament\Clinic\Resources\PatientInsuranceClaims\PatientInsuranceClaimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatientInsuranceClaims extends ListRecords
{
    protected static string $resource = PatientInsuranceClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
