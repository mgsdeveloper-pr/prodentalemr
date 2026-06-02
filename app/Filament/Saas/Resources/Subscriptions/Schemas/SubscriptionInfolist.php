<?php

namespace App\Filament\Saas\Resources\Subscriptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Overview')
                    ->description('Plan linkage, billing lifecycle, and renewal timing for this organization subscription.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('organization.name')
                                    ->label('Organization')
                                    ->columnSpan(2),
                                TextEntry::make('subscriptionPlan.name')
                                    ->label('Subscription plan')
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($state): string => match ($state) {
                                        'active' => 'success',
                                        'cancelled' => 'danger',
                                        'paused' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('start_date')
                                    ->date(),
                                TextEntry::make('end_date')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->placeholder('-'),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->placeholder('-'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
