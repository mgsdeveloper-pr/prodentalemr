<?php

namespace App\Filament\Saas\Resources\Organizations\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClinicsRelationManager extends RelationManager
{
    protected static string $relationship = 'clinics';

    protected static ?string $recordTitleAttribute = 'clinic_name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('clinic_name')
                ->label('Clinic name')
                ->required()
                ->maxLength(255),
            TextInput::make('clinic_code')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('timezone')
                ->required()
                ->default('America/New_York')
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
                TextColumn::make('clinic_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clinic_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('locations_count')
                    ->label('Locations')
                    ->counts('locations')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
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
