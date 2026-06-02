<?php

namespace App\Filament\Clinic\Resources\PatientInsuranceClaims\Tables;

use App\Models\PatientInsuranceClaim;
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

class PatientInsuranceClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('claim_number')
                    ->label('Claim #')
                    ->searchable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (PatientInsuranceClaim $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('insurancePolicy.insurance_company')
                    ->label('Insurance')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('claim_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('billed_amount')
                    ->label('Billed')
                    ->money('USD'),
                TextColumn::make('line_items_count')
                    ->label('Lines')
                    ->counts('lineItems')
                    ->badge()
                    ->color('info'),
                TextColumn::make('claim_date')
                    ->label('Claim date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('claim_type')
                    ->label('Type')
                    ->options(PatientInsuranceClaim::CLAIM_TYPE_OPTIONS),
                SelectFilter::make('status')
                    ->options(PatientInsuranceClaim::STATUS_OPTIONS),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('claim_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicInsuranceClaims() ?? false),
                DeleteAction::make()
                    ->visible(fn (PatientInsuranceClaim $record): bool => (auth()->user()?->canDeleteClinicInsuranceClaims() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicInsuranceClaims() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicInsuranceClaims() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicInsuranceClaims() ?? false),
                ]),
            ]);
    }
}
