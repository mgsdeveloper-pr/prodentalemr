<?php

namespace App\Filament\Clinic\Resources\PatientConsentForms\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientConsentFormInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Consent Snapshot')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('title'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('form_type')
                                    ->label('Form type')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('signed_by_name')
                                    ->label('Signed by')
                                    ->placeholder('-'),
                                TextEntry::make('relationship_to_patient')
                                    ->label('Relationship')
                                    ->placeholder('-'),
                                TextEntry::make('signed_on')
                                    ->label('Signed on')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('expires_on')
                                    ->label('Expires on')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('uploader.name')
                                    ->label('Uploaded by')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Consent Body')
                    ->schema([
                        TextEntry::make('body_text')->placeholder('-'),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')->placeholder('-'),
                    ]),
            ])
            ->columns(1);
    }
}
