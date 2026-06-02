<?php

namespace App\Filament\Clinic\Resources\ClinicServices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ClinicServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Service name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category')
                    ->toggleable(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->placeholder('All locations'),
                TextColumn::make('default_fee')
                    ->label('Default fee')
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
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
