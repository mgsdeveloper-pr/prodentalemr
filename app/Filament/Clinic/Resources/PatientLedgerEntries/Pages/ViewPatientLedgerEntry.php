<?php

namespace App\Filament\Clinic\Resources\PatientLedgerEntries\Pages;

use App\Filament\Clinic\Resources\PatientLedgerEntries\PatientLedgerEntryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientLedgerEntry extends ViewRecord
{
    protected static string $resource = PatientLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
