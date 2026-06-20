<?php

namespace App\Filament\Saas\Resources\SaasEntitlementAuditLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SaasEntitlementAuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('actorUser.name')
                    ->label('Changed by')
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->replace('_', ' ')->headline()->toString() : '-')
                    ->color('warning'),
                TextColumn::make('entity_type')
                    ->label('Entity')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->searchable(),
                TextColumn::make('notes')
                    ->label('Changed fields')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->toggleable(),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->toggleable(),
                TextColumn::make('subscriptionPlan.name')
                    ->label('Plan')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Event')
                    ->options([
                        'entitlement_updated' => 'Entitlement Updated',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
