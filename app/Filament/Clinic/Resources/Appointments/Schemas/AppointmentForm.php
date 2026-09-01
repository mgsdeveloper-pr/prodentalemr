<?php

namespace App\Filament\Clinic\Resources\Appointments\Schemas;

use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Models\Appointment;
use App\Models\ClinicOperatory;
use App\Models\ClinicService;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Support\AppointmentWorkspaceScope;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => AppointmentWorkspaceScope::selectedOrganizationId()),
                Hidden::make('clinic_id')
                    ->default(fn () => AppointmentWorkspaceScope::selectedClinicId()),
                Grid::make(12)
                    ->schema([
                        Select::make('location_id')
                            ->label('Select Clinic Location')
                            ->default(fn (): ?int => AppointmentWorkspaceScope::mappedLocationId())
                            ->options(fn (): array => Location::query()
                                ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
                                ->orderBy('location_name')
                                ->pluck('location_name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->hidden(fn ($record): bool => blank($record) && AppointmentWorkspaceScope::hasLockedLocation())
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $set('provider_id', null);
                                $set('clinic_service_id', null);
                                $set('clinic_operatory_id', null);
                                $set('appointment_type', null);
                                $set('verification_processing_mode', VerificationRequestForm::defaultProcessingMode(
                                    AppointmentWorkspaceScope::selectedOrganizationId(),
                                    AppointmentWorkspaceScope::selectedClinicId(),
                                    filled($state) ? (int) $state : null,
                                ));
                                $set('start_time', null);
                                $set('end_time', null);
                            })
                            ->required()
                            ->columnSpan(6),
                        Select::make('provider_id')
                            ->label('Select Doctor')
                            ->options(fn (Get $get): array => Provider::query()
                                ->with('user')
                                ->where('organization_id', AppointmentWorkspaceScope::selectedOrganizationId())
                                ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
                                ->when(filled($get('location_id')), fn ($query) => $query->where(function ($inner) use ($get): void {
                                    $inner->whereNull('location_id')->orWhere('location_id', $get('location_id'));
                                }))
                                ->where('status', true)
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (Provider $provider) => [$provider->id => $provider->display_name])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (Get $get): bool => blank($get('location_id')))
                            ->required()
                            ->columnSpan(6),
                        Select::make('clinic_service_id')
                            ->label('Select Service')
                            ->options(fn (Get $get): array => ClinicService::query()
                                ->where('organization_id', AppointmentWorkspaceScope::selectedOrganizationId())
                                ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
                                ->when(filled($get('location_id')), fn ($query) => $query->where(function ($inner) use ($get): void {
                                    $inner->whereNull('location_id')->orWhere('location_id', $get('location_id'));
                                }))
                                ->where('status', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (ClinicService $service): array => [
                                    $service->id => collect([
                                        $service->name,
                                        filled($service->service_code) ? $service->service_code : null,
                                        '$'.number_format((float) $service->default_fee, 2),
                                    ])->filter()->implode(' | '),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (Get $get): bool => blank($get('location_id')))
                            ->required()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $service = ClinicService::query()->find($state);
                                $set('appointment_type', $service?->name);
                                $set('duration_minutes', (int) ($service?->default_duration_minutes ?: 30));
                                $set('start_time', null);
                                $set('end_time', null);
                            })
                            ->createOptionForm([
                                Grid::make(2)->schema([
                                    TextInput::make('name')->label('Service name')->required()->maxLength(255),
                                    TextInput::make('service_code')->label('Service code')->maxLength(100),
                                    Select::make('default_duration_minutes')
                                        ->label('Default duration')
                                        ->options([15 => '15 minutes', 30 => '30 minutes', 45 => '45 minutes', 60 => '60 minutes', 90 => '90 minutes', 120 => '120 minutes'])
                                        ->default(30)
                                        ->required()
                                        ->native(false),
                                    TextInput::make('default_fee')->label('Default fee')->numeric()->prefix('$')->default(0)->minValue(0)->required(),
                                ]),
                            ])
                            ->createOptionUsing(fn (array $data, Get $get): int => ClinicService::query()->create([
                                'organization_id' => AppointmentWorkspaceScope::selectedOrganizationId(),
                                'clinic_id' => AppointmentWorkspaceScope::selectedClinicId(),
                                'location_id' => $get('location_id'),
                                'name' => $data['name'],
                                'service_code' => $data['service_code'] ?? null,
                                'default_duration_minutes' => $data['default_duration_minutes'] ?? 30,
                                'default_fee' => $data['default_fee'] ?? 0,
                                'status' => true,
                            ])->getKey())
                            ->createOptionAction(fn (Action $action) => $action
                                ->label('+ Add Service')
                                ->modalHeading('Add Clinic Service')
                                ->modalSubmitActionLabel('Create Service')
                                ->visible(auth()->user()?->canCreateClinicServices() ?? false))
                            ->columnSpan(6),
                        Hidden::make('appointment_type'),
                        Select::make('patient_id')
                            ->label('Select Patient')
                            ->options(fn (): array => Patient::query()
                                ->where('organization_id', AppointmentWorkspaceScope::selectedOrganizationId())
                                ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Patient $patient) => [$patient->id => $patient->full_name])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (Get $get): bool => blank($get('location_id')) || blank($get('provider_id')) || blank($get('clinic_service_id')))
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $policyId = PatientInsurancePolicy::query()
                                    ->where('organization_id', AppointmentWorkspaceScope::selectedOrganizationId())
                                    ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
                                    ->where('patient_id', $state)
                                    ->where('status', true)
                                    ->orderByRaw("case when coverage_priority = 'primary' then 0 else 1 end")
                                    ->value('id');

                                $set('patient_insurance_policy_id', $policyId);
                                $set('parent_appointment_id', null);
                                $set('is_follow_up', false);
                            })
                            ->createOptionForm([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('first_name')
                                            ->label('First name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('last_name')
                                            ->label('Last name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('phone')
                                            ->label('Phone')
                                            ->tel()
                                            ->maxLength(255),
                                        DatePicker::make('dob')
                                            ->label('Date of birth')
                                            ->native(false)
                                            ->maxDate(now()),
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),
                                        Select::make('gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                                'non_binary' => 'Non-binary',
                                                'prefer_not_to_say' => 'Prefer not to say',
                                            ])
                                            ->native(false),
                                    ]),
                            ])
                            ->createOptionUsing(function (array $data, Get $get): int {
                                $patient = Patient::query()->create([
                                    'organization_id' => AppointmentWorkspaceScope::selectedOrganizationId(),
                                    'clinic_id' => AppointmentWorkspaceScope::selectedClinicId(),
                                    'location_id' => $get('location_id'),
                                    'first_name' => $data['first_name'],
                                    'last_name' => $data['last_name'],
                                    'phone' => $data['phone'] ?? null,
                                    'dob' => $data['dob'] ?? null,
                                    'email' => $data['email'] ?? null,
                                    'gender' => $data['gender'] ?? null,
                                    'status' => true,
                                ]);

                                return $patient->getKey();
                            })
                            ->createOptionAction(fn (Action $action) => $action
                                ->label('+ Add Patient')
                                ->modalHeading('Add Patient')
                                ->modalSubmitActionLabel('Create Patient')
                                ->visible((auth()->user()?->canCreateClinicPatients() ?? false)
                                    || (auth()->user()?->canCreateClinicAppointments() ?? false)))
                            ->noSearchResultsMessage('Patient not found. Use + Add Patient.')
                            ->required()
                            ->columnSpan(6),
                        Hidden::make('patient_insurance_policy_id'),
                        Toggle::make('is_follow_up')
                            ->label('Is this a follow-up appointment?')
                            ->dehydrated(false)
                            ->default(fn ($record): bool => filled($record?->parent_appointment_id))
                            ->live()
                            ->columnSpan(6),
                        Select::make('parent_appointment_id')
                            ->label('Previous Appointment')
                            ->options(fn (Get $get, $record): array => Appointment::query()
                                ->where('organization_id', AppointmentWorkspaceScope::selectedOrganizationId())
                                ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
                                ->where('patient_id', $get('patient_id'))
                                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                ->latest('appointment_date')
                                ->limit(25)
                                ->get()
                                ->mapWithKeys(fn (Appointment $appointment): array => [
                                    $appointment->id => collect([
                                        $appointment->appointment_date?->format('M d, Y'),
                                        $appointment->appointment_type,
                                        str($appointment->status)->replace('_', ' ')->title(),
                                    ])->filter()->implode(' | '),
                                ])
                                ->all())
                            ->visible(fn (Get $get): bool => (bool) $get('is_follow_up'))
                            ->required(fn (Get $get): bool => (bool) $get('is_follow_up'))
                            ->searchable()
                            ->preload()
                            ->columnSpan(6),
                    ]),

                Hidden::make('appointment_date')
                    ->default(now()->toDateString())
                    ->required(),
                Hidden::make('start_time')
                    ->required(),
                Hidden::make('end_time')
                    ->required(),

                View::make('filament.clinic.resources.appointments.forms.booking-interaction')
                    ->viewData(fn (Get $get, mixed $livewire): array => [
                        'calendarMonthLabel' => $livewire->getCalendarMonthLabel(),
                        'calendarYearLabel' => $livewire->getCalendarYearLabel(),
                        'calendarWeeks' => $livewire->getCalendarWeeks(),
                        'selectedDate' => $get('appointment_date'),
                        'availableSlots' => $livewire->getAvailableSlots(),
                        'selectedSlotLabel' => $livewire->getSelectedSlotLabel(),
                        'availabilityMessage' => $livewire->getAvailabilityMessage(),
                        'displayTimezone' => $livewire->getDisplayTimezone(),
                        'selectedDuration' => (int) ($get('duration_minutes') ?: 30),
                    ])
                    ->columnSpanFull(),

                Grid::make(12)
                    ->schema([
                        Select::make('duration_minutes')
                            ->label('Slot Duration')
                            ->options([
                                15 => '15 mins',
                                30 => '30 mins',
                                45 => '45 mins',
                                60 => '60 mins',
                            ])
                            ->default(30)
                            ->native(false)
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $set('start_time', null);
                                $set('end_time', null);
                            })
                            ->columnSpan(4),
                        Select::make('status')
                            ->label('Status')
                            ->options(fn ($livewire): array => method_exists($livewire, 'create')
                                ? [
                                    'scheduled' => 'Scheduled',
                                    'confirmed' => 'Confirmed',
                                ]
                                : [
                                    'scheduled' => 'Scheduled',
                                    'confirmed' => 'Confirmed',
                                    'checked_in' => 'Checked in',
                                    'in_chair' => 'In chair',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                    'no_show' => 'No-show',
                                ])
                            ->default('scheduled')
                            ->native(false)
                            ->live()
                            ->required()
                            ->columnSpan(4),
                        Select::make('clinic_operatory_id')
                            ->label('Operatory / Chair')
                            ->options(fn (Get $get): array => ClinicOperatory::query()
                                ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
                                ->when(filled($get('location_id')), fn ($query) => $query->where('location_id', $get('location_id')))
                                ->where('status', true)
                                ->orderBy('display_order')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),
                    ]),

                Grid::make(12)
                    ->schema([
                        Toggle::make('verification_required')
                            ->label('Insurance verification required')
                            ->default(true)
                            ->live()
                            ->columnSpan(4),
                        Select::make('verification_processing_mode')
                            ->label('Verification handled by')
                            ->options(fn (Get $get): array => VerificationRequestForm::processingModeOptions(
                                AppointmentWorkspaceScope::selectedOrganizationId(),
                                AppointmentWorkspaceScope::selectedClinicId(),
                                filled($get('location_id')) ? (int) $get('location_id') : null,
                            ))
                            ->default(fn (): string => VerificationRequestForm::defaultProcessingMode(
                                AppointmentWorkspaceScope::selectedOrganizationId(),
                                AppointmentWorkspaceScope::selectedClinicId(),
                                AppointmentWorkspaceScope::mappedLocationId(),
                            ))
                            ->required(fn (Get $get): bool => (bool) $get('verification_required'))
                            ->visible(fn (Get $get): bool => (bool) $get('verification_required'))
                            ->native(false)
                            ->columnSpan(4),
                        Hidden::make('source')->default('manual'),
                        Textarea::make('reason_for_visit')
                            ->label('Reason for Visit')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpan(4),
                    ]),

                Grid::make(12)
                    ->schema([
                        Textarea::make('notes')
                            ->label('Clinical Notes')
                            ->rows(4)
                            ->columnSpan(7),
                        Textarea::make('arrival_notes')
                            ->label('Arrival Notes')
                            ->rows(4)
                            ->columnSpan(5),
                    ]),
            ])
            ->columns(1);
    }

    protected static function timeToMinutes(string $time): int
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, 0);

        return ((int) $hour * 60) + (int) $minute;
    }
}
