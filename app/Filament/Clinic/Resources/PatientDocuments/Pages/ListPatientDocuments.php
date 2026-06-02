<?php

namespace App\Filament\Clinic\Resources\PatientDocuments\Pages;

use App\Filament\Clinic\Resources\PatientDocuments\PatientDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatientDocuments extends ListRecords
{
    protected static string $resource = PatientDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Upload document')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicPatientDocuments() ?? false),
        ];
    }
}
