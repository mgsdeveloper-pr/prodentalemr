<?php

namespace App\Filament\Clinic\Resources\ClinicOperatories\Tables;

use App\Models\ClinicOperatory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ClinicOperatoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('code')->placeholder('-')->searchable(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->placeholder('-'),
                TextColumn::make('appointments_count')
                    ->label('Appointments')
                    ->badge()
                    ->color('info'),
                TextColumn::make('display_order')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive'),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('display_order')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicOperatories() ?? false),
                DeleteAction::make()
                    ->visible(fn (ClinicOperatory $record): bool => (auth()->user()?->canDeleteClinicOperatories() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicOperatories() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicOperatories() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicOperatories() ?? false),
                ]),
            ]);
    }
}
