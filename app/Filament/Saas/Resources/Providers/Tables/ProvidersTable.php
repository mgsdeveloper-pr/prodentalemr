<?php

namespace App\Filament\Saas\Resources\Providers\Tables;

use App\Filament\Saas\Resources\Providers\ProviderResource;
use App\Models\Provider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('specialization')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('npi_number')
                    ->label('Provider NPI')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('license_number')
                    ->label('License')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('appointments_count')
                    ->label('Visits')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name'),
                SelectFilter::make('clinic_id')
                    ->label('Clinic')
                    ->relationship('clinic', 'clinic_name'),
                SelectFilter::make('status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
                TrashedFilter::make(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Provider $record): bool => ProviderResource::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (Provider $record): bool => ! $record->trashed()
                        && ProviderResource::canDelete($record)),
                RestoreAction::make()
                    ->visible(fn (Provider $record): bool => ProviderResource::canRestore($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canPerformSaasModuleAction('providers', 'delete')
                            && \App\Support\SaasSupportAccess::activeOrganizationId() !== null),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canPerformSaasModuleAction('providers', 'delete')
                            && \App\Support\SaasSupportAccess::activeOrganizationId() !== null),
                ]),
            ]);
    }
}
