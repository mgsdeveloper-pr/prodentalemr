<?php

namespace App\Filament\Saas\Resources\Locations\Schemas;

use App\Support\UsLocationOptions;
use App\Support\SchedulingFormSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Location Details')->schema([
                    Grid::make(2)->schema([
                Select::make('clinic_id')
                    ->label('Clinic')
                    ->relationship('clinic', 'clinic_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('location_name')
                    ->label('Location name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('address')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('state')
                    ->options(UsLocationOptions::stateOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('city', null);
                        $set('zip_code', null);
                    })
                    ->required(),
                Select::make('city')
                    ->options(fn (Get $get): array => UsLocationOptions::cityOptions($get('state')))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set, ?string $state) => $set('zip_code', UsLocationOptions::zipFor($get('state'), $state)))
                    ->required(),
                TextInput::make('zip_code')
                    ->label('ZIP code')
                    ->default(null)
                    ->maxLength(255),
                Select::make('country')
                    ->required()
                    ->default('USA')
                    ->options(['USA' => 'USA'])
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('phone')
                    ->tel()
                    ->default(null)
                    ->maxLength(255),
                Toggle::make('status')
                    ->label('Active')
                    ->default(true)
                    ->required(),
                    ]),
                ]),
                SchedulingFormSchema::hours(),
                SchedulingFormSchema::exceptions(),
            ]);
    }
}
