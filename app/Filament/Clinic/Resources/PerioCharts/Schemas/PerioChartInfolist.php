<?php

namespace App\Filament\Clinic\Resources\PerioCharts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PerioChartInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Perio Chart Snapshot')
                    ->description('A periodontal exam summary for follow-up comparison and clinical review.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->state(fn ($record): ?string => $record->provider?->display_name)
                                    ->placeholder('-'),
                                TextEntry::make('chart_date')
                                    ->label('Chart date')
                                    ->date('M d, Y'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'finalized' => 'success',
                                        'in_progress' => 'warning',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('exam_type')
                                    ->label('Exam type')
                                    ->placeholder('-'),
                                TextEntry::make('plaque_level')
                                    ->label('Plaque level')
                                    ->placeholder('-'),
                                TextEntry::make('entries_count')
                                    ->label('Teeth charted')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Clinical Summary')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('bleeding_notes')
                                    ->label('Bleeding notes')
                                    ->placeholder('-'),
                                TextEntry::make('diagnosis_summary')
                                    ->label('Diagnosis summary')
                                    ->placeholder('-'),
                                TextEntry::make('notes')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
