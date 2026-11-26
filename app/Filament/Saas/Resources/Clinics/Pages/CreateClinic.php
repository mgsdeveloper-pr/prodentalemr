<?php

namespace App\Filament\Saas\Resources\Clinics\Pages;

use App\Filament\Saas\Resources\Clinics\ClinicResource;
use App\Filament\Saas\Resources\Clinics\Schemas\ClinicForm;
use App\Models\Clinic;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateClinic extends CreateRecord
{
    protected static string $resource = ClinicResource::class;

    protected ?bool $hasDatabaseTransactions = true;

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

    protected function handleRecordCreation(array $data): Clinic
    {
        try {
            return Clinic::createWithRequiredVerificationTemplates($data);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'data.verification_default_form_template' => $exception->getMessage(),
            ]);
        }
    }
}
