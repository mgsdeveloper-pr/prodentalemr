<?php

namespace App\Filament\Clinic\Resources\PatientLedgerEntries\Pages;

use App\Filament\Clinic\Resources\PatientLedgerEntries\PatientLedgerEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatientLedgerEntry extends EditRecord
{
    protected static string $resource = PatientLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
