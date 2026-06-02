<?php

namespace App\Filament\Clinic\Resources\PatientDocuments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Snapshot')
                    ->description('A patient attachment record with clinical context and storage metadata.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('title')
                                    ->state(fn ($record): string => $record->display_title)
                                    ->label('Title')
                                    ->columnSpan(2),
                                TextEntry::make('document_type')
                                    ->label('Type')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('file_size_label')
                                    ->label('File size')
                                    ->state(fn ($record): string => $record->file_size_label),
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->state(fn ($record): ?string => $record->provider?->display_name)
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('encounter.encounter_date')
                                    ->label('Encounter')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('uploader.name')
                                    ->label('Uploaded by')
                                    ->placeholder('-'),
                                TextEntry::make('original_name')
                                    ->label('Original file')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('mime_type')
                                    ->label('MIME type')
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
