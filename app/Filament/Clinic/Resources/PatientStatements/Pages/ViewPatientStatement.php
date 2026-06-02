<?php

namespace App\Filament\Clinic\Resources\PatientStatements\Pages;

use App\Filament\Clinic\Resources\PatientStatements\PatientStatementResource;
use App\Support\ClinicStatementNotifications;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPatientStatement extends ViewRecord
{
    protected static string $resource = PatientStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_pdf')
                ->label('View PDF')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('clinic.patient-statements.show', $this->record))
                ->openUrlInNewTab(),
            Action::make('download_pdf')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('clinic.patient-statements.download', $this->record)),
            Action::make('send_statement')
                ->label('Send')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->record->loadMissing('patient');

                    if (! ClinicStatementNotifications::canSend($record)) {
                        Notification::make()
                            ->title('Statement could not be sent')
                            ->body('Check the patient email and platform email settings before sending this statement.')
                            ->danger()
                            ->send();

                        return;
                    }

                    ClinicStatementNotifications::send($record, auth()->user());

                    Notification::make()
                        ->title('Statement sent')
                        ->body('The patient statement email was sent with the attached PDF.')
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
