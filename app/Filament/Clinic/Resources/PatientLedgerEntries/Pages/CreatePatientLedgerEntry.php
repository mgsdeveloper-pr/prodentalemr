<?php

namespace App\Filament\Clinic\Resources\PatientLedgerEntries\Pages;

use App\Filament\Clinic\Resources\PatientLedgerEntries\PatientLedgerEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientLedgerEntry extends CreateRecord
{
    protected static string $resource = PatientLedgerEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
