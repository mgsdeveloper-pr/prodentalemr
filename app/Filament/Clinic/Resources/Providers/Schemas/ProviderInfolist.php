<?php

namespace App\Filament\Clinic\Resources\Providers\Schemas;

use App\Models\Provider;
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
                                TextEntry::make('provider_status')
                                    ->label('Status')
                                    ->state(fn (Provider $record): string => $record->trashed()
                                        ? 'Deactivated'
                                        : ($record->status ? 'Active' : 'Inactive'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Active' => 'success',
                                        'Inactive' => 'warning',
                                        default => 'gray',
                                    }),
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
                                TextEntry::make('license_state')->label('License state')->placeholder('-'),
                                TextEntry::make('license_expires_at')->label('License expires')->date()->placeholder('-'),
                                TextEntry::make('npi_number')
                                    ->label('NPI number')
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
                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->placeholder('-')
                                    ->copyable(),
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
