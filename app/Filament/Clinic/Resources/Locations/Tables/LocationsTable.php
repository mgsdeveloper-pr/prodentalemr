<?php

namespace App\Filament\Clinic\Resources\Locations\Tables;

use App\Filament\Clinic\Resources\Locations\LocationResource;
use App\Models\Location;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location_name')->label('Location')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('city')->searchable()->sortable(),
                TextColumn::make('state')->searchable(),
                TextColumn::make('zip_code')->label('ZIP code')->searchable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('location_status')
                    ->label('Status')
                    ->state(fn (Location $record): string => $record->trashed() ? 'Archived' : ($record->status ? 'Active' : 'Inactive'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (Location $record): bool => LocationResource::canEdit($record)),
                DeleteAction::make()->label('Archive')->visible(fn (Location $record): bool => ! $record->trashed() && LocationResource::canDelete($record)),
                RestoreAction::make()->visible(fn (Location $record): bool => $record->trashed() && LocationResource::canDelete($record)),
            ])
            ->emptyStateHeading('No locations added yet')
            ->emptyStateDescription('Add the clinic locations used by providers, appointments, and verification requests.');
    }
}
