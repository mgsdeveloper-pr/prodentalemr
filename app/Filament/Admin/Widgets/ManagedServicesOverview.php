<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ManagedServicesOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $clinicId = AdminClinicScope::selectedClinicId() ?: 'all';

        $stats = Cache::remember(
            "admin_dashboard.managed_services_overview.{$clinicId}",
            now()->addSeconds(45),
            function (): array {
                $row = $this->verificationItems()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->selectRaw("
                        SUM(CASE WHEN assigned_to IS NULL OR status = 'unassigned' THEN 1 ELSE 0 END) as unassigned_count,
                        SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
                        SUM(CASE WHEN DATE(due_at) = CURDATE() THEN 1 ELSE 0 END) as due_today_count,
                        SUM(CASE WHEN due_at < NOW() THEN 1 ELSE 0 END) as overdue_count
                    ")
                    ->first();

                return [
                    'unassigned' => (int) ($row?->unassigned_count ?? 0),
                    'urgent' => (int) ($row?->urgent_count ?? 0),
                    'due_today' => (int) ($row?->due_today_count ?? 0),
                    'overdue' => (int) ($row?->overdue_count ?? 0),
                ];
            },
        );

        return [
            Stat::make('Unassigned Verifications', number_format($stats['unassigned']))
                ->description('Requests still waiting for ownership')
                ->color($stats['unassigned'] > 0 ? 'warning' : 'success'),
            Stat::make('Urgent Requests', number_format($stats['urgent']))
                ->description('Priority items needing immediate attention')
                ->color($stats['urgent'] > 0 ? 'danger' : 'success'),
            Stat::make('Due Today', number_format($stats['due_today']))
                ->description('Verification work due before end of day')
                ->color($stats['due_today'] > 0 ? 'warning' : 'success'),
            Stat::make('Overdue SLA Items', number_format($stats['overdue']))
                ->description('Requests already outside their SLA window')
                ->color($stats['overdue'] > 0 ? 'danger' : 'success'),
        ];
    }

    protected function verificationItems(): Builder
    {
        return AdminClinicScope::apply(
            BillingWorkItem::query()
                ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))
        );
    }
}
