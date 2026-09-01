<?php

namespace App\Filament\Saas\Resources\Providers\Schemas;

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
                    ->description('Client, clinic, provider identity, and operational status.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Provider name')
                                    ->columnSpan(2),
                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->copyable()
                                    ->placeholder('-'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('organization.name')
                                    ->label('Organization')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('clinic.clinic_name')
                                    ->label('Clinic')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
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
                            ]),
                    ]),
                Section::make('Professional Identifiers')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('license_number')
                                    ->label('State license')
                                    ->placeholder('-'),
                                TextEntry::make('license_state')->label('License state')->placeholder('-'),
                                TextEntry::make('license_expires_at')->label('License expires')->date()->placeholder('-'),
                                TextEntry::make('npi_number')
                                    ->label('Provider NPI')
                                    ->copyable()
                                    ->placeholder('-'),
                                TextEntry::make('taxonomy_code')->label('Taxonomy code')->placeholder('-'),
                                TextEntry::make('tax_id')
                                    ->label('Tax ID / EIN')
                                    ->formatStateUsing(fn (?string $state): string => self::mask($state))
                                    ->placeholder('-'),
                                TextEntry::make('dea_number')
                                    ->label('DEA number')
                                    ->formatStateUsing(fn (?string $state): string => self::mask($state))
                                    ->placeholder('-'),
                                TextEntry::make('credentialing_status')
                                    ->label('Credentialing')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'not_started')->replace('_', ' ')->title()->toString())
                                    ->badge(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    private static function mask(?string $value): string
    {
        return filled($value) ? str_repeat('*', max(strlen($value) - 4, 4)).substr($value, -4) : '-';
    }
}
