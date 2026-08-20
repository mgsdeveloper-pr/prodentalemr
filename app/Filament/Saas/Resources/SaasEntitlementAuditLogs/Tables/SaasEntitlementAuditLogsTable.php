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
                    ->color('gray'),
                TextColumn::make('entity_type')
                    ->label('Entity')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->searchable(),
                TextColumn::make('entity_id')
                    ->label('Record')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label('Support reason / notes')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('change_summary')
                    ->label('Change summary')
                    ->state(function ($record): string {
                        $before = data_get($record->before_values, 'record', []);
                        $after = data_get($record->after_values, 'record', []);

                        if (! is_array($after) || $after === []) {
                            return '-';
                        }

                        return collect($after)
                            ->map(function ($value, string $key) use ($before): string {
                                $oldValue = data_get($before, $key, '-');
                                $newValue = filled($value) ? (string) $value : '-';

                                return str($key)->replace('_', ' ')->headline() . ': ' . $oldValue . ' -> ' . $newValue;
                            })
                            ->implode('; ');
                    })
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('support_session_id')
                    ->label('Support session')
                    ->state(fn ($record): string => (string) (data_get($record->after_values, 'support_session_id')
                        ?: data_get($record->before_values, 'support_session_id')
                        ?: '-'))
                    ->limit(12)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        'support_access_started' => 'Support Access Started',
                        'support_access_ended' => 'Support Access Ended',
                        'support_provider_created' => 'Support Provider Created',
                        'support_provider_updated' => 'Support Provider Updated',
                        'support_provider_deleted' => 'Support Provider Deleted',
                        'support_provider_restored' => 'Support Provider Restored',
                        'support_portal_credential_created' => 'Support Portal Credential Created',
                        'support_portal_credential_updated' => 'Support Portal Credential Updated',
                        'support_portal_credential_deleted' => 'Support Portal Credential Deleted',
                        'support_portal_credential_restored' => 'Support Portal Credential Restored',
                        'support_document_previewed' => 'Support Document Previewed',
                        'support_document_downloaded' => 'Support Document Downloaded',
                        'support_clinic_template_settings_updated' => 'Support Clinic Template Settings Updated',
                        'ada_code_created' => 'ADA/CDT Code Created',
                        'ada_code_updated' => 'ADA/CDT Code Updated',
                        'ada_code_removed_by_ada' => 'ADA/CDT Code Removed by ADA',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
