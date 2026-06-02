<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientServiceEnrollmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Enrollment')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('organization.name')
                                    ->label('Organization'),
                                TextEntry::make('clinic.clinic_name')
                                    ->label('Clinic')
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('managedBillingService.name')
                                    ->label('Managed service'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('work_items_count')
                                    ->label('Work items')
                                    ->state(fn ($record) => $record->work_items_count ?? $record->workItems()->count()),
                                TextEntry::make('start_date')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('end_date')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('creator.name')
                                    ->label('Created by')
                                    ->placeholder('-'),
                                TextEntry::make('sla_summary')
                                    ->label('Verification SLA')
                                    ->columnSpanFull(),
                            ]),
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
