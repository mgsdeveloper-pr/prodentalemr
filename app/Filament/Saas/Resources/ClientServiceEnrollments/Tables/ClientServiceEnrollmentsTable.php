<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments\Tables;

use App\Models\ClientServiceEnrollment;
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

class ClientServiceEnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('managedBillingService.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('work_items_count')
                    ->label('Work items')
                    ->sortable(),
                TextColumn::make('sla_summary')
                    ->label('Verification SLA')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ClientServiceEnrollment::STATUS_OPTIONS),
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name'),
                SelectFilter::make('managed_billing_service_id')
                    ->label('Managed service')
                    ->relationship('managedBillingService', 'name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
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
