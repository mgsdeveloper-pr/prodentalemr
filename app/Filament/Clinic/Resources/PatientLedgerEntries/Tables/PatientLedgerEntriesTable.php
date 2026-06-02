<?php

namespace App\Filament\Clinic\Resources\PatientLedgerEntries\Tables;

use App\Models\PatientLedgerEntry;
use Filament\Actions\Action;
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

class PatientLedgerEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('posted_on')
                    ->label('Posted')
                    ->date()
                    ->sortable(),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (PatientLedgerEntry $record): string => $record->patient?->full_name ?? 'Unknown patient')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('entry_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('credit_amount')
                    ->label('Credit')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('balance_impact_label')
                    ->label('Impact')
                    ->state(fn (PatientLedgerEntry $record): string => $record->balance_impact_label),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->title()->toString() : '-'),
            ])
            ->filters([
                SelectFilter::make('entry_type')
                    ->label('Type')
                    ->options(PatientLedgerEntry::ENTRY_TYPE_OPTIONS),
                SelectFilter::make('status')
                    ->options(PatientLedgerEntry::STATUS_OPTIONS),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                TrashedFilter::make(),
            ])
            ->defaultSort('posted_on', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('view_receipt')
                    ->label('View Receipt')
                    ->icon('heroicon-o-document')
                    ->url(fn (PatientLedgerEntry $record): string => route('clinic.patient-ledger-entries.receipt.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (PatientLedgerEntry $record): bool => $record->entry_type === 'patient_payment'),
                Action::make('download_receipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (PatientLedgerEntry $record): string => route('clinic.patient-ledger-entries.receipt.download', $record))
                    ->visible(fn (PatientLedgerEntry $record): bool => $record->entry_type === 'patient_payment'),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->canEditClinicPatientLedger() ?? false),
                DeleteAction::make()
                    ->visible(fn (PatientLedgerEntry $record): bool => (auth()->user()?->canDeleteClinicPatientLedger() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientLedger() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientLedger() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicPatientLedger() ?? false),
                ]),
            ]);
    }
}
