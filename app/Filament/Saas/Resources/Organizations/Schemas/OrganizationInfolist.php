<?php

namespace App\Filament\Saas\Resources\Organizations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization Overview')
                    ->description('Primary business identity, ownership, and readiness snapshot for this organization.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Organization name')
                                    ->columnSpan(2),
                                TextEntry::make('owner_name')
                                    ->label('Owner name')
                                    ->placeholder('-'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('clinics_count')
                                    ->label('Clinics')
                                    ->state(fn ($record): int => $record->clinics()->count())
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('locations_count')
                                    ->label('Locations')
                                    ->state(fn ($record): int => $record->locations()->count())
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('users_count')
                                    ->label('Users')
                                    ->state(fn ($record): int => $record->users()->count())
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ]),
                Section::make('Contact & Timeline')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('Email address')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('phone')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('address')
                                    ->label('Billing address')
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
