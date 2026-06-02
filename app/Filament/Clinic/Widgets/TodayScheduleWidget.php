<?php

namespace App\Filament\Clinic\Widgets;

use App\Models\Appointment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TodayScheduleWidget extends TableWidget
{
    protected static ?string $heading = "Today's Schedule";

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('start_time')
                    ->label('Start'),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (Appointment $record): string => $record->patient?->full_name ?? 'Unknown patient'),
                TextColumn::make('provider.display_name')
                    ->label('Provider')
                    ->state(fn (Appointment $record): string => $record->provider?->display_name ?? 'Unknown provider'),
                TextColumn::make('operatory.name')
                    ->label('Operatory')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
            ])
            ->defaultSort('start_time')
            ->paginated([8]);
    }

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        return Appointment::query()
            ->with(['patient', 'provider.user', 'operatory'])
            ->where('organization_id', $user?->organization_id)
            ->where('clinic_id', $user?->clinic_id)
            ->whereDate('appointment_date', today());
    }
}
