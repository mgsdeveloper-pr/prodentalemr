<?php

namespace App\Filament\Saas\Widgets;

use App\Models\Payment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentPayments extends TableWidget
{
    protected static ?string $heading = 'Recent Payments';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with('organization')
                    ->latest('payment_date')
                    ->latest('id')
                    ->limit(6)
            )
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->weight(FontWeight::Medium)
                    ->formatStateUsing(fn (?string $state, Payment $record): string => filled($state) ? $state : 'PAY-' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT))
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn (?string $state): string => Payment::METHOD_LABELS[$state ?? 'other'] ?? 'Other'),
                TextColumn::make('amount')
                    ->money('USD'),
                TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('payment_date', 'desc')
            ->defaultKeySort(false)
            ->paginated(false);
    }
}
