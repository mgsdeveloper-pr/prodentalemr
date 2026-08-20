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
                                IconEntry::make('verification_services_enabled')
                                    ->label('Verification Services')
                                    ->boolean(),
                                IconEntry::make('clinic_operations_enabled')
                                    ->label('Clinic Operations')
                                    ->boolean(),
                                TextEntry::make('clinic_code')
                                    ->label('Clinic code')
                                    ->badge()
                                    ->color('gray'),
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
                                TextEntry::make('providers_count')
                                    ->label('Providers')
                                    ->state(fn ($record): int => $record->providers()->count())
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('verification_requests_count')
                                    ->label('Verification requests')
                                    ->state(fn ($record): int => $record->billingWorkItems()->count())
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),
                Section::make('Verification Services')
                    ->description('The verification configuration currently applied to this clinic.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('verification_service_status')
                                    ->label('Service status')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'not set')->replace('_', ' ')->headline()->toString())
                                    ->badge()
                                    ->color(fn (?string $state): string => in_array($state, ['active', 'trial'], true) ? 'success' : 'gray'),
                                TextEntry::make('managed_services_status')
                                    ->label('Managed service')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'not set')->replace('_', ' ')->headline()->toString())
                                    ->badge()
                                    ->color(fn (?string $state): string => in_array($state, ['active', 'trial'], true) ? 'success' : 'gray'),
                                TextEntry::make('verification_default_form_template')
                                    ->label('Default form')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'not selected')->replace('_', ' ')->headline()->toString()),
                                TextEntry::make('verification_pdf_output_mode')
                                    ->label('PDF output')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'standard')->replace('_', ' ')->headline()->toString()),
                                IconEntry::make('allow_verification_manager_template_edits')
                                    ->label('Manager template access')
                                    ->boolean(),
                                TextEntry::make('accountManager.name')
                                    ->label('Account manager')
                                    ->placeholder('Unassigned'),
                                TextEntry::make('trial_ends_at')
                                    ->label('Trial ends')
                                    ->date()
                                    ->placeholder('-'),
                                IconEntry::make('demo_mode')
                                    ->label('Demo mode')
                                    ->boolean(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
