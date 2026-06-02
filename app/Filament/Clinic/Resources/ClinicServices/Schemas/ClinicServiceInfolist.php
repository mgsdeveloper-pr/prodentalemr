<?php

namespace App\Filament\Clinic\Resources\ClinicServices\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClinicServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service Snapshot')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Service name')
                                    ->columnSpan(2),
                                TextEntry::make('service_code')
                                    ->label('Service code')
                                    ->placeholder('-'),
                                TextEntry::make('category')
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('All clinic locations'),
                                TextEntry::make('default_fee')
                                    ->label('Default fee')
                                    ->money('USD'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                            ]),
                    ]),
                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->placeholder('-'),
                    ]),
            ])
            ->columns(1);
    }
}
