<?php

namespace App\Filament\Saas\Resources\Dsos\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DsoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('DSO Overview')
                    ->description('Enterprise parent account, ownership footprint, and service health.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('DSO name')
                                    ->columnSpan(2),
                                TextEntry::make('account_code')
                                    ->label('Account code')
                                    ->placeholder('-'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('organizations_count')
                                    ->label('Organizations')
                                    ->state(fn ($record): int => $record->organizations()->count())
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('clinics_count')
                                    ->label('Clinics')
                                    ->state(fn ($record): int => $record->clinics()->count())
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('users_count')
                                    ->label('Users')
                                    ->state(fn ($record): int => $record->users()->count())
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('billing_mode')
                                    ->label('Billing mode')
                                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->replace('_', ' ')->headline()->toString() : '-')
                                    ->badge(),
                            ]),
                    ]),
                Section::make('Contact')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('primary_contact_name')
                                    ->label('Primary contact')
                                    ->placeholder('-'),
                                TextEntry::make('accountManager.name')
                                    ->label('Account manager')
                                    ->placeholder('-'),
                                TextEntry::make('email')
                                    ->copyable()
                                    ->placeholder('-'),
                                TextEntry::make('phone')
                                    ->copyable()
                                    ->placeholder('-'),
                                TextEntry::make('address')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
