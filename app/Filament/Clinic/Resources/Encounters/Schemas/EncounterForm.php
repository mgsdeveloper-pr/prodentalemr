<?php

namespace App\Filament\Clinic\Resources\Encounters\Schemas;

use App\Models\Appointment;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Provider;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EncounterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Encounter Setup')
                    ->description('Tie the note to the right patient, provider, appointment, and clinic location.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('encounter_date')
                                    ->label('Encounter date')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'in_progress' => 'In progress',
                                        'finalized' => 'Finalized',
                                    ])
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                Select::make('patient_id')
                                    ->label('Patient')
                                    ->options(fn (): array => Patient::query()
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Patient $patient) => [$patient->id => $patient->full_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('provider_id')
                                    ->label('Provider')
                                    ->options(fn (): array => Provider::query()
                                        ->with('user')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('id')
                                        ->get()
                                        ->mapWithKeys(fn (Provider $provider) => [$provider->id => $provider->display_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('appointment_id')
                                    ->label('Linked appointment')
                                    ->options(fn (): array => Appointment::query()
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('appointment_date')
                                        ->get()
                                        ->mapWithKeys(fn (Appointment $appointment) => [
                                            $appointment->id => $appointment->appointment_date?->format('M d, Y') . ' · ' . ($appointment->patient?->full_name ?? 'Patient') . ' · ' . ($appointment->appointment_type ?: 'Visit'),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('chief_complaint')
                                    ->label('Chief complaint')
                                    ->placeholder('Reason for visit')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('SOAP Notes')
                    ->description('Capture the clinical story, findings, assessment, and treatment plan in a structured format.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('subjective_note')
                                    ->label('Subjective')
                                    ->rows(5),
                                Textarea::make('objective_note')
                                    ->label('Objective')
                                    ->rows(5),
                                Textarea::make('assessment_note')
                                    ->label('Assessment')
                                    ->rows(5),
                                Textarea::make('plan_note')
                                    ->label('Plan')
                                    ->rows(5),
                            ]),
                    ]),
                Section::make('Vitals & Orders')
                    ->description('Add core measurements and follow-up items without forcing a more complex EHR workflow yet.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('blood_pressure')
                                    ->label('Blood pressure')
                                    ->placeholder('120/80'),
                                TextInput::make('heart_rate')
                                    ->label('Heart rate')
                                    ->placeholder('72 bpm'),
                                TextInput::make('temperature')
                                    ->label('Temperature')
                                    ->placeholder('98.6 F'),
                                TextInput::make('weight')
                                    ->label('Weight')
                                    ->placeholder('170 lb'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Textarea::make('prescriptions')
                                    ->label('Prescriptions')
                                    ->rows(4),
                                Textarea::make('follow_up_instructions')
                                    ->label('Follow-up instructions')
                                    ->rows(4),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
