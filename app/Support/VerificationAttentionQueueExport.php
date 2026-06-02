<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

class VerificationAttentionQueueExport
{
    public static function query(?string $filter = null): Builder
    {
        $query = AdminClinicScope::apply(
            BillingWorkItem::query()
                ->with(['clinic', 'assignedTo', 'patient', 'verificationProfile'])
                ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
                ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
        );

        match ($filter) {
            'pending_unassigned' => $query->where(function (Builder $innerQuery): void {
                $innerQuery
                    ->whereNull('assigned_to')
                    ->orWhere('status', BillingWorkItem::STATUS_PENDING)
                    ->orWhere('status', 'unassigned');
            }),
            'urgent_requests' => $query->where('priority', 'urgent'),
            'due_today' => $query->whereDate('due_at', today())->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
            'overdue' => $query->where('due_at', '<', now())->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
            'awaiting_clinic_response' => $query->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
            'returned_for_rework' => $query->where('status', BillingWorkItem::STATUS_RETURNED_FOR_REWORK),
            default => null,
        };

        return $query
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 WHEN priority = 'high' THEN 1 WHEN priority = 'normal' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN due_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_at');
    }

    public static function rows(?string $filter = null): array
    {
        return static::query($filter)
            ->get()
            ->map(fn (BillingWorkItem $record): array => [
                'Patient' => $record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?? '-'),
                'Clinic' => $record->clinic?->clinic_name ?: ($record->verificationProfile?->location_name ?: '-'),
                'Priority' => filled($record->priority) ? str($record->priority)->replace('_', ' ')->title()->toString() : '-',
                'SLA' => match ($record->sla_status) {
                    'overdue' => 'Overdue',
                    'due_today' => 'Due Today',
                    'paused_waiting_clinic' => 'Waiting on Clinic',
                    'on_track' => 'On Track',
                    'closed' => 'Closed',
                    default => 'Not Set',
                },
                'Due' => $record->due_at?->format('M d, Y h:i A') ?? '-',
                'Assignee' => $record->assignedTo?->name ?? 'Unassigned',
            ])
            ->all();
    }

    public static function excelHtml(array $rows, array $meta = []): string
    {
        return view('exports.verification-attention-queue.table', [
            'rows' => $rows,
            'meta' => $meta,
        ])->render();
    }

    public static function pdf(array $rows, array $meta = []): string
    {
        return Pdf::loadView('exports.verification-attention-queue.table', [
            'rows' => $rows,
            'meta' => $meta,
        ])->setPaper('a4', 'landscape')->output();
    }

    public static function meta(?string $filter = null): array
    {
        $label = match ($filter) {
            'pending_unassigned' => 'Pending & Unassigned',
            'urgent_requests' => 'Urgent Requests',
            'due_today' => 'Due Today',
            'overdue' => 'Overdue SLA',
            'awaiting_clinic_response' => 'Waiting on Clinic',
            'returned_for_rework' => 'Returned for Rework',
            default => 'All Attention Queue Items',
        };

        return [
            'title' => 'Verification Attention Queue',
            'scope' => $label,
            'generated_at' => now()->format('M d, Y h:i A'),
        ];
    }
}
