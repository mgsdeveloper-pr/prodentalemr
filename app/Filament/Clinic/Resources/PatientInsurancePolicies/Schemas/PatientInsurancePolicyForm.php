<?php

namespace App\Filament\Clinic\Resources\PatientInsurancePolicies\Schemas;

use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientInsurancePolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Coverage Snapshot')
                    ->description('Capture the patient insurance profile cleanly so treatment planning and future claim workflows have reliable coverage data.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
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
                                Select::make('coverage_priority')
                                    ->label('Coverage priority')
                                    ->options(PatientInsurancePolicy::PRIORITY_OPTIONS)
                                    ->default('primary')
                                    ->native(false)
                                    ->required(),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('insurance_company')
                                    ->label('Insurance company')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('plan_name')
                                    ->label('Plan name')
                                    ->maxLength(255),
                                TextInput::make('member_id')
                                    ->label('Member ID')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('group_number')
                                    ->label('Group number')
                                    ->maxLength(255),
                                DatePicker::make('effective_date')
                                    ->label('Effective date')
                                    ->native(false),
                                DatePicker::make('termination_date')
                                    ->label('Termination date')
                                    ->native(false),
                            ]),
                    ]),
                Section::make('Subscriber Details')
                    ->description('Track the subscriber information that front-desk, clinical, and financial teams usually need for verification and billing follow-up.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('subscriber_name')
                                    ->label('Subscriber name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('subscriber_relationship')
                                    ->label('Relationship')
                                    ->options(PatientInsurancePolicy::RELATIONSHIP_OPTIONS)
                                    ->default('self')
                                    ->native(false)
                                    ->required(),
                                DatePicker::make('subscriber_dob')
                                    ->label('Subscriber DOB')
                                    ->native(false),
                                TextInput::make('subscriber_employer')
                                    ->label('Subscriber employer')
                                    ->maxLength(255),
                                TextInput::make('payer_phone')
                                    ->label('Payer phone')
                                    ->tel()
                                    ->maxLength(255),
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        1 => 'Active',
                                        0 => 'Inactive',
                                    ])
                                    ->default(1)
                                    ->native(false)
                                    ->required(),
                            ]),
                        Textarea::make('claims_address')
                            ->label('Claims address')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
