<?php

namespace App\Filament\Clinic\Resources\PatientStatements\Pages;

use App\Filament\Clinic\Resources\PatientStatements\PatientStatementResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientStatement extends CreateRecord
{
    protected static string $resource = PatientStatementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->refreshSummary();
    }
}
