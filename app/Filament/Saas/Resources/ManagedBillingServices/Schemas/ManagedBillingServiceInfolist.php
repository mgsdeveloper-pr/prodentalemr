<?php

namespace App\Filament\Saas\Resources\ManagedBillingServices\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManagedBillingServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('slug'),
                                TextEntry::make('category'),
                                TextEntry::make('service_level_agreement_hours')
                                    ->label('SLA (hours)'),
                                TextEntry::make('default_priority'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                            ]),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
                Section::make('Requirements')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                IconEntry::make('requires_appointment')->boolean(),
                                IconEntry::make('requires_patient')->boolean(),
                                IconEntry::make('requires_policy')->boolean(),
                                IconEntry::make('requires_claim')->boolean(),
                            ]),
                    ]),
                Section::make('Usage')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('enrollments_count')
                                    ->label('Client enrollments')
                                    ->state(fn ($record) => $record->enrollments_count ?? $record->enrollments()->count()),
                                TextEntry::make('work_items_count')
                                    ->label('Work items')
                                    ->state(fn ($record) => $record->work_items_count ?? $record->workItems()->count()),
                            ]),
                    ]),
            ]);
    }
}
