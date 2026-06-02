<?php

namespace App\Filament\Clinic\Resources\PatientStatements\Pages;

use App\Filament\Clinic\Resources\PatientStatements\PatientStatementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatientStatements extends ListRecords
{
    protected static string $resource = PatientStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
