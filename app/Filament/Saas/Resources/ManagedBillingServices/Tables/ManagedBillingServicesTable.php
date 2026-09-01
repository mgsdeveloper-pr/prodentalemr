<?php

namespace App\Filament\Saas\Resources\ManagedBillingServices\Tables;

use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use App\Models\ManagedBillingService;
use Filament\Actions\Action;
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
                    ->label('Service requests')
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
                Action::make('enrollClient')
                    ->label('Enroll Client')
                    ->icon('heroicon-o-user-plus')
                    ->url(fn (ManagedBillingService $record): string => ClientServiceEnrollmentResource::getUrl('create', [
                        'service' => $record->getKey(),
                    ]))
                    ->visible(fn (ManagedBillingService $record): bool => (bool) $record->status && ClientServiceEnrollmentResource::canCreate()),
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
