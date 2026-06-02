<?php

namespace App\Filament\Clinic\Resources\PatientConsentForms\Pages;

use App\Filament\Clinic\Resources\PatientConsentForms\PatientConsentFormResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatientConsentForm extends EditRecord
{
    protected static string $resource = PatientConsentFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
