<?php

namespace App\Filament\Saas\Resources\SubscriptionPlans\Tables;

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

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('max_clinics')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_users')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('included_modules_count')
                    ->label('Modules')
                    ->state(fn ($record): int => count($record->included_modules ?? []))
                    ->badge()
                    ->color('primary')
                    ->sortable(query: fn ($query, string $direction) => $query->orderByRaw('JSON_LENGTH(included_modules) ' . $direction)),
                TextColumn::make('subscriptions_count')
                    ->label('Subscriptions')
                    ->counts('subscriptions')
                    ->sortable(),
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
