<?php

namespace App\Filament\Clinic\Resources\PatientInsuranceClaims\Pages;

use App\Filament\Clinic\Resources\PatientInsuranceClaims\PatientInsuranceClaimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatientInsuranceClaim extends EditRecord
{
    protected static string $resource = PatientInsuranceClaimResource::class;

    protected function afterSave(): void
    {
        $this->record->refreshFinancialSummary();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
