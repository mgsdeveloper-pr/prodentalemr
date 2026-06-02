<?php

namespace App\Filament\Saas\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class InvoiceStatusOverview extends ChartWidget
{
    protected ?string $heading = 'Invoice Status Overview';

    protected function getData(): array
    {
        $statuses = ['draft', 'sent', 'partial', 'paid', 'overdue', 'cancelled'];

        $countsByStatus = Cache::remember('saas_dashboard.invoice_status_overview', now()->addSeconds(45), function (): array {
            return Invoice::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->map(fn ($count) => (int) $count)
                ->all();
        });

        $counts = collect($statuses)->map(
            fn (string $status): int => $countsByStatus[$status] ?? 0
        );

        return [
            'datasets' => [[
                'label' => 'Invoices',
                'data' => $counts->all(),
                'backgroundColor' => ['#f59e0b', '#3b82f6', '#8b5cf6', '#16a34a', '#dc2626', '#6b7280'],
            ]],
            'labels' => ['Draft', 'Sent', 'Partial', 'Paid', 'Overdue', 'Cancelled'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
