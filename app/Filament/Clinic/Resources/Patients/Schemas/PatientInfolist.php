<?php

namespace App\Filament\Clinic\Resources\Patients\Schemas;

use App\Models\Patient;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Patient Snapshot')
                    ->description('A quick clinical and operational overview for front desk, provider, and admin teams.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('full_name')
                                    ->label('Patient name')
                                    ->state(fn (Patient $record): string => $record->full_name)
                                    ->columnSpan(2),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('appointments_count')
                                    ->label('Appointments')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('insurance_policies_count')
                                    ->label('Policies')
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('dob')
                                    ->label('Date of birth')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('age_label')
                                    ->label('Age')
                                    ->state(fn (Patient $record): ?string => $record->age_label)
                                    ->placeholder('-'),
                                TextEntry::make('gender')
                                    ->label('Gender')
                                    ->placeholder('-')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Contact Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('phone')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('email')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('address')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Coverage & Record Ownership')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('insurance_provider')
                                    ->label('Insurance provider')
                                    ->placeholder('-'),
                                TextEntry::make('insurance_number')
                                    ->label('Insurance number')
                                    ->placeholder('-'),
                                TextEntry::make('ledger_balance')
                                    ->label('Open balance')
                                    ->state(function (Patient $record): string {
                                        $debits = (float) ($record->ledger_debit_total ?? 0);
                                        $credits = (float) ($record->ledger_credit_total ?? 0);

                                        return '$' . number_format(max($debits - $credits, 0), 2);
                                    }),
                                TextEntry::make('guarantor_name')
                                    ->label('Guarantor')
                                    ->placeholder('-'),
                                TextEntry::make('creator.name')
                                    ->label('Created by')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
