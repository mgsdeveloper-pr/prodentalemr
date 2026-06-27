<?php

namespace App\Filament\Saas\Widgets;

use App\Models\Clinic;
use App\Models\Dso;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class SaasBusinessOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = Cache::remember('saas_dashboard.business_overview.v2', now()->addSeconds(45), function (): array {
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();
            $startOfLastMonth = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $endOfLastMonth = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
            $startOfYear = now()->startOfYear()->toDateString();
            $endOfYear = now()->endOfYear()->toDateString();

            $activeSubscriptions = Subscription::query()
                ->whereIn('subscriptions.status', ['active', 'trial'])
                ->whereIn('subscriptions.service_status', ['active', 'trial']);

            $estimatedMrr = (float) (clone $activeSubscriptions)
                ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.subscription_plan_id')
                ->sum('subscription_plans.price');

            $paymentThisMonth = (float) Payment::query()
                ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');
            $paymentLastMonth = (float) Payment::query()
                ->whereBetween('payment_date', [$startOfLastMonth, $endOfLastMonth])
                ->sum('amount');

            $newClientsThisMonth = Organization::query()
                ->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
                ->count();
            $newClientsLastMonth = Organization::query()
                ->whereBetween('created_at', [$startOfLastMonth . ' 00:00:00', $endOfLastMonth . ' 23:59:59'])
                ->count();

            $planMix = SubscriptionPlan::query()
                ->leftJoin('subscriptions', function ($join): void {
                    $join->on('subscription_plans.id', '=', 'subscriptions.subscription_plan_id')
                        ->whereNull('subscriptions.deleted_at')
                        ->whereIn('subscriptions.status', ['active', 'trial'])
                        ->whereIn('subscriptions.service_status', ['active', 'trial']);
                })
                ->selectRaw('subscription_plans.name, COUNT(subscriptions.id) as subscription_count')
                ->groupBy('subscription_plans.id', 'subscription_plans.name')
                ->orderByDesc('subscription_count')
                ->limit(3)
                ->pluck('subscription_count', 'name')
                ->map(fn ($count): int => (int) $count)
                ->all();

            $unpaidInvoices = Invoice::query()
                ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
                ->where('balance_due', '>', 0);
            $overdueInvoices = Invoice::query()
                ->where('balance_due', '>', 0)
                ->where(fn ($query) => $query
                    ->where('status', 'overdue')
                    ->orWhereDate('due_date', '<', now()->toDateString()));

            return [
                'active_clients' => Organization::query()->where('status', true)->count(),
                'total_clients' => Organization::query()->count(),
                'active_dsos' => Dso::query()->where('status', true)->count(),
                'enterprise_clinics' => Clinic::query()
                    ->whereHas('organization', fn ($query) => $query->whereNotNull('dso_id'))
                    ->count(),
                'total_clinics' => Clinic::query()->count(),
                'pms_clinics' => Clinic::query()->where('clinic_operations_enabled', true)->count(),
                'verification_clinics' => Clinic::query()->where('verification_services_enabled', true)->count(),
                'dual_service_clinics' => Clinic::query()
                    ->where('clinic_operations_enabled', true)
                    ->where('verification_services_enabled', true)
                    ->count(),
                'pending_onboarding' => Organization::query()
                    ->where(fn ($query) => $query
                        ->where('lifecycle_status', 'onboarding')
                        ->orWhere('onboarding_status', '!=', 'complete'))
                    ->count(),
                'pending_setup' => Subscription::query()->where('service_status', 'pending_setup')->count(),
                'suspended_services' => Subscription::query()->where('service_status', 'suspended')->count(),
                'trials_ending_soon' => Subscription::query()
                    ->where(fn ($query) => $query
                        ->where('status', 'trial')
                        ->orWhere('service_status', 'trial'))
                    ->whereBetween('trial_ends_at', [now()->toDateString(), now()->addDays(14)->toDateString()])
                    ->count(),
                'active_subscriptions' => (clone $activeSubscriptions)->count(),
                'estimated_mrr' => $estimatedMrr,
                'payments_this_month' => $paymentThisMonth,
                'payments_last_month' => $paymentLastMonth,
                'payments_ytd' => (float) Payment::query()
                    ->whereBetween('payment_date', [$startOfYear, $endOfYear])
                    ->sum('amount'),
                'new_clients_this_month' => $newClientsThisMonth,
                'new_clients_last_month' => $newClientsLastMonth,
                'unpaid_count' => (clone $unpaidInvoices)->count(),
                'unpaid_balance' => (float) (clone $unpaidInvoices)->sum('balance_due'),
                'overdue_count' => (clone $overdueInvoices)->count(),
                'overdue_balance' => (float) (clone $overdueInvoices)->sum('balance_due'),
                'plan_mix' => $planMix,
            ];
        });

        $collectionDelta = $stats['payments_this_month'] - $stats['payments_last_month'];
        $clientDelta = $stats['new_clients_this_month'] - $stats['new_clients_last_month'];
        $serviceRisk = $stats['pending_onboarding'] + $stats['pending_setup'] + $stats['suspended_services'];

        return [
            Stat::make('Active Clients', number_format($stats['active_clients']))
                ->description(number_format($stats['total_clients']) . ' total organizations | ' . number_format($stats['active_dsos']) . ' active DSOs')
                ->color('primary'),
            Stat::make('Active Clinics', number_format($stats['total_clinics']))
                ->description(number_format($stats['pms_clinics']) . ' clinic ops | ' . number_format($stats['verification_clinics']) . ' verification | ' . number_format($stats['dual_service_clinics']) . ' both')
                ->color('success'),
            Stat::make('Estimated MRR', '$' . number_format($stats['estimated_mrr'], 2))
                ->description(number_format($stats['active_subscriptions']) . ' active/trial subscriptions | ' . ($collectionDelta >= 0 ? '+' : '') . '$' . number_format($collectionDelta, 2) . ' vs last month')
                ->color($collectionDelta >= 0 ? 'success' : 'warning'),
            Stat::make('Service Attention', number_format($serviceRisk))
                ->description('Onboarding ' . $stats['pending_onboarding'] . ' | Setup ' . $stats['pending_setup'] . ' | Suspended ' . $stats['suspended_services'] . ' | Trials ending ' . $stats['trials_ending_soon'])
                ->color($serviceRisk > 0 || $stats['trials_ending_soon'] > 0 ? 'warning' : 'success'),
            Stat::make('Top Plan Adoption', '')
                ->description(new HtmlString($this->planMixHtml($stats['plan_mix']))),
            Stat::make('YTD Collections', '$' . number_format($stats['payments_ytd'], 2))
                ->description(number_format($stats['new_clients_this_month']) . ' new clients this month | ' . ($clientDelta >= 0 ? '+' : '') . number_format($clientDelta) . ' vs last month')
                ->color('success'),
        ];
    }

    protected function planMixHtml(array $planMix): string
    {
        if ($planMix === []) {
            return '<div style="margin-top:8px; font-size:12px; color:#64748b;">No active subscriptions yet.</div>';
        }

        $rows = collect($planMix)
            ->map(fn (int $count, string $name): string => '<span style="white-space:nowrap;">' . e($name) . ' <strong style="color:#0f172a;">' . number_format($count) . '</strong></span>')
            ->implode(' <span style="color:#cbd5e1;">|</span> ');

        return '<div style="margin-top:12px; font-size:12px; line-height:1.8; color:#b45309; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' . $rows . '</div>';
    }
}
