<?php

namespace App\Filament\Clinic\Resources\PatientDocuments\Pages;

use App\Filament\Clinic\Resources\PatientDocuments\PatientDocumentResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientDocument extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = PatientDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route('clinic.patient-documents.show', $this->record))
                ->openUrlInNewTab(),
            Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('clinic.patient-documents.download', $this->record)),
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicPatientDocuments() ?? false),
        ];
    }
}
