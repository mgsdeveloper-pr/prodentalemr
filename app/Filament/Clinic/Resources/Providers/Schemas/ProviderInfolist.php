<?php

namespace App\Filament\Clinic\Resources\Providers\Schemas;

use App\Models\Provider;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProviderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Provider Snapshot')
                    ->description('A quick overview of the clinician profile, access identity, and visit workload.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Provider name')
                                    ->columnSpan(2),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('appointments_count')
                                    ->label('Appointments')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('specialization')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('user.primary_role_label')
                                    ->label('Linked role')
                                    ->state(fn (Provider $record): ?string => $record->user?->getPrimaryRoleLabel())
                                    ->badge()
                                    ->color('success'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Professional Identifiers')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('license_number')
                                    ->label('State license')
                                    ->placeholder('-'),
                                TextEntry::make('npi_number')
                                    ->label('NPI number')
                                    ->placeholder('-'),
                                TextEntry::make('tax_id')
                                    ->label('Tax ID / EIN')
                                    ->placeholder('-'),
                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->placeholder('-')
                                    ->copyable(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
