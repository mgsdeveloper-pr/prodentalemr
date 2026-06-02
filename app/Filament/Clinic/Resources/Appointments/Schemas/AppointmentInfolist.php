<?php

namespace App\Filament\Clinic\Resources\Appointments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Appointment Snapshot')
                    ->description('Key schedule, provider, and patient details for the upcoming or completed visit.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->state(fn ($record): string => $record->provider?->display_name ?? 'Unknown provider'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'confirmed', 'checked_in', 'in_chair' => 'info',
                                        'cancelled', 'no_show' => 'danger',
                                        default => 'warning',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('appointment_date')
                                    ->label('Date')
                                    ->date('M d, Y'),
                                TextEntry::make('start_time')
                                    ->label('Start time'),
                                TextEntry::make('end_time')
                                    ->label('End time')
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('operatory.name')
                                    ->label('Operatory')
                                    ->placeholder('-'),
                                TextEntry::make('appointment_type')
                                    ->label('Visit type')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('duration_minutes')
                                    ->label('Duration')
                                    ->formatStateUsing(fn ($state): string => filled($state) ? $state . ' min' : '-'),
                                TextEntry::make('checked_in_at')
                                    ->label('Checked in at')
                                    ->dateTime('M d, Y h:i A')
                                    ->placeholder('-'),
                                TextEntry::make('seated_at')
                                    ->label('Seated at')
                                    ->dateTime('M d, Y h:i A')
                                    ->placeholder('-'),
                                TextEntry::make('completed_at')
                                    ->label('Completed at')
                                    ->dateTime('M d, Y h:i A')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Coordination Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Clinical notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('arrival_notes')
                            ->label('Arrival notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
