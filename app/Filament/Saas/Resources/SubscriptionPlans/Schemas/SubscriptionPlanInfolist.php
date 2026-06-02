<?php

namespace App\Filament\Saas\Resources\SubscriptionPlans\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Overview')
                    ->description('Commercial structure and current usage footprint for this subscription plan.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Plan name')
                                    ->columnSpan(2),
                                TextEntry::make('price')
                                    ->money('USD')
                                    ->badge()
                                    ->color('success'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('max_clinics')
                                    ->numeric()
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('max_users')
                                    ->numeric()
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('included_modules_count')
                                    ->label('Modules')
                                    ->state(fn ($record): int => count($record->included_modules ?? []))
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('subscriptions_count')
                                    ->label('Subscriptions')
                                    ->state(fn ($record): int => $record->subscriptions()->count())
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ]),
                Section::make('Included Modules')
                    ->description('Clinic-side product modules included in this subscription plan.')
                    ->schema([
                        TextEntry::make('included_module_labels')
                            ->label('')
                            ->badge()
                            ->separator(', ')
                            ->state(fn ($record): array => $record->included_module_labels)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
