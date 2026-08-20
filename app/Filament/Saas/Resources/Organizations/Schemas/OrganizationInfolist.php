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
                                TextEntry::make('dso.name')
                                    ->label('DSO')
                                    ->placeholder('Independent'),
                                TextEntry::make('owner_name')
                                    ->label('Owner name')
                                    ->placeholder('-'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('lifecycle_status')
                                    ->label('Lifecycle')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'active')->replace('_', ' ')->headline()->toString())
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('onboarding_status')
                                    ->label('Onboarding')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'not started')->replace('_', ' ')->headline()->toString())
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'in_progress' => 'info',
                                        default => 'warning',
                                    }),
                                TextEntry::make('accountManager.name')
                                    ->label('Account manager')
                                    ->placeholder('Unassigned'),
                                TextEntry::make('clinics_count')
                                    ->label('Clinics')
                                    ->state(fn ($record): int => $record->clinics()->count())
                                    ->badge()
                                    ->color('info'),
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
                                TextEntry::make('verification_requests_count')
                                    ->label('Verification requests')
                                    ->state(fn ($record): int => $record->billingWorkItems()->count())
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('subscriptions_count')
                                    ->label('Subscriptions')
                                    ->state(fn ($record): int => $record->subscriptions()->count())
                                    ->badge()
                                    ->color('gray'),
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
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('M d, Y h:i A'),
                                TextEntry::make('updated_at')
                                    ->label('Last updated')
                                    ->dateTime('M d, Y h:i A'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
