<?php

namespace App\Filament\Saas\Resources\Providers\Schemas;

use App\Models\Clinic;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use App\Support\SaasSupportAccess;
use App\Support\SchedulingFormSchema;
use App\Support\UsLocationOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Context')
                    ->description('Place the provider under the correct organization, clinic, and primary location.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('organization_id')
                                    ->label('Organization')
                                    ->default(fn (): ?int => request()->integer('organization_id') ?: SaasSupportAccess::activeOrganizationId())
                                    ->options(fn (): array => Organization::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('clinic_id', null);
                                        $set('location_id', null);
                                        $set('user_id', null);
                                    })
                                    ->required(),
                                Select::make('clinic_id')
                                    ->label('Clinic')
                                    ->default(fn (): ?int => request()->integer('clinic_id') ?: SaasSupportAccess::activeClinicId())
                                    ->options(fn (Get $get): array => Clinic::query()
                                        ->when($get('organization_id'), fn ($query, $organizationId) => $query->where('organization_id', $organizationId))
                                        ->orderBy('clinic_name')
                                        ->pluck('clinic_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('location_id', null);
                                        $set('user_id', null);
                                    })
                                    ->required(),
                                Select::make('location_id')
                                    ->label('Primary location')
                                    ->options(fn (Get $get): array => Location::query()
                                        ->when($get('clinic_id'), fn ($query, $clinicId) => $query->where('clinic_id', $clinicId))
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),
                Section::make('Provider Identity')
                    ->description('Link this provider to an existing clinic user account.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Linked user')
                                    ->options(fn (Get $get): array => User::query()
                                        ->with('roles')
                                        ->when($get('organization_id'), fn ($query, $organizationId) => $query->where('organization_id', $organizationId))
                                        ->when($get('clinic_id'), fn ($query, $clinicId) => $query->where('clinic_id', $clinicId))
                                        ->whereHas('roles', fn ($query) => $query->whereIn('name', ['doctor', 'clinic_admin', 'clinic_manager']))
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (User $user) => [$user->id => $user->name . ' - ' . ($user->getPrimaryRoleLabel() ?? 'Clinic user')])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->unique(ignoreRecord: true),
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
                    ->description('Identifiers used for verification, credentialing, scheduling, and future claims workflows.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('license_number')
                                    ->label('State license number')
                                    ->maxLength(255),
                                Select::make('license_state')
                                    ->label('License state')
                                    ->options(UsLocationOptions::stateOptions())
                                    ->searchable()
                                    ->preload(),
                                DatePicker::make('license_expires_at')->label('License expires'),
                                TextInput::make('npi_number')
                                    ->label('Provider NPI')
                                    ->length(10)
                                    ->regex('/^\d{10}$/'),
                                TextInput::make('taxonomy_code')->label('Taxonomy code')->maxLength(20),
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
