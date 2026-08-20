<?php

namespace App\Filament\Saas\Resources\Clinics\Pages;

use App\Filament\Saas\Resources\Clinics\ClinicResource;
use App\Filament\Saas\Resources\Clinics\Schemas\ClinicForm;
use App\Support\ClinicTemplateSettingsSupport;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClinic extends EditRecord
{
    protected static string $resource = ClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = ClinicForm::normalizeForSave($data);
        ClinicTemplateSettingsSupport::assertCanChange($this->record, $data);

        return $data;
    }
}
