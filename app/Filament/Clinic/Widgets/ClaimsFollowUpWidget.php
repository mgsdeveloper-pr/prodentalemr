<?php

namespace App\Filament\Clinic\Widgets;

use App\Models\PatientInsuranceClaim;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ClaimsFollowUpWidget extends TableWidget
{
    protected static ?string $heading = 'Claims Follow-up';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('claim_number')
                    ->label('Claim #'),
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->state(fn (PatientInsuranceClaim $record): string => $record->patient?->full_name ?? 'Unknown patient'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('billed_amount')
                    ->label('Billed')
                    ->money('USD'),
                TextColumn::make('claim_date')
                    ->label('Claim date')
                    ->date('M d, Y'),
            ])
            ->defaultSort('claim_date', 'desc')
            ->paginated([6]);
    }

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        return PatientInsuranceClaim::query()
            ->with('patient')
            ->where('organization_id', $user?->organization_id)
            ->where('clinic_id', $user?->clinic_id)
            ->whereIn('status', ['ready', 'submitted', 'pending', 'partially_paid'])
            ->limit(20);
    }
}
