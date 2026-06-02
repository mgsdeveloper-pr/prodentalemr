<?php

namespace App\Filament\Clinic\Resources\PatientInsurancePolicies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientInsurancePolicyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Coverage Snapshot')
                    ->description('A coverage profile that front-desk and billing staff can review quickly before scheduling, treatment presentation, or payment posting.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('coverage_priority')
                                    ->label('Priority')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->title()->toString() : '-'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => (bool) $state ? 'Active' : 'Inactive'),
                                TextEntry::make('insurance_company')
                                    ->label('Insurance company')
                                    ->columnSpan(2),
                                TextEntry::make('plan_name')
                                    ->label('Plan name')
                                    ->placeholder('-'),
                                TextEntry::make('member_id')
                                    ->label('Member ID'),
                                TextEntry::make('group_number')
                                    ->label('Group number')
                                    ->placeholder('-'),
                                TextEntry::make('effective_date')
                                    ->label('Effective date')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('termination_date')
                                    ->label('Termination date')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Subscriber Details')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('subscriber_name')
                                    ->label('Subscriber name')
                                    ->columnSpan(2),
                                TextEntry::make('subscriber_relationship')
                                    ->label('Relationship')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->title()->toString() : '-'),
                                TextEntry::make('subscriber_dob')
                                    ->label('Subscriber DOB')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('subscriber_employer')
                                    ->label('Subscriber employer')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('payer_phone')
                                    ->label('Payer phone')
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('creator.name')
                                    ->label('Created by')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Billing Notes')
                    ->schema([
                        TextEntry::make('claims_address')
                            ->label('Claims address')
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('-'),
                    ]),
            ])
            ->columns(1);
    }
}
