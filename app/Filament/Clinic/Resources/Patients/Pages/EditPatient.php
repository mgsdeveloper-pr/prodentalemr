<?php

namespace App\Filament\Clinic\Resources\Patients\Pages;

use App\Filament\Clinic\Resources\Patients\PatientResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatients() ?? false),
        ];
    }
}
