<?php

namespace App\Filament\Clinic\Resources\PatientConsentForms\Pages;

use App\Filament\Clinic\Resources\PatientConsentForms\PatientConsentFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientConsentForm extends CreateRecord
{
    protected static string $resource = PatientConsentFormResource::class;
}
