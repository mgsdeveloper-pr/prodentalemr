<?php

namespace App\Filament\Clinic\Resources\DentalChartEntries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DentalChartEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chart Snapshot')
                    ->description('A tooth-level clinical record that can later feed an odontogram or case review workflow.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->state(fn ($record): string => $record->provider?->display_name ?? 'Unknown provider')
                                    ->placeholder('-'),
                                TextEntry::make('recorded_on')
                                    ->label('Recorded on')
                                    ->date('M d, Y'),
                                TextEntry::make('tooth_number')
                                    ->label('Tooth'),
                                TextEntry::make('tooth_surface')
                                    ->label('Surface')
                                    ->placeholder('-'),
                                TextEntry::make('chart_type')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'planned' => 'warning',
                                        'watch' => 'info',
                                        'archived' => 'gray',
                                        default => 'danger',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('condition_code')
                                    ->label('Condition / procedure')
                                    ->placeholder('-')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-')
                                    ->columnSpan(2),
                                TextEntry::make('description')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                            ]),
                    ]),
                Section::make('Linked Clinical Context')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('clinicService.name')
                                    ->label('Service')
                                    ->placeholder('-'),
                                TextEntry::make('creator.name')
                                    ->label('Created by')
                                    ->placeholder('-'),
                                TextEntry::make('treatmentPlan.plan_number')
                                    ->label('Treatment plan')
                                    ->placeholder('-'),
                                TextEntry::make('encounter.encounter_date')
                                    ->label('Encounter')
                                    ->date('M d, Y')
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
