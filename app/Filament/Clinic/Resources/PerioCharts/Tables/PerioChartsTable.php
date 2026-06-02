<?php

namespace App\Filament\Clinic\Resources\PerioCharts\Tables;

use App\Models\PerioChart;
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

class PerioChartsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chart_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (PerioChart $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('provider.display_name')
                    ->label('Provider')
                    ->state(fn (PerioChart $record): ?string => $record->provider?->display_name)
                    ->toggleable(),
                TextColumn::make('exam_type')
                    ->label('Exam type')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'finalized' => 'success',
                        'in_progress' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('entries_count')
                    ->label('Teeth charted')
                    ->badge()
                    ->color('info'),
                TextColumn::make('plaque_level')
                    ->label('Plaque')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PerioChart::STATUS_OPTIONS),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('chart_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicPerioCharting() ?? false),
                DeleteAction::make()
                    ->visible(fn (PerioChart $record): bool => (auth()->user()?->canDeleteClinicPerioCharting() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicPerioCharting() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPerioCharting() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPerioCharting() ?? false),
                ]),
            ]);
    }
}
