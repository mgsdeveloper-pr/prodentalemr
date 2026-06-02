<?php

namespace App\Filament\Saas\Resources\ServiceItems\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service Overview')
                    ->description('Default billing information for this reusable service line item.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Service name')
                                    ->columnSpan(2),
                                TextEntry::make('default_price')
                                    ->money('USD')
                                    ->badge()
                                    ->color('success'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('invoice_items_count')
                                    ->label('Used on invoices')
                                    ->state(fn ($record): int => $record->invoiceItems()->count())
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),
                Section::make('Description')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('description')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
