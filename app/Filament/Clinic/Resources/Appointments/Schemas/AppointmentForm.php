<?php

namespace App\Filament\Clinic\Resources\Appointments\Schemas;

use App\Models\ClinicOperatory;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Provider;
use App\Support\ClinicPanelScope;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => ClinicPanelScope::selectedOrganizationId()),
                Hidden::make('clinic_id')
                    ->default(fn () => ClinicPanelScope::selectedClinicId()),
                Grid::make(12)
                    ->schema([
                        Select::make('location_id')
                            ->label('Select Clinic Location')
                            ->options(fn (): array => Location::query()
                                ->where('clinic_id', ClinicPanelScope::selectedClinicId())
                                ->orderBy('location_name')
                                ->pluck('location_name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('clinic_operatory_id', null))
                            ->required()
                            ->columnSpan(6),
                        Select::make('provider_id')
                            ->label('Select Doctor')
                            ->options(fn (): array => Provider::query()
                                ->with('user')
                                ->where('organization_id', ClinicPanelScope::selectedOrganizationId())
                                ->where('clinic_id', ClinicPanelScope::selectedClinicId())
                                ->where('status', true)
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (Provider $provider) => [$provider->id => $provider->display_name])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->columnSpan(6),
                        TextInput::make('appointment_type')
                            ->label('Select Service')
                            ->placeholder('General Consultation, Follow Up Visit')
                            ->maxLength(255)
                            ->live()
                            ->required()
                            ->columnSpan(6),
                        Select::make('patient_id')
                            ->label('Select Patient')
                            ->options(fn (): array => Patient::query()
                                ->where('organization_id', ClinicPanelScope::selectedOrganizationId())
                                ->where('clinic_id', ClinicPanelScope::selectedClinicId())
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Patient $patient) => [$patient->id => $patient->full_name])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
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
                                        TextInput::make('insurance_provider')
                                            ->label('Insurance provider')
                                            ->maxLength(255),
                                        TextInput::make('insurance_number')
                                            ->label('Insurance number')
                                            ->maxLength(255),
                                    ]),
                            ])
                            ->createOptionUsing(function (array $data, Get $get): int {
                                return Patient::query()->create([
                                    'organization_id' => ClinicPanelScope::selectedOrganizationId(),
                                    'clinic_id' => ClinicPanelScope::selectedClinicId(),
                                    'location_id' => $get('location_id'),
                                    'first_name' => $data['first_name'],
                                    'last_name' => $data['last_name'],
                                    'phone' => $data['phone'] ?? null,
                                    'dob' => $data['dob'] ?? null,
                                    'email' => $data['email'] ?? null,
                                    'gender' => $data['gender'] ?? null,
                                    'insurance_provider' => $data['insurance_provider'] ?? null,
                                    'insurance_number' => $data['insurance_number'] ?? null,
                                    'status' => true,
                                ])->getKey();
                            })
                            ->createOptionAction(fn (Action $action) => $action
                                ->label('+ Add Patient')
                                ->modalHeading('Add Patient')
                                ->modalSubmitActionLabel('Create Patient'))
                            ->noSearchResultsMessage('Patient not found. Use + Add Patient.')
                            ->required()
                            ->columnSpan(6),
                        Checkbox::make('is_follow_up')
                            ->label('Is this a follow-up appointment?')
                            ->dehydrated(false)
                            ->columnSpanFull(),
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
                            ->options([
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
                                ->where('clinic_id', ClinicPanelScope::selectedClinicId())
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
