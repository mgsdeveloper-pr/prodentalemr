<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class ManagedServicesQuickLinks extends Widget
{
    protected string $view = 'filament.admin.widgets.managed-services-quick-links';

    protected int|string|array $columnSpan = 'full';

    public ?string $activeFilter = null;

    public function mount(): void
    {
        $filter = request()->query('attention_filter');
        $this->activeFilter = filled($filter) ? (string) $filter : null;
    }

    public function applyFilter(?string $filter = null): void
    {
        $nextFilter = filled($filter) && $this->activeFilter !== $filter
            ? (string) $filter
            : null;

        $this->activeFilter = $nextFilter;

        $this->dispatch('verification-attention-filter-changed', filter: $nextFilter);
    }

    protected function getActiveFilter(): ?string
    {
        return $this->activeFilter;
    }

    protected function getViewData(): array
    {
        $verificationQuery = AdminClinicScope::apply(
            BillingWorkItem::query()
                ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))
        );

        $activeFilter = $this->getActiveFilter();
        $dashboardUrl = url('/verification');
        $waitingOnClinicCount = (clone $verificationQuery)
            ->whereIn('status', [
                BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
                'waiting_on_client',
            ])
            ->count();
        $returnedForReworkCount = (clone $verificationQuery)
            ->whereIn('status', [
                BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                'audit',
            ])
            ->count();

        return [
            'activeFilter' => $activeFilter,
            'links' => [
                [
                    'filter' => 'new_pending',
                    'title' => 'New & Pending',
                    'description' => 'Start with newly opened requests that are still pending work.',
                    'metric' => (clone $verificationQuery)
                        ->whereIn('status', [
                            BillingWorkItem::STATUS_PENDING,
                            'unassigned',
                        ])
                        ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
                        ->count(),
                    'url' => "{$dashboardUrl}?attention_filter=new_pending#verification-attention-queue",
                ],
                [
                    'filter' => 'urgent_requests',
                    'title' => 'Urgent Requests',
                    'description' => 'Jump to high-pressure items first.',
                    'metric' => (clone $verificationQuery)
                        ->where('priority', 'urgent')
                        ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
                        ->count(),
                    'url' => "{$dashboardUrl}?attention_filter=urgent_requests#verification-attention-queue",
                ],
                [
                    'filter' => 'overdue',
                    'title' => 'Overdue SLA',
                    'description' => 'Recover requests already outside expected turnaround.',
                    'metric' => (clone $verificationQuery)
                        ->where('due_at', '<', now())
                        ->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
                        ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
                        ->count(),
                    'url' => "{$dashboardUrl}?attention_filter=overdue#verification-attention-queue",
                ],
                [
                    'filter' => 'returned_for_rework',
                    'title' => 'Returned for Rework',
                    'description' => 'Track requests sent back for correction before closure.',
                    'metric' => $returnedForReworkCount,
                    'url' => "{$dashboardUrl}?attention_filter=returned_for_rework#verification-attention-queue",
                ],
                [
                    'filter' => 'awaiting_clinic_response',
                    'title' => 'Waiting on Clinic',
                    'description' => 'Monitor requests paused until the clinic sends missing details.',
                    'metric' => $waitingOnClinicCount,
                    'url' => "{$dashboardUrl}?attention_filter=awaiting_clinic_response#verification-attention-queue",
                ],
            ],
        ];
    }
}
