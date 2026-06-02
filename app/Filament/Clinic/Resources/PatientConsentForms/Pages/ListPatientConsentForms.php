<?php

namespace App\Filament\Clinic\Resources\PatientConsentForms\Pages;

use App\Filament\Clinic\Resources\PatientConsentForms\PatientConsentFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatientConsentForms extends ListRecords
{
    protected static string $resource = PatientConsentFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
