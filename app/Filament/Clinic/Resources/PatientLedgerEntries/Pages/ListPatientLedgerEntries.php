<?php

namespace App\Filament\Clinic\Resources\PatientLedgerEntries\Pages;

use App\Filament\Clinic\Resources\PatientLedgerEntries\PatientLedgerEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatientLedgerEntries extends ListRecords
{
    protected static string $resource = PatientLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
