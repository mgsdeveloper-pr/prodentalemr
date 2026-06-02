<?php

namespace App\Filament\Clinic\Resources\PatientLedgerEntries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientLedgerEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Posting Snapshot')
                    ->description('A ledger posting that can be traced back to the patient visit, treatment plan, or service line when needed.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('posted_on')
                                    ->label('Posted on')
                                    ->date('M d, Y'),
                                TextEntry::make('entry_type')
                                    ->label('Entry type')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->title()->toString() : '-'),
                                TextEntry::make('reference_number')
                                    ->label('Reference number')
                                    ->placeholder('-'),
                                TextEntry::make('description')
                                    ->label('Description')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                            ]),
                    ]),
                Section::make('Financial Breakdown')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                TextEntry::make('quantity')
                                    ->label('Qty')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('unit_amount')
                                    ->label('Unit amount')
                                    ->money('USD'),
                                TextEntry::make('debit_amount')
                                    ->label('Debit')
                                    ->money('USD'),
                                TextEntry::make('credit_amount')
                                    ->label('Credit')
                                    ->money('USD'),
                                TextEntry::make('balance_impact_label')
                                    ->label('Balance impact')
                                    ->state(fn ($record): string => $record->balance_impact_label),
                                TextEntry::make('insurance_portion')
                                    ->label('Insurance portion')
                                    ->money('USD'),
                                TextEntry::make('patient_portion')
                                    ->label('Patient portion')
                                    ->money('USD'),
                            ]),
                    ]),
                Section::make('Clinical Links')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('clinicService.name')
                                    ->label('Service')
                                    ->placeholder('-'),
                                TextEntry::make('creator.name')
                                    ->label('Posted by')
                                    ->placeholder('-'),
                                TextEntry::make('appointment.appointment_date')
                                    ->label('Appointment')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('encounter.encounter_date')
                                    ->label('Encounter')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('treatmentPlan.plan_number')
                                    ->label('Treatment plan')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->placeholder('-'),
                    ]),
            ])
            ->columns(1);
    }
}
