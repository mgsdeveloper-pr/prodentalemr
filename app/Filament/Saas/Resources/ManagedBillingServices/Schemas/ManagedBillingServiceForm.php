<?php

namespace App\Filament\Saas\Resources\ManagedBillingServices\Schemas;

use App\Models\ManagedBillingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManagedBillingServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service Definition')
                    ->description('Define the managed billing services your SaaS operations team can provide across enrolled clients.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug()->toString())),
                                TextInput::make('slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                TextInput::make('service_level_agreement_hours')
                                    ->label('SLA (hours)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(24)
                                    ->required(),
                                Select::make('category')
                                    ->options(ManagedBillingService::CATEGORY_OPTIONS)
                                    ->native(false)
                                    ->required(),
                                Select::make('default_priority')
                                    ->options(ManagedBillingService::PRIORITY_OPTIONS)
                                    ->default('normal')
                                    ->native(false)
                                    ->required(),
                            ]),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Operational Requirements')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                Toggle::make('requires_appointment')
                                    ->label('Requires appointment')
                                    ->default(false),
                                Toggle::make('requires_patient')
                                    ->label('Requires patient')
                                    ->default(true),
                                Toggle::make('requires_policy')
                                    ->label('Requires policy')
                                    ->default(false),
                                Toggle::make('requires_claim')
                                    ->label('Requires claim')
                                    ->default(false),
                                Toggle::make('status')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
