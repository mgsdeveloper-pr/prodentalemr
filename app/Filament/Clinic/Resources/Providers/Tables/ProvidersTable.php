<?php

namespace App\Filament\Clinic\Resources\Providers\Tables;

use App\Models\Provider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('specialization')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.primary_role_label')
                    ->label('Role')
                    ->state(fn (Provider $record): ?string => $record->user?->getPrimaryRoleLabel())
                    ->badge()
                    ->color('success'),
                TextColumn::make('appointments_count')
                    ->label('Visits')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('license_number')
                    ->label('License')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                SelectFilter::make('status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
                TrashedFilter::make(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicProviders() ?? false),
                DeleteAction::make()
                    ->visible(fn (Provider $record): bool => (auth()->user()?->canDeleteClinicProviders() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicProviders() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicProviders() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicProviders() ?? false),
                ]),
            ]);
    }
}
