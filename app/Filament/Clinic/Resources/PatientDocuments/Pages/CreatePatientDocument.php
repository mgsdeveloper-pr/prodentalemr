<?php

namespace App\Filament\Clinic\Resources\PatientDocuments\Pages;

use App\Filament\Clinic\Resources\PatientDocuments\PatientDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientDocument extends CreateRecord
{
    protected static string $resource = PatientDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;
        $data['disk'] = 'local';

        return $data;
    }
}
