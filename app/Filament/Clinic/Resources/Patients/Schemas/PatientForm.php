<?php

namespace App\Filament\Clinic\Resources\Patients\Schemas;

use App\Models\Location;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Patient Identity')
                    ->description('Capture the core demographic information used across visits, scheduling, and billing.')
                    ->schema([
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
                                TextInput::make('pms_patient_id')
                                    ->label('PMS Patient ID')
                                    ->maxLength(255),
                                DatePicker::make('dob')
                                    ->label('Date of birth')
                                    ->native(false)
                                    ->maxDate(now()),
                                Select::make('gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                        'non_binary' => 'Non-binary',
                                        'prefer_not_to_say' => 'Prefer not to say',
                                    ])
                                    ->native(false)
                                    ->searchable(),
                                Select::make('location_id')
                                    ->label('Primary location')
                                    ->options(fn (): array => Location::query()
                                        ->when(auth()->user()?->clinic_id, fn ($query, $clinicId) => $query->where('clinic_id', $clinicId))
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Toggle::make('status')
                                    ->label('Active patient')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Contact & Address')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                Textarea::make('address')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Coverage & Responsible Party')
                    ->description('Store the insurer and guarantor details needed for patient coordination and future billing flows.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('insurance_provider')
                                    ->label('Insurance provider')
                                    ->maxLength(255),
                                TextInput::make('insurance_number')
                                    ->label('Insurance number')
                                    ->maxLength(255),
                                TextInput::make('guarantor_name')
                                    ->label('Guarantor name')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
