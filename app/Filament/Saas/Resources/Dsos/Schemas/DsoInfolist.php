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
                                TextEntry::make('lifecycle_status')
                                    ->label('Lifecycle')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'active')->replace('_', ' ')->headline()->toString())
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('organizations_count')
                                    ->label('Organizations')
                                    ->state(fn ($record): int => $record->organizations()->count())
                                    ->badge()
                                    ->color('info'),
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
                                TextEntry::make('service_status')
                                    ->label('Service status')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'not set')->replace('_', ' ')->headline()->toString())
                                    ->badge()
                                    ->color(fn (?string $state): string => in_array($state, ['active', 'trial'], true) ? 'success' : 'gray'),
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
                                TextEntry::make('city')->placeholder('-'),
                                TextEntry::make('state')->placeholder('-'),
                                TextEntry::make('zip_code')->label('ZIP code')->placeholder('-'),
                                TextEntry::make('country')->placeholder('-'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
