<?php

namespace App\Filament\Clinic\Resources\PatientStatements\Schemas;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientStatementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Statement Snapshot')
                    ->description('A saved statement snapshot built from the patient ledger for this selected period.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('statement_number')
                                    ->label('Statement #'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('statement_date')
                                    ->label('Statement date')
                                    ->date('M d, Y'),
                                TextEntry::make('period_from')
                                    ->label('Period from')
                                    ->date('M d, Y'),
                                TextEntry::make('period_to')
                                    ->label('Period to')
                                    ->date('M d, Y'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('recipient_email')
                                    ->label('Recipient email')
                                    ->placeholder('-'),
                                TextEntry::make('sent_at')
                                    ->label('Sent at')
                                    ->dateTime('M d, Y h:i A')
                                    ->placeholder('-'),
                                TextEntry::make('sender.name')
                                    ->label('Last sent by')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Financial Summary')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                TextEntry::make('opening_balance')->label('Opening')->money('USD'),
                                TextEntry::make('charges_total')->label('Charges')->money('USD'),
                                TextEntry::make('payments_total')->label('Payments')->money('USD'),
                                TextEntry::make('adjustments_total')->label('Adjustments')->money('USD'),
                                TextEntry::make('closing_balance')->label('Closing')->money('USD'),
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
