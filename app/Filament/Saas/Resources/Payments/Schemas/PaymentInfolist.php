<?php

namespace App\Filament\Saas\Resources\Payments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Overview')
                    ->description('Collection details, funding method, and audit trail for this payment record.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('amount')
                                    ->money('USD')
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('payment_method')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('payment_date')
                                    ->date(),
                                TextEntry::make('invoice.invoice_number')
                                    ->label('Invoice')
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('organization.name')
                                    ->label('Organization')
                                    ->columnSpan(2),
                                TextEntry::make('reference_number')
                                    ->label('Reference')
                                    ->placeholder('-'),
                                TextEntry::make('creator.name')
                                    ->label('Recorded by')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Notes & Timeline')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('notes')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->dateTime(),
                                TextEntry::make('deleted_at')
                                    ->dateTime()
                                    ->placeholder('-'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
