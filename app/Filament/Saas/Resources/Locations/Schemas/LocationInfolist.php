<?php

namespace App\Filament\Saas\Resources\Locations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Location Overview')
                    ->description('Geographic, clinic, and contact details for this operational location.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('location_name')
                                    ->label('Location name')
                                    ->columnSpan(2),
                                TextEntry::make('clinic.organization.name')
                                    ->label('Organization')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('clinic.clinic_name')
                                    ->label('Clinic')
                                    ->badge()
                                    ->color('info'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('phone')
                                    ->placeholder('-')
                                    ->copyable(),
                            ]),
                    ]),
                Section::make('Address Details')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('address')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                                TextEntry::make('city')
                                    ->placeholder('-'),
                                TextEntry::make('state')
                                    ->placeholder('-'),
                                TextEntry::make('zip_code')
                                    ->label('ZIP code')
                                    ->placeholder('-'),
                                TextEntry::make('country')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
