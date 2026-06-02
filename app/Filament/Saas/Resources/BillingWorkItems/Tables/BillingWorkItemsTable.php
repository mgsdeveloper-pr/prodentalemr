<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\Tables;

use App\Models\BillingWorkItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BillingWorkItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(45),
                TextColumn::make('managedBillingService.name')
                    ->label('Service')
                    ->badge()
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (BillingWorkItem $record): string => $record->patient?->full_name ?? '-')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('assignedTo.name')
                    ->label('Assigned')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => BillingWorkItem::STATUS_OPTIONS[BillingWorkItem::normalizeStatus($state)] ?? str((string) $state)->headline()->toString())
                    ->sortable(),
                TextColumn::make('outcome_status')
                    ->label('Outcome')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('managed_billing_service_id')
                    ->label('Service')
                    ->relationship('managedBillingService', 'name'),
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name'),
                SelectFilter::make('status')
                    ->options(BillingWorkItem::STATUS_OPTIONS),
                SelectFilter::make('outcome_status')
                    ->label('Outcome')
                    ->options(BillingWorkItem::OUTCOME_STATUS_OPTIONS),
                SelectFilter::make('priority')
                    ->options(BillingWorkItem::PRIORITY_OPTIONS),
                SelectFilter::make('assigned_to')
                    ->label('Assignee')
                    ->relationship('assignedTo', 'name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('due_at', 'asc')
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
