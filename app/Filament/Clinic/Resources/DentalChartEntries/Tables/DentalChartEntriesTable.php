<?php

namespace App\Filament\Clinic\Resources\DentalChartEntries\Tables;

use App\Models\DentalChartEntry;
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

class DentalChartEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recorded_on')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (DentalChartEntry $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('tooth_number')
                    ->label('Tooth')
                    ->sortable(),
                TextColumn::make('tooth_surface')
                    ->label('Surface')
                    ->toggleable(),
                TextColumn::make('condition_code')
                    ->label('Condition')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-')
                    ->toggleable(),
                TextColumn::make('chart_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'planned' => 'warning',
                        'watch' => 'info',
                        'archived' => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('provider.display_name')
                    ->label('Provider')
                    ->state(fn (DentalChartEntry $record): ?string => $record->provider?->display_name)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('chart_type')
                    ->options(DentalChartEntry::CHART_TYPE_OPTIONS),
                SelectFilter::make('status')
                    ->options(DentalChartEntry::STATUS_OPTIONS),
                SelectFilter::make('condition_code')
                    ->options(DentalChartEntry::CONDITION_CODE_OPTIONS),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('recorded_on', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicDentalCharting() ?? false),
                DeleteAction::make()
                    ->visible(fn (DentalChartEntry $record): bool => (auth()->user()?->canDeleteClinicDentalCharting() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicDentalCharting() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicDentalCharting() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicDentalCharting() ?? false),
                ]),
            ]);
    }
}
