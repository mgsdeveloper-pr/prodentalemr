<?php

namespace App\Filament\Saas\Widgets;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class SaasBusinessOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = Cache::remember('saas_dashboard.business_overview', now()->addSeconds(45), function (): array {
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();
            $startOfYear = now()->startOfYear()->toDateString();
            $endOfYear = now()->endOfYear()->toDateString();

            return [
                'total_clients' => Organization::query()->count(),
                'total_clinics' => Clinic::query()->count(),
                'new_clients_this_month' => Organization::query()
                    ->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])
                    ->count(),
                'payments_this_month' => (float) Payment::query()
                    ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                    ->sum('amount'),
                'payments_ytd' => (float) Payment::query()
                    ->whereBetween('payment_date', [$startOfYear, $endOfYear])
                    ->sum('amount'),
            ];
        });

        return [
            Stat::make('Client & Clinic Count', '')
                ->description(new HtmlString(
                    '<div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-top:10px;">'
                    . '<div style="padding:12px 14px; border:1px solid #e2e8f0; border-radius:14px; background:#f8fbff;">'
                    . '<div style="font-size:11px; letter-spacing:0.12em; text-transform:uppercase; color:#7c8aa5; margin-bottom:6px;">Clients</div>'
                    . '<div style="font-size:28px; line-height:1; font-weight:700; color:#0f172a;">' . number_format($stats['total_clients']) . '</div>'
                    . '<div style="font-size:12px; color:#64748b; margin-top:8px;">Active organizations</div>'
                    . '</div>'
                    . '<div style="padding:12px 14px; border:1px solid #e2e8f0; border-radius:14px; background:#fffaf2;">'
                    . '<div style="font-size:11px; letter-spacing:0.12em; text-transform:uppercase; color:#7c8aa5; margin-bottom:6px;">Clinics</div>'
                    . '<div style="font-size:28px; line-height:1; font-weight:700; color:#0f172a;">' . number_format($stats['total_clinics']) . '</div>'
                    . '<div style="font-size:12px; color:#64748b; margin-top:8px;">Live clinic workspaces</div>'
                    . '</div>'
                    . '</div>'
                )),
            Stat::make('New Clients This Month', number_format($stats['new_clients_this_month']))
                ->description('Organizations added in the current month'),
            Stat::make('Payment Received This Month', '$' . number_format($stats['payments_this_month'], 2))
                ->description('Payments recorded in the current month'),
            Stat::make('Money Received Year on Year', '$' . number_format($stats['payments_ytd'], 2))
                ->description('Payments recorded since the start of the current year'),
        ];
    }
}
