<?php

namespace App\Filament\Clinic\Resources\PatientInsurancePolicies\Tables;

use App\Models\PatientInsurancePolicy;
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

class PatientInsurancePoliciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (PatientInsurancePolicy $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('insurance_company')
                    ->label('Insurance company')
                    ->searchable(),
                TextColumn::make('coverage_priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->title()->toString() : '-'),
                TextColumn::make('member_id')
                    ->label('Member ID')
                    ->searchable(),
                TextColumn::make('plan_name')
                    ->label('Plan')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => (bool) $state ? 'Active' : 'Inactive'),
                TextColumn::make('effective_date')
                    ->label('Effective')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('coverage_priority')
                    ->label('Priority')
                    ->options(PatientInsurancePolicy::PRIORITY_OPTIONS),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('effective_date', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicInsurancePolicies() ?? false),
                DeleteAction::make()
                    ->visible(fn (PatientInsurancePolicy $record): bool => (auth()->user()?->canDeleteClinicInsurancePolicies() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicInsurancePolicies() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicInsurancePolicies() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicInsurancePolicies() ?? false),
                ]),
            ]);
    }
}
