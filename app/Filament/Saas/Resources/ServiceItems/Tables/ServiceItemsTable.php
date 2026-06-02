<?php

namespace App\Filament\Saas\Resources\ServiceItems\Tables;

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

class ServiceItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Service name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('default_price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('invoice_items_count')
                    ->label('Invoice usage')
                    ->counts('invoiceItems')
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
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
