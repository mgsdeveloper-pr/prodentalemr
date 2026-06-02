<?php

namespace App\Filament\Clinic\Resources\ManagedServiceRequests\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManagedServiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('managedBillingService.name')
                    ->label('Managed service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('managedBillingService.category')
                    ->label('Category')
                    ->badge(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->placeholder('Whole clinic'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Start date')
                    ->date()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('end_date')
                    ->label('End date')
                    ->date()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
