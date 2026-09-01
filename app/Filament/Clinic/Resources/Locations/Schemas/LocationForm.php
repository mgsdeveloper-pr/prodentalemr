<?php

namespace App\Filament\Clinic\Resources\Locations\Schemas;

use App\Support\ClinicPanelScope;
use App\Support\SchedulingFormSchema;
use App\Support\UsLocationOptions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('clinic_id')->default(fn (): ?int => ClinicPanelScope::selectedClinicId()),
            Section::make('Location Details')
                ->description('Add an office location for scheduling, providers, and verification requests.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('location_name')->label('Location name')->required()->maxLength(255),
                        TextInput::make('phone')->tel()->maxLength(255),
                        Textarea::make('address')->columnSpanFull(),
                        Select::make('state')
                            ->options(UsLocationOptions::stateOptions())->searchable()->preload()->live()->required()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('city', null);
                                $set('zip_code', null);
                            }),
                        Select::make('city')
                            ->options(fn (Get $get): array => UsLocationOptions::cityOptions($get('state')))
                            ->searchable()->preload()->live()->required()
                            ->afterStateUpdated(fn (Get $get, Set $set, ?string $state) => $set('zip_code', UsLocationOptions::zipFor($get('state'), $state))),
                        TextInput::make('zip_code')->label('ZIP code')->maxLength(255),
                        Select::make('country')->options(['USA' => 'USA'])->default('USA')->disabled()->dehydrated()->required(),
                        Toggle::make('status')->label('Active location')->default(true)->required(),
                    ]),
                ]),
            SchedulingFormSchema::hours(),
            SchedulingFormSchema::exceptions(),
        ])->columns(1);
    }
}
