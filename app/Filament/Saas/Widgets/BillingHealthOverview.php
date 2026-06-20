<?php

namespace App\Filament\Saas\Widgets;

use App\Models\Invoice;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class BillingHealthOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = Cache::remember('saas_dashboard.billing_health', now()->addSeconds(45), function (): array {
            $unpaid = Invoice::query()
                ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
                ->where('balance_due', '>', 0);

            $overdue = Invoice::query()
                ->where('balance_due', '>', 0)
                ->where(fn ($query) => $query
                    ->where('status', 'overdue')
                    ->orWhereDate('due_date', '<', now()->toDateString()));

            return [
                'unpaid_count' => (clone $unpaid)->count(),
                'unpaid_balance' => (float) (clone $unpaid)->sum('balance_due'),
                'overdue_count' => (clone $overdue)->count(),
                'overdue_balance' => (float) (clone $overdue)->sum('balance_due'),
                'trial_count' => Subscription::query()
                    ->where(fn ($query) => $query
                        ->where('status', 'trial')
                        ->orWhere('service_status', 'trial'))
                    ->count(),
                'scheduled_changes' => Subscription::query()
                    ->whereIn('change_type', ['upgrade', 'downgrade'])
                    ->whereDate('effective_date', '>=', now()->toDateString())
                    ->count(),
                'upgrades' => Subscription::query()
                    ->where('change_type', 'upgrade')
                    ->whereDate('effective_date', '>=', now()->toDateString())
                    ->count(),
                'downgrades' => Subscription::query()
                    ->where('change_type', 'downgrade')
                    ->whereDate('effective_date', '>=', now()->toDateString())
                    ->count(),
                'ending_soon' => Subscription::query()
                    ->where('cancel_at_period_end', true)
                    ->count(),
                'paid_this_month' => Invoice::query()
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                    ->count(),
            ];
        });

        return [
            Stat::make('Open Receivables', '$' . number_format($stats['unpaid_balance'], 2))
                ->description(number_format($stats['unpaid_count']) . ' invoices still need collection')
                ->color($stats['unpaid_count'] > 0 ? 'warning' : 'success'),
            Stat::make('Overdue Exposure', '$' . number_format($stats['overdue_balance'], 2))
                ->description(number_format($stats['overdue_count']) . ' invoices need immediate follow-up')
                ->color($stats['overdue_count'] > 0 ? 'danger' : 'success'),
            Stat::make('Trial Subscriptions', number_format($stats['trial_count']))
                ->description('Customers currently evaluating services')
                ->color($stats['trial_count'] > 0 ? 'info' : 'gray'),
            Stat::make('Plan Movement', number_format($stats['scheduled_changes']))
                ->description("Upgrades {$stats['upgrades']} | Downgrades {$stats['downgrades']}")
                ->color($stats['scheduled_changes'] > 0 ? 'primary' : 'gray'),
            Stat::make('Retention Watch', number_format($stats['ending_soon']))
                ->description("Cancelling at period end | {$stats['paid_this_month']} invoices paid this month")
                ->color($stats['ending_soon'] > 0 ? 'danger' : 'success'),
        ];
    }
}
