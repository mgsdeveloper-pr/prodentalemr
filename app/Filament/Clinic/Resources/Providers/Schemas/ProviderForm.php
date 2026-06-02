<?php

namespace App\Filament\Clinic\Resources\Providers\Schemas;

use App\Models\Location;
use App\Models\User;
use Filament\Forms\Components\Hidden;
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
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Provider Identity')
                    ->description('Link the clinical provider profile to an existing clinic user account.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Linked user')
                                    ->options(fn (): array => User::query()
                                        ->with('roles')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->whereHas('roles', fn ($query) => $query->whereIn('name', ['doctor', 'clinic_admin', 'clinic_manager']))
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (User $user) => [$user->id => $user->name . ' · ' . ($user->getPrimaryRoleLabel() ?? 'Clinic user')])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Select::make('location_id')
                                    ->label('Primary location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
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
                                TextInput::make('npi_number')
                                    ->label('NPI number')
                                    ->maxLength(255),
                                TextInput::make('tax_id')
                                    ->label('Tax ID / EIN')
                                    ->maxLength(255),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
