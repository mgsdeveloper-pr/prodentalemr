<?php

namespace App\Filament\Clinic\Resources\PatientDocuments\Pages;

use App\Filament\Clinic\Resources\PatientDocuments\PatientDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPatientDocument extends EditRecord
{
    protected static string $resource = PatientDocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;
        $data['disk'] = 'local';

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route('clinic.patient-documents.show', $this->record))
                ->openUrlInNewTab(),
            Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('clinic.patient-documents.download', $this->record)),
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientDocuments() ?? false),
        ];
    }
}
