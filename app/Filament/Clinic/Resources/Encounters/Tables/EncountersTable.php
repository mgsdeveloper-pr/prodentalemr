<?php

namespace App\Filament\Clinic\Resources\Encounters\Tables;

use App\Models\Encounter;
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

class EncountersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('encounter_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (Encounter $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('provider.display_name')
                    ->label('Provider')
                    ->state(fn (Encounter $record): string => $record->provider?->display_name ?? 'Unknown provider')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('provider.user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('chief_complaint')
                    ->label('Chief complaint')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'finalized' => 'success',
                        'in_progress' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label('Created by')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In progress',
                        'finalized' => 'Finalized',
                    ]),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('encounter_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicEncounters() ?? false),
                DeleteAction::make()
                    ->visible(fn (Encounter $record): bool => (auth()->user()?->canDeleteClinicEncounters() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicEncounters() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicEncounters() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicEncounters() ?? false),
                ]),
            ]);
    }
}
