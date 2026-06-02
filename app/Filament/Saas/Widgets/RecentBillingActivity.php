<?php

namespace App\Filament\Saas\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentBillingActivity extends TableWidget
{
    protected static ?string $heading = 'Recent Billing Activity';

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getActivityQuery())
            ->columns([
                TextColumn::make('activity_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable(),
                TextColumn::make('organization_name')
                    ->label('Organization')
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('USD'),
                TextColumn::make('activity_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('activity_at', 'desc')
            ->defaultKeySort(false)
            ->paginated(false);
    }

    protected function getActivityQuery(): Builder
    {
        $payments = Payment::query()
            ->leftJoin('organizations', 'organizations.id', '=', 'payments.organization_id')
            ->selectRaw('payments.id as id')
            ->selectRaw("'payment' as activity_type")
            ->selectRaw('payments.reference_number as reference')
            ->selectRaw('payments.amount as amount')
            ->selectRaw('payments.payment_date as activity_at')
            ->selectRaw('organizations.name as organization_name');

        $invoices = Invoice::query()
            ->leftJoin('organizations', 'organizations.id', '=', 'invoices.organization_id')
            ->selectRaw('invoices.id as id')
            ->selectRaw("'invoice' as activity_type")
            ->selectRaw('invoices.invoice_number as reference')
            ->selectRaw('invoices.total_amount as amount')
            ->selectRaw('invoices.created_at as activity_at')
            ->selectRaw('organizations.name as organization_name');

        return Payment::query()
            ->withoutGlobalScopes()
            ->fromSub(
                $payments->toBase()->unionAll($invoices->toBase()),
                'billing_activity'
            )
            ->select('billing_activity.*')
            ->orderByDesc('activity_at')
            ->limit(10);
    }
}
