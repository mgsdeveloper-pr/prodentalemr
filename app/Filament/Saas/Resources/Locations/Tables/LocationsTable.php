<?php

namespace App\Filament\Saas\Resources\Locations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clinic.organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('state')
                    ->searchable(),
                TextColumn::make('zip_code')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
