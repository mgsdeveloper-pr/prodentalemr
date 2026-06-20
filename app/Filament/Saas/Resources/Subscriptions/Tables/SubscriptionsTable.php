<?php

namespace App\Filament\Saas\Resources\Subscriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscription_scope')
                    ->label('Scope')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'dso' => 'DSO',
                        'clinic' => 'Clinic',
                        'organization' => 'Organization',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'dso' => 'success',
                        'clinic' => 'info',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('billing_account')
                    ->label('Billing account')
                    ->state(fn ($record): string => match ($record->subscription_scope) {
                        'dso' => $record->dso?->name ?? '-',
                        'clinic' => $record->clinic?->clinic_name ?? '-',
                        default => $record->organization?->name ?? '-',
                    })
                    ->searchable(query: function ($query, string $search): void {
                        $query
                            ->whereHas('organization', fn ($organizationQuery) => $organizationQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('dso', fn ($dsoQuery) => $dsoQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('clinic', fn ($clinicQuery) => $clinicQuery->where('clinic_name', 'like', "%{$search}%"));
                    }),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dso.name')
                    ->label('DSO')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subscriptionPlan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('previousSubscriptionPlan.name')
                    ->label('Previous')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('change_type')
                    ->label('Change')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->replace('_', ' ')->headline()->toString() : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'upgrade' => 'success',
                        'downgrade' => 'warning',
                        'cancellation' => 'danger',
                        'renewal' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('service_status')
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->replace('_', ' ')->headline()->toString() : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'pending_setup' => 'warning',
                        'suspended', 'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('accountManager.name')
                    ->label('Manager')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
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
