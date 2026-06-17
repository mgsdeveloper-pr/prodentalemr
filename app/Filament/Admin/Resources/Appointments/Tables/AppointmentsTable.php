<?php

namespace App\Filament\Admin\Resources\Appointments\Tables;

use App\Models\Appointment;
use App\Support\AppointmentVerificationSender;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('appointment_details')
                    ->label('Appointment Details')
                    ->html()
                    ->state(fn (Appointment $record): HtmlString => new HtmlString(
                        '<div style="display:flex;flex-direction:column;gap:4px;min-width:220px;">'
                        . '<span style="font-size:14px;font-weight:800;color:#0f172a;">' . e($record->patient?->full_name ?? 'Unknown patient') . '</span>'
                        . '<span style="font-size:12px;color:#64748b;">' . e($record->clinic?->clinic_name ?? 'Clinic not assigned') . '</span>'
                        . '</div>'
                    ))
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($builder) use ($search): void {
                            $builder->whereHas('patient', function ($patientQuery) use ($search): void {
                                $patientQuery
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('pms_patient_id', 'like', "%{$search}%");
                            })->orWhereHas('clinic', function ($clinicQuery) use ($search): void {
                                $clinicQuery->where('clinic_name', 'like', "%{$search}%");
                            });
                        });
                    }),
                TextColumn::make('date_time')
                    ->label('Date & Time')
                    ->html()
                    ->state(function (Appointment $record): HtmlString {
                        $date = $record->appointment_date?->format('M d, Y') ?? '-';
                        $time = $record->start_time
                            ? date('g:i A', strtotime((string) $record->start_time))
                            : 'Time optional';

                        return new HtmlString(
                            '<div style="display:flex;flex-direction:column;gap:4px;min-width:150px;">'
                            . '<span style="font-size:14px;font-weight:800;color:#0f172a;">' . e($date) . '</span>'
                            . '<span style="font-size:12px;color:#64748b;">' . e($time) . '</span>'
                            . '</div>'
                        );
                    })
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('appointment_date', $direction)
                        ->orderBy('start_time', $direction)),
                TextColumn::make('appointment_type')
                    ->label('Service')
                    ->state(fn (Appointment $record): string => $record->appointment_type ?: 'General Appointment')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'confirmed', 'checked_in', 'in_chair' => 'info',
                        'cancelled', 'no_show' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    ->state(fn (Appointment $record): string => $record->verification_status ?: Appointment::VERIFICATION_STATUS_NOT_SENT)
                    ->formatStateUsing(fn (?string $state): string => Appointment::VERIFICATION_STATUS_OPTIONS[$state ?: Appointment::VERIFICATION_STATUS_NOT_SENT] ?? 'Not Sent')
                    ->color(fn (?string $state): string => match ($state ?: Appointment::VERIFICATION_STATUS_NOT_SENT) {
                        Appointment::VERIFICATION_STATUS_SENT => 'info',
                        Appointment::VERIFICATION_STATUS_IN_PROGRESS => 'warning',
                        Appointment::VERIFICATION_STATUS_COMPLETED => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'checked_in' => 'Checked in',
                        'in_chair' => 'In chair',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No-show',
                    ]),
                TrashedFilter::make(),
            ])
            ->defaultSort('appointment_date', 'asc')
            ->recordActions([
                Action::make('sendForVerification')
                    ->label('Send for Verification')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send appointment for verification?')
                    ->modalDescription('This will create a verification request from this appointment and mark it as Sent.')
                    ->visible(fn (Appointment $record): bool => ($record->verification_status ?: Appointment::VERIFICATION_STATUS_NOT_SENT) === Appointment::VERIFICATION_STATUS_NOT_SENT)
                    ->action(function (Appointment $record, AppointmentVerificationSender $sender): void {
                        try {
                            $workItem = $sender->send($record);

                            Notification::make()
                                ->title('Sent for verification')
                                ->body('Verification request ' . $workItem->reference_number . ' has been created.')
                                ->success()
                                ->send();
                        } catch (\Throwable $exception) {
                            Notification::make()
                                ->title('Unable to send for verification')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('cancelAppointment')
                    ->label('Cancel Appointment')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->modalHeading('Cancel appointment?')
                    ->modalDescription('Add a cancellation note before marking this appointment as cancelled.')
                    ->modalSubmitActionLabel('Cancel Appointment')
                    ->form([
                        Textarea::make('cancellation_note')
                            ->label('Cancellation Note')
                            ->placeholder('Enter why this appointment is being cancelled.')
                            ->rows(4)
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->visible(fn (Appointment $record): bool => $record->status !== 'cancelled' && ! $record->trashed())
                    ->action(function (Appointment $record, array $data): void {
                        $note = trim((string) ($data['cancellation_note'] ?? ''));
                        $existingNotes = trim((string) $record->notes);
                        $cancellationNote = 'Cancellation note: ' . $note;

                        $record->forceFill([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'notes' => $existingNotes !== ''
                                ? $existingNotes . "\n\n" . $cancellationNote
                                : $cancellationNote,
                        ])->save();

                        Notification::make()
                            ->title('Appointment cancelled')
                            ->body('The appointment has been marked as cancelled.')
                            ->success()
                            ->send();
                    }),
                ViewAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton()
                    ->visible(fn (Appointment $record): bool => ! $record->trashed()),
            ]);
    }
}
