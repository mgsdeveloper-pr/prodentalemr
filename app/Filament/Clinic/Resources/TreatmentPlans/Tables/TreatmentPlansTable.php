<?php

namespace App\Filament\Clinic\Resources\TreatmentPlans\Tables;

use App\Models\TreatmentPlan;
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

class TreatmentPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan_number')
                    ->label('Plan #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (TreatmentPlan $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('provider.display_name')
                    ->label('Provider')
                    ->state(fn (TreatmentPlan $record): string => $record->provider?->display_name ?? 'Unknown provider')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('provider.user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'accepted', 'completed' => 'success',
                        'in_progress' => 'warning',
                        'declined' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('estimated_total')
                    ->money('USD')
                    ->label('Total est.')
                    ->sortable(),
                TextColumn::make('estimated_patient')
                    ->money('USD')
                    ->label('Patient est.')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'proposed' => 'Proposed',
                        'accepted' => 'Accepted',
                        'in_progress' => 'In progress',
                        'completed' => 'Completed',
                        'declined' => 'Declined',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('plan_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicTreatmentPlans() ?? false),
                DeleteAction::make()
                    ->visible(fn (TreatmentPlan $record): bool => (auth()->user()?->canDeleteClinicTreatmentPlans() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicTreatmentPlans() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicTreatmentPlans() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicTreatmentPlans() ?? false),
                ]),
            ]);
    }
}
