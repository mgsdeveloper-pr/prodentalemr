<?php

namespace App\Filament\Clinic\Resources\ManagedServiceRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManagedServiceRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Snapshot')
                    ->description('Service, ownership, and current request status.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('managedBillingService.name')->label('Service'),
                                TextEntry::make('managedBillingService.category')->label('Category')->badge(),
                                TextEntry::make('status')
                                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'pending')->replace('_', ' ')->headline()->toString())
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('created_at')->label('Requested on')->dateTime('M d, Y h:i A'),
                                TextEntry::make('organization.name')->label('Organization'),
                                TextEntry::make('clinic.clinic_name')->label('Clinic'),
                                TextEntry::make('location.location_name')->label('Location')->placeholder('Whole clinic'),
                                TextEntry::make('creator.name')->label('Requested by')->placeholder('-'),
                            ]),
                    ]),
                Section::make('Request Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Clinic notes')
                            ->placeholder('No additional request details were added.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
