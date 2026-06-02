<?php

namespace App\Filament\Clinic\Resources\ClinicOperatories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClinicOperatoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Operatory Snapshot')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('code')->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive'),
                                TextEntry::make('display_order')
                                    ->label('Display order'),
                                TextEntry::make('appointments_count')
                                    ->label('Appointments')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')->placeholder('-'),
                    ]),
            ])
            ->columns(1);
    }
}
