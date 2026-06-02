<?php

namespace App\Filament\Clinic\Resources\PatientConsentForms\Pages;

use App\Filament\Clinic\Resources\PatientConsentForms\PatientConsentFormResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientConsentForm extends ViewRecord
{
    protected static string $resource = PatientConsentFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
