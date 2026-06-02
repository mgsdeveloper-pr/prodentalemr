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

    protected function getActiveFilter(): ?string
    {
        $filter = request()->query('attention_filter');

        return filled($filter) ? (string) $filter : null;
    }

    protected function getViewData(): array
    {
        $verificationQuery = AdminClinicScope::apply(
            BillingWorkItem::query()
                ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))
        );

        $activeFilter = $this->getActiveFilter();
        $dashboardUrl = url('/verification');

        return [
            'activeFilter' => $activeFilter,
            'links' => [
                [
                    'filter' => 'pending_unassigned',
                    'title' => 'Pending & Unassigned',
                    'description' => 'Start with new requests that still need ownership.',
                    'metric' => (clone $verificationQuery)
                        ->where(function (Builder $query): void {
                            $query
                                ->whereNull('assigned_to')
                                ->orWhere('status', BillingWorkItem::STATUS_PENDING)
                                ->orWhere('status', 'unassigned');
                        })
                        ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
                        ->count(),
                    'url' => "{$dashboardUrl}?attention_filter=pending_unassigned#verification-attention-queue",
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
                    'filter' => 'due_today',
                    'title' => 'Due Today',
                    'description' => 'Review requests reaching their SLA today.',
                    'metric' => (clone $verificationQuery)
                        ->whereDate('due_at', today())
                        ->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
                        ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
                        ->count(),
                    'url' => "{$dashboardUrl}?attention_filter=due_today#verification-attention-queue",
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
                    'filter' => 'awaiting_clinic_response',
                    'title' => 'Waiting on Clinic',
                    'description' => 'Monitor requests paused until the clinic sends missing details.',
                    'metric' => (clone $verificationQuery)
                        ->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
                        ->count(),
                    'url' => "{$dashboardUrl}?attention_filter=awaiting_clinic_response#verification-attention-queue",
                ],
                [
                    'filter' => 'returned_for_rework',
                    'title' => 'Returned for Rework',
                    'description' => 'Watch requests sent back for correction before closure.',
                    'metric' => (clone $verificationQuery)
                        ->where('status', BillingWorkItem::STATUS_RETURNED_FOR_REWORK)
                        ->count(),
                    'url' => "{$dashboardUrl}?attention_filter=returned_for_rework#verification-attention-queue",
                ],
                [
                    'filter' => null,
                    'title' => 'Verification Questions',
                    'description' => 'Maintain clinic-specific verification sections and prompts.',
                    'metric' => null,
                    'url' => url('/verification/verification-form-questions'),
                ],
            ],
        ];
    }
}
