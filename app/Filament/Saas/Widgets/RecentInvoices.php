<?php

namespace App\Filament\Saas\Widgets;

use App\Models\Invoice;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentInvoices extends TableWidget
{
    protected static ?string $heading = 'Recent Invoices';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->with('organization')
                    ->latest('created_at')
                    ->limit(6)
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->weight(FontWeight::Medium)
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('USD'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?? 'draft')->headline()->toString())
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'overdue' => 'danger',
                        'sent' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->date('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultKeySort(false)
            ->paginated(false);
    }
}
