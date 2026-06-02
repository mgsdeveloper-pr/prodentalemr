<?php

namespace App\Filament\Clinic\Resources\PatientInsuranceClaims\Pages;

use App\Filament\Clinic\Resources\PatientInsuranceClaims\PatientInsuranceClaimResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientInsuranceClaim extends ViewRecord
{
    protected static string $resource = PatientInsuranceClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
