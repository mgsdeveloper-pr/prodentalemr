<?php

namespace App\Filament\Clinic\Resources\Locations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Location Overview')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('location_name')->label('Location')->columnSpan(2),
                    IconEntry::make('status')->label('Active')->boolean(),
                    TextEntry::make('phone')->placeholder('-'),
                    TextEntry::make('city')->placeholder('-'),
                    TextEntry::make('state')->placeholder('-'),
                    TextEntry::make('address')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('zip_code')->label('ZIP code')->placeholder('-'),
                    TextEntry::make('country')->placeholder('-'),
                ]),
            ]),
        ])->columns(1);
    }
}
