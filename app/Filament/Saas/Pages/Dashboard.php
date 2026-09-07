<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Filament\Saas\Resources\Subscriptions\SubscriptionResource;
use App\Models\Invoice;
use App\Models\OnboardingDraft;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.saas.pages.dashboard';

    public string $period = 'month';

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getWidgets(): array
    {
        return [];
    }

    public function updatedPeriod(string $period): void
    {
        if (! in_array($period, ['month', 'quarter', 'year'], true)) {
            $this->period = 'month';
        }
    }

    public function dashboardData(): array
    {
        [$periodStart, $periodEnd, $previousStart, $previousEnd] = $this->periodRange();

        $activeSubscriptions = Subscription::query()
            ->where('subscriptions.status', 'active')
            ->where('subscriptions.service_status', 'active');

        $mrr = (float) (clone $activeSubscriptions)
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.subscription_plan_id')
            ->sum('subscription_plans.price');

        $openInvoices = Invoice::query()
            ->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])
            ->where('balance_due', '>', 0);

        $overdueInvoices = Invoice::query()
            ->where('balance_due', '>', 0)
            ->where(fn ($query) => $query
                ->where('status', 'overdue')
                ->orWhereDate('due_date', '<', now()->toDateString()));

        $newClients = Organization::query()->whereBetween('created_at', [$periodStart, $periodEnd])->count();
        $previousNewClients = Organization::query()->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        $attention = $this->attentionItems();

        return [
            'period_label' => $this->periodLabel($periodStart, $periodEnd),
            'refreshed_at' => now()->format('M d, Y g:i A'),
            'kpis' => [
                [
                    'label' => 'Monthly recurring revenue',
                    'value' => '$'.number_format($mrr, 2),
                    'detail' => number_format((clone $activeSubscriptions)->count()).' active paid subscriptions',
                    'tone' => 'primary',
                    'url' => SubscriptionResource::getUrl('index'),
                ],
                [
                    'label' => 'Active clients',
                    'value' => number_format(Organization::query()->where('status', true)->count()),
                    'detail' => $this->comparisonText($newClients, $previousNewClients, 'new this period'),
                    'tone' => 'neutral',
                    'url' => ClientManagement::getUrl(),
                ],
                [
                    'label' => 'Active subscriptions',
                    'value' => number_format((clone $activeSubscriptions)->count()),
                    'detail' => number_format(Subscription::query()->where(fn ($query) => $query->where('status', 'trial')->orWhere('service_status', 'trial'))->count()).' currently in trial',
                    'tone' => 'neutral',
                    'url' => SubscriptionResource::getUrl('index'),
                ],
                [
                    'label' => 'Open receivables',
                    'value' => '$'.number_format((float) (clone $openInvoices)->sum('balance_due'), 2),
                    'detail' => number_format((clone $openInvoices)->count()).' invoices awaiting collection',
                    'tone' => (clone $openInvoices)->exists() ? 'warning' : 'neutral',
                    'url' => InvoiceResource::getUrl('index'),
                ],
                [
                    'label' => 'Overdue amount',
                    'value' => '$'.number_format((float) (clone $overdueInvoices)->sum('balance_due'), 2),
                    'detail' => number_format((clone $overdueInvoices)->count()).' invoices require follow-up',
                    'tone' => (clone $overdueInvoices)->exists() ? 'danger' : 'neutral',
                    'url' => InvoiceResource::getUrl('index'),
                ],
            ],
            'attention' => $attention,
            'revenue' => $this->revenueTrend(),
            'accounts' => $this->accountsRequiringFollowUp(),
            'clients_url' => ClientManagement::getUrl(),
            'onboarding_url' => TenantOnboarding::getUrl(),
        ];
    }

    protected function periodRange(): array
    {
        $end = now()->endOfDay();
        $start = match ($this->period) {
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
        $previousEnd = $start->copy()->subSecond();
        $previousStart = match ($this->period) {
            'quarter' => $start->copy()->subQuarter()->startOfQuarter(),
            'year' => $start->copy()->subYear()->startOfYear(),
            default => $start->copy()->subMonthNoOverflow()->startOfMonth(),
        };

        return [$start, $end, $previousStart, $previousEnd];
    }

    protected function periodLabel(Carbon $start, Carbon $end): string
    {
        return $start->isSameDay($end)
            ? $start->format('M d, Y')
            : $start->format('M d, Y').' - '.$end->format('M d, Y');
    }

    protected function comparisonText(int $current, int $previous, string $label): string
    {
        $delta = $current - $previous;

        return number_format($current).' '.$label.' | '.($delta >= 0 ? '+' : '').number_format($delta).' vs previous period';
    }

    protected function attentionItems(): array
    {
        $items = [
            [
                'label' => 'Onboarding incomplete',
                'count' => Organization::query()->where(fn ($query) => $query
                    ->where('lifecycle_status', 'onboarding')
                    ->orWhere(fn ($onboarding) => $onboarding
                        ->whereNotNull('onboarding_status')
                        ->where('onboarding_status', '!=', 'complete')))->count(),
                'description' => 'Client setup has not reached completion.',
                'tone' => 'warning',
                'url' => ClientManagement::getUrl(),
            ],
            [
                'label' => 'Draft onboarding',
                'count' => OnboardingDraft::query()->whereIn('status', [OnboardingDraft::STATUS_DRAFT, OnboardingDraft::STATUS_CHANGES_REQUESTED])->count(),
                'description' => 'Onboarding drafts are waiting to be resumed.',
                'tone' => 'warning',
                'url' => TenantOnboarding::getUrl(),
            ],
            [
                'label' => 'Service setup pending',
                'count' => Subscription::query()->where('service_status', 'pending_setup')->count(),
                'description' => 'Subscriptions require service configuration.',
                'tone' => 'warning',
                'url' => SubscriptionResource::getUrl('index'),
            ],
            [
                'label' => 'Suspended service',
                'count' => Subscription::query()->where('service_status', 'suspended')->count(),
                'description' => 'Client service is currently unavailable.',
                'tone' => 'danger',
                'url' => SubscriptionResource::getUrl('index'),
            ],
            [
                'label' => 'Trials ending soon',
                'count' => Subscription::query()
                    ->where(fn ($query) => $query->where('status', 'trial')->orWhere('service_status', 'trial'))
                    ->whereBetween('trial_ends_at', [now()->toDateString(), now()->addDays(14)->toDateString()])
                    ->count(),
                'description' => 'Trials end within the next 14 days.',
                'tone' => 'info',
                'url' => SubscriptionResource::getUrl('index'),
            ],
            [
                'label' => 'Overdue invoices',
                'count' => Invoice::query()
                    ->where('balance_due', '>', 0)
                    ->where(fn ($query) => $query->where('status', 'overdue')->orWhereDate('due_date', '<', now()->toDateString()))
                    ->count(),
                'description' => 'Payment follow-up is required.',
                'tone' => 'danger',
                'url' => InvoiceResource::getUrl('index'),
            ],
        ];

        return collect($items)->filter(fn (array $item): bool => $item['count'] > 0)->values()->all();
    }

    protected function revenueTrend(): array
    {
        return collect(range(5, 0))->map(function (int $monthsAgo): array {
            $month = now()->subMonthsNoOverflow($monthsAgo);
            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();

            return [
                'label' => $month->format('M'),
                'invoiced' => (float) Invoice::query()->whereBetween('issue_date', [$start, $end])->sum('total_amount'),
                'collected' => (float) Payment::query()->whereBetween('payment_date', [$start, $end])->sum('amount'),
            ];
        })->all();
    }

    protected function accountsRequiringFollowUp(): array
    {
        return Organization::query()
            ->with([
                'accountManager:id,name',
                'subscriptions' => fn ($query) => $query->with('subscriptionPlan:id,name')->latest('start_date'),
                'invoices' => fn ($query) => $query->where('balance_due', '>', 0)->latest('due_date'),
            ])
            ->where(function ($query): void {
                $query
                    ->where('status', false)
                    ->orWhere('lifecycle_status', 'onboarding')
                    ->orWhere(fn ($onboarding) => $onboarding
                        ->whereNotNull('onboarding_status')
                        ->where('onboarding_status', '!=', 'complete'))
                    ->orWhereHas('subscriptions', fn ($subscriptions) => $subscriptions
                        ->whereIn('service_status', ['pending_setup', 'suspended', 'trial'])
                        ->orWhere('cancel_at_period_end', true))
                    ->orWhereHas('invoices', fn ($invoices) => $invoices
                        ->where('balance_due', '>', 0)
                        ->where(fn ($overdue) => $overdue->where('status', 'overdue')->orWhereDate('due_date', '<', now()->toDateString())));
            })
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(function (Organization $organization): array {
                $subscription = $organization->subscriptions->first();
                $overdueBalance = (float) $organization->invoices
                    ->filter(fn (Invoice $invoice): bool => $invoice->status === 'overdue' || ($invoice->due_date?->isPast() && (float) $invoice->balance_due > 0))
                    ->sum('balance_due');

                [$status, $tone, $nextAction] = match (true) {
                    $overdueBalance > 0 => ['Payment overdue', 'danger', 'Review billing'],
                    $subscription?->service_status === 'suspended' => ['Service suspended', 'danger', 'Review service'],
                    $subscription?->service_status === 'pending_setup' => ['Setup pending', 'warning', 'Complete setup'],
                    $subscription?->cancel_at_period_end === true => ['Cancellation scheduled', 'warning', 'Review retention'],
                    $subscription?->isTrial() === true => ['Trial', 'info', 'Review conversion'],
                    ! $organization->status => ['Inactive', 'neutral', 'Review account'],
                    default => ['Onboarding', 'warning', 'Continue onboarding'],
                };

                return [
                    'client' => $organization->name,
                    'plan' => $subscription?->subscriptionPlan?->name ?: 'Not assigned',
                    'status' => $status,
                    'tone' => $tone,
                    'risk' => $overdueBalance > 0 ? '$'.number_format($overdueBalance, 2).' overdue' : 'No financial risk',
                    'setup' => str($organization->onboarding_status ?: 'Not started')->replace('_', ' ')->headline()->toString(),
                    'owner' => $organization->accountManager?->name ?: 'Unassigned',
                    'next_action' => $nextAction,
                    'url' => OrganizationWorkspace::getUrl(['record' => $organization]),
                ];
            })
            ->all();
    }
}
