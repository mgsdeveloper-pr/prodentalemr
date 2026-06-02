<?php

namespace App\Filament\Clinic\Resources\TreatmentPlans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TreatmentPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Snapshot')
                    ->description('A high-level case overview for the care team and front desk before treatment acceptance or scheduling.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('plan_number')
                                    ->label('Plan number'),
                                TextEntry::make('plan_date')
                                    ->label('Plan date')
                                    ->date('M d, Y'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'accepted', 'completed' => 'success',
                                        'in_progress' => 'warning',
                                        'declined' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('priority')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('patient.full_name')
                                    ->label('Patient')
                                    ->state(fn ($record): string => $record->patient?->full_name ?? 'Unknown patient')
                                    ->columnSpan(2),
                                TextEntry::make('provider.display_name')
                                    ->label('Provider')
                                    ->state(fn ($record): string => $record->provider?->display_name ?? 'Unknown provider'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('-'),
                                TextEntry::make('title')
                                    ->label('Plan title')
                                    ->placeholder('-')
                                    ->columnSpan(2),
                                TextEntry::make('phase')
                                    ->placeholder('-')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                                TextEntry::make('items_count')
                                    ->label('Planned procedures')
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),
                Section::make('Estimate Summary')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('estimated_total')
                                    ->money('USD')
                                    ->label('Estimated total'),
                                TextEntry::make('estimated_insurance')
                                    ->money('USD')
                                    ->label('Insurance estimate'),
                                TextEntry::make('estimated_patient')
                                    ->money('USD')
                                    ->label('Patient estimate'),
                                TextEntry::make('accepted_at')
                                    ->label('Accepted on')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('appointment.appointment_date')
                                    ->label('Linked appointment')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                                TextEntry::make('encounter.encounter_date')
                                    ->label('Linked encounter')
                                    ->date('M d, Y')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Notes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('notes')
                                    ->label('Clinical / case notes')
                                    ->placeholder('-'),
                                TextEntry::make('acceptance_notes')
                                    ->label('Acceptance notes')
                                    ->placeholder('-'),
                            ]),
                    ]),
                Section::make('Scheduling Queue')
                    ->schema([
                        TextEntry::make('unscheduled_items_snapshot')
                            ->label('')
                            ->state(function ($record): string {
                                $items = $record->items;

                                if ($items->isEmpty()) {
                                    return 'No treatment plan items have been added yet.';
                                }

                                return $items->map(function ($item): string {
                                    $scheduleState = $item->appointment_id ? 'Scheduled' : 'Unscheduled';

                                    return collect([
                                        $item->description,
                                        $item->tooth_number ? 'Tooth ' . $item->tooth_number : null,
                                        $item->target_date?->format('M d, Y'),
                                        $scheduleState,
                                    ])->filter()->implode(' - ');
                                })->implode("\n");
                            })
                            ->html()
                            ->formatStateUsing(fn (string $state): string => nl2br(e($state))),
                    ]),
            ])
            ->columns(1);
    }
}
