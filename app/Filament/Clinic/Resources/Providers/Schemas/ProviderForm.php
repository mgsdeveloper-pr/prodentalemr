<?php

namespace App\Filament\Clinic\Resources\Providers\Schemas;

use App\Models\Location;
use App\Models\User;
use App\Support\ClinicPanelScope;
use App\Support\SchedulingFormSchema;
use App\Support\UsLocationOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => ClinicPanelScope::selectedOrganizationId()),
                Hidden::make('clinic_id')
                    ->default(fn () => ClinicPanelScope::selectedClinicId()),
                Section::make('Provider Identity')
                    ->description('Link the clinical provider profile to an existing clinic user account.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Linked user')
                                    ->options(fn (): array => User::query()
                                        ->with('roles')
                                        ->where('organization_id', ClinicPanelScope::selectedOrganizationId())
                                        ->where('clinic_id', ClinicPanelScope::selectedClinicId())
                                        ->whereHas('roles', fn ($query) => $query->whereIn('name', ['doctor', 'clinic_admin', 'clinic_manager']))
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (User $user) => [$user->id => $user->name.' · '.($user->getPrimaryRoleLabel() ?? 'Clinic user')])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Select::make('location_id')
                                    ->label('Primary location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', ClinicPanelScope::selectedClinicId())
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('specialization')
                                    ->label('Specialization')
                                    ->placeholder('General Dentistry, Orthodontics, Endodontics')
                                    ->maxLength(255),
                                Toggle::make('status')
                                    ->label('Active provider')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Professional Credentials')
                    ->description('Store identifiers needed for scheduling, credentialing, and future claims workflows.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('license_number')
                                    ->label('State license number')
                                    ->maxLength(255),
                                Select::make('license_state')
                                    ->label('License state')
                                    ->options(UsLocationOptions::stateOptions())
                                    ->searchable()
                                    ->preload(),
                                DatePicker::make('license_expires_at')
                                    ->label('License expires'),
                                TextInput::make('npi_number')
                                    ->label('NPI number')
                                    ->length(10)
                                    ->regex('/^\d{10}$/'),
                                TextInput::make('taxonomy_code')
                                    ->label('Taxonomy code')
                                    ->maxLength(20),
                                TextInput::make('tax_id')
                                    ->label('Tax ID / EIN')
                                    ->password()
                                    ->revealable()
                                    ->helperText('Encrypted at rest. Reveal only when required.')
                                    ->maxLength(20),
                                TextInput::make('dea_number')
                                    ->label('DEA number')
                                    ->password()
                                    ->revealable()
                                    ->helperText('Optional. Encrypted at rest.')
                                    ->maxLength(20),
                                Select::make('credentialing_status')
                                    ->label('Credentialing status')
                                    ->options([
                                        'not_started' => 'Not started',
                                        'in_progress' => 'In progress',
                                        'active' => 'Active',
                                        'expiring' => 'Expiring',
                                        'expired' => 'Expired',
                                        'suspended' => 'Suspended',
                                    ])
                                    ->default('not_started')
                                    ->native(false),
                                DatePicker::make('credentialing_effective_at')->label('Credentialing effective'),
                                DatePicker::make('credentialing_expires_at')->label('Credentialing expires'),
                                TextInput::make('scheduling_buffer_minutes')
                                    ->label('Scheduling buffer (minutes)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(120),
                            ]),
                        Repeater::make('additional_licenses')
                            ->label('Additional state licenses')
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('state')->options(UsLocationOptions::stateOptions())->searchable()->preload()->required(),
                                    TextInput::make('number')->label('License number')->required()->maxLength(255),
                                    DatePicker::make('expires_at')->label('Expires'),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add another license')
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
                SchedulingFormSchema::hours(),
                SchedulingFormSchema::exceptions(),
            ])
            ->columns(1);
    }
}
