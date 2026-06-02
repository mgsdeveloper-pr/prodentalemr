<?php

namespace App\Filament\Clinic\Resources\Encounters\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EncounterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Encounter Snapshot')
                    ->description('Core patient, provider, location, and visit context for this clinical note.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->state(fn ($record): string => $record->provider?->display_name ?? 'Unknown provider'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'finalized' => 'success',
                                        'in_progress' => 'warning',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('encounter_date')
                                    ->label('Encounter date')
                                    ->date('M d, Y'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('chief_complaint')
                                    ->label('Chief complaint')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('appointment_id')
                                    ->label('Linked appointment')
                                    ->state(fn ($record): ?string => $record->appointment?->appointment_date?->format('M d, Y'))
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('SOAP Notes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('subjective_note')
                                    ->label('Subjective')
                                    ->placeholder('-'),
                                TextEntry::make('objective_note')
                                    ->label('Objective')
                                    ->placeholder('-'),
                                TextEntry::make('assessment_note')
                                    ->label('Assessment')
                                    ->placeholder('-'),
                                TextEntry::make('plan_note')
                                    ->label('Plan')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Vitals, Prescriptions & Follow-up')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('blood_pressure')
                                    ->label('Blood pressure')
                                    ->placeholder('-'),
                                TextEntry::make('heart_rate')
                                    ->label('Heart rate')
                                    ->placeholder('-'),
                                TextEntry::make('temperature')
                                    ->label('Temperature')
                                    ->placeholder('-'),
                                TextEntry::make('weight')
                                    ->label('Weight')
                                    ->placeholder('-'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('prescriptions')
                                    ->placeholder('-'),
                                TextEntry::make('follow_up_instructions')
                                    ->label('Follow-up instructions')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
