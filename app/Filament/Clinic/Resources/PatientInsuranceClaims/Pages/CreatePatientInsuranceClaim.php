<?php

namespace App\Filament\Clinic\Resources\PatientInsuranceClaims\Pages;

use App\Filament\Clinic\Resources\PatientInsuranceClaims\PatientInsuranceClaimResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientInsuranceClaim extends CreateRecord
{
    protected static string $resource = PatientInsuranceClaimResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->refreshFinancialSummary();
    }
}
