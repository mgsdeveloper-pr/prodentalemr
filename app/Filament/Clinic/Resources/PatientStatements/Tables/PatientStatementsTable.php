<?php

namespace App\Filament\Clinic\Resources\PatientStatements\Tables;

use App\Models\PatientStatement;
use App\Support\ClinicStatementNotifications;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PatientStatementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('statement_number')
                    ->label('Statement #')
                    ->searchable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (PatientStatement $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('statement_date')
                    ->label('Statement date')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_from')
                    ->label('Period start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_to')
                    ->label('Period end')
                    ->date()
                    ->sortable(),
                TextColumn::make('closing_balance')
                    ->label('Closing')
                    ->money('USD'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PatientStatement::STATUS_OPTIONS),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('statement_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PatientStatement $record): string => route('clinic.patient-statements.show', $record))
                    ->openUrlInNewTab(),
                Action::make('download_pdf')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (PatientStatement $record): string => route('clinic.patient-statements.download', $record)),
                Action::make('send_statement')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (PatientStatement $record): bool => ! $record->trashed())
                    ->action(function (PatientStatement $record): void {
                        $record->loadMissing('patient');

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
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicPatientStatements() ?? false),
                DeleteAction::make()
                    ->visible(fn (PatientStatement $record): bool => (auth()->user()?->canDeleteClinicPatientStatements() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientStatements() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientStatements() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientStatements() ?? false),
                ]),
            ]);
    }
}
