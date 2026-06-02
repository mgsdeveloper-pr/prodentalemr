<?php

namespace App\Filament\Clinic\Widgets;

use App\Models\TreatmentPlanItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UnscheduledTreatmentQueueWidget extends TableWidget
{
    protected static ?string $heading = 'Unscheduled Treatment Queue';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('treatmentPlan.patient.full_name')
                    ->label('Patient')
                    ->state(fn (TreatmentPlanItem $record): string => $record->treatmentPlan?->patient?->full_name ?? 'Unknown patient'),
                TextColumn::make('description')
                    ->label('Procedure'),
                TextColumn::make('tooth_number')
                    ->label('Tooth')
                    ->placeholder('-'),
                TextColumn::make('target_date')
                    ->label('Target')
                    ->date('M d, Y')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
            ])
            ->defaultSort('target_date')
            ->paginated([6]);
    }

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        return TreatmentPlanItem::query()
            ->with(['treatmentPlan.patient'])
            ->whereNull('appointment_id')
            ->whereIn('status', ['accepted', 'scheduled', 'proposed'])
            ->whereHas('treatmentPlan', function ($query) use ($user): void {
                $query
                    ->where('organization_id', $user?->organization_id)
                    ->where('clinic_id', $user?->clinic_id);
            })
            ->limit(20);
    }
}
