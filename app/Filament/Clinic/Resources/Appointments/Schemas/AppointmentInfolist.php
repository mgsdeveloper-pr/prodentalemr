<?php

namespace App\Filament\Clinic\Resources\Appointments\Schemas;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Models\Appointment;
use App\Models\BillingWorkItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Appointment Snapshot')
                    ->description('Key schedule, provider, and patient details for the upcoming or completed visit.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('provider_name')
                                    ->label('Provider')
                                    ->state(fn ($record): string => $record->provider?->display_name ?? 'Unknown provider'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'confirmed', 'checked_in', 'in_chair' => 'info',
                                        'cancelled', 'no_show' => 'danger',
                                        default => 'warning',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('appointment_date')
                                    ->label('Date')
                                    ->date('M d, Y'),
                                TextEntry::make('start_time')
                                    ->label('Start time'),
                                TextEntry::make('end_time')
                                    ->label('End time')
                                    ->placeholder('-'),
                                TextEntry::make('location_name')
                                    ->label('Location')
                                    ->state(fn ($record): ?string => $record->location?->location_name)
                                    ->placeholder('-'),
                                TextEntry::make('operatory_name')
                                    ->label('Operatory')
                                    ->state(fn ($record): ?string => $record->operatory?->name)
                                    ->placeholder('-'),
                                TextEntry::make('appointment_type')
                                    ->label('Visit type')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('service_code')
                                    ->label('Service code')
                                    ->state(fn ($record): ?string => $record->clinicService?->service_code)
                                    ->placeholder('-'),
                                TextEntry::make('insurance_policy')
                                    ->label('Insurance policy')
                                    ->state(fn ($record): ?string => $record->insurancePolicy
                                        ? collect([
                                            $record->insurancePolicy->insurance_company,
                                            filled($record->insurancePolicy->member_id)
                                                ? 'Member '.$record->insurancePolicy->member_id
                                                : null,
                                        ])->filter()->implode(' | ')
                                        : null)
                                    ->placeholder('Not selected'),
                                TextEntry::make('parent_appointment_date')
                                    ->label('Follow-up to')
                                    ->state(fn ($record) => $record->parentAppointment?->appointment_date)
                                    ->date('M d, Y')
                                    ->placeholder('Not a follow-up'),
                                TextEntry::make('duration_minutes')
                                    ->label('Duration')
                                    ->formatStateUsing(fn ($state): string => filled($state) ? $state.' min' : '-'),
                                TextEntry::make('checked_in_at')
                                    ->label('Checked in at')
                                    ->dateTime('M d, Y h:i A')
                                    ->placeholder('-'),
                                TextEntry::make('seated_at')
                                    ->label('Seated at')
                                    ->dateTime('M d, Y h:i A')
                                    ->placeholder('-'),
                                TextEntry::make('completed_at')
                                    ->label('Completed at')
                                    ->dateTime('M d, Y h:i A')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Coordination Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Clinical notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('arrival_notes')
                            ->label('Arrival notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('reason_for_visit')
                            ->label('Reason for visit')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Insurance Verification')
                    ->description('Verification follows this appointment from request creation through completion.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('verification_status')
                                    ->label('Verification status')
                                    ->badge()
                                    ->state(fn ($record): string => $record->verification_status ?: Appointment::VERIFICATION_STATUS_NOT_SENT)
                                    ->formatStateUsing(fn (string $state): string => Appointment::VERIFICATION_STATUS_OPTIONS[$state] ?? 'Not Sent')
                                    ->color(fn (string $state): string => match ($state) {
                                        Appointment::VERIFICATION_STATUS_COMPLETED => 'success',
                                        Appointment::VERIFICATION_STATUS_IN_PROGRESS => 'info',
                                        Appointment::VERIFICATION_STATUS_SENT => 'warning',
                                        Appointment::VERIFICATION_STATUS_NEEDS_INSURANCE => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('verification_request')
                                    ->label('Request')
                                    ->state(fn ($record): ?string => $record->verificationWorkItem?->reference_number)
                                    ->placeholder('Not created')
                                    ->url(function ($record): ?string {
                                        $request = $record->verificationWorkItem;

                                        if (! $request) {
                                            return null;
                                        }

                                        return VerificationRequestResource::getUrl(
                                            $request->clinicUserCanEditVerification(auth()->user()) ? 'edit' : 'view',
                                            ['record' => $request],
                                        );
                                    }),
                                TextEntry::make('verification_processing_mode')
                                    ->label('Completed by')
                                    ->state(fn ($record): ?string => $record->verificationWorkItem?->processing_mode)
                                    ->formatStateUsing(fn (?string $state): string => BillingWorkItem::PROCESSING_MODE_OPTIONS[$state] ?? 'Not selected')
                                    ->placeholder('-'),
                                TextEntry::make('verification_outcome')
                                    ->label('Result')
                                    ->state(fn ($record): ?string => $record->verificationWorkItem?->outcome_status)
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : 'Pending')
                                    ->color(fn (?string $state): string => in_array($state, ['verified', 'completed', 'approved'], true) ? 'success' : 'gray'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
