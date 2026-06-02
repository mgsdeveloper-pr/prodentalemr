<?php

namespace App\Filament\Saas\Resources\Clinics\RelationManagers;

use App\Support\UsLocationOptions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    protected static ?string $recordTitleAttribute = 'location_name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('state')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
