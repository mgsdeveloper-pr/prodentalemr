<?php

namespace App\Filament\Saas\Resources\Clinics\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClinicInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Clinic Overview')
                    ->description('Operational identity and access footprint for this clinic.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('clinic_name')
                                    ->label('Clinic name')
                                    ->columnSpan(2),
                                TextEntry::make('organization.name')
                                    ->label('Organization')
                                    ->badge()
                                    ->color('gray'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('clinic_code')
                                    ->label('Clinic code')
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('timezone')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('locations_count')
                                    ->label('Locations')
                                    ->state(fn ($record): int => $record->locations()->count())
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('users_count')
                                    ->label('Users')
                                    ->state(fn ($record): int => $record->users()->count())
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
