<?php

namespace App\Filament\Saas\Resources\ManagedBillingServices\Tables;

use Filament\Actions\BulkActionGroup;
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

class ManagedBillingServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('service_level_agreement_hours')
                    ->label('SLA')
                    ->suffix(' hrs')
                    ->sortable(),
                TextColumn::make('default_priority')
                    ->badge(),
                TextColumn::make('enrollments_count')
                    ->label('Enrollments')
                    ->sortable(),
                TextColumn::make('work_items_count')
                    ->label('Work items')
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'verification' => 'Verification',
                        'coding' => 'Coding',
                        'claims' => 'Claims',
                        'ar' => 'AR Follow-up',
                        'payment_posting' => 'Payment Posting',
                        'credentialing' => 'Credentialing',
                        'analysis' => 'Analysis',
                        'integration' => 'PMS Integration',
                    ]),
                TrashedFilter::make(),
            ])
            ->defaultSort('name')
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
