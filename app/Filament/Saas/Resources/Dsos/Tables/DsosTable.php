<?php

namespace App\Filament\Saas\Resources\Dsos\Tables;

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

class DsosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('DSO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('primary_contact_name')
                    ->label('Contact')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('organizations_count')
                    ->label('Organizations')
                    ->counts('organizations')
                    ->sortable(),
                TextColumn::make('clinics_count')
                    ->label('Clinics')
                    ->state(fn ($record): int => $record->clinics()->count())
                    ->sortable(),
                TextColumn::make('billing_mode')
                    ->label('Billing')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->replace('_', ' ')->headline()->toString() : '-')
                    ->toggleable(),
                TextColumn::make('service_status')
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->replace('_', ' ')->headline()->toString() : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'active', 'trial' => 'success',
                        'pending_setup' => 'warning',
                        'suspended', 'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('accountManager.name')
                    ->label('Manager')
                    ->toggleable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
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
