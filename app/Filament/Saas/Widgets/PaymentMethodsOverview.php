<?php

namespace App\Filament\Saas\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class PaymentMethodsOverview extends ChartWidget
{
    protected ?string $heading = 'Payment Methods';

    protected function getData(): array
    {
        $methods = collect(Payment::methodOptions());
        $countsByMethod = Cache::remember('saas_dashboard.payment_methods_overview', now()->addSeconds(45), function (): array {
            return Payment::query()
                ->selectRaw('payment_method, COUNT(*) as aggregate')
                ->groupBy('payment_method')
                ->pluck('aggregate', 'payment_method')
                ->map(fn ($count) => (int) $count)
                ->all();
        });

        $labels = $methods->values()->all();
        $counts = $methods->keys()->map(
            fn (string $method): int => $countsByMethod[$method] ?? 0
        )->all();

        return [
            'datasets' => [[
                'label' => 'Payments',
                'data' => $counts,
                'backgroundColor' => ['#1d4ed8', '#16a34a', '#f59e0b', '#0f766e', '#9333ea', '#6366f1', '#ef4444', '#6b7280'],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
