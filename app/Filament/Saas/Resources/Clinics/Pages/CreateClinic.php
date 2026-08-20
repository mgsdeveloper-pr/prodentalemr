<?php

namespace App\Filament\Saas\Resources\Clinics\Pages;

use App\Filament\Saas\Resources\Clinics\ClinicResource;
use App\Filament\Saas\Resources\Clinics\Schemas\ClinicForm;
use Filament\Resources\Pages\CreateRecord;

class CreateClinic extends CreateRecord
{
    protected static string $resource = ClinicResource::class;

    public function getSubheading(): ?string
    {
        return 'Add the clinic details and choose how verification work will be managed.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ClinicForm::normalizeForCreate($data);
    }

    protected function getRedirectUrl(): string
    {
        return ClinicResource::getUrl('view', ['record' => $this->record]);
    }
}
