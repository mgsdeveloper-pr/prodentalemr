<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use Carbon\CarbonInterface;

class SLAService
{
    public const DEFAULT_PRIORITY_WINDOWS = [
        'low' => ['days' => 5],
        'normal' => ['days' => 3],
        'high' => ['hours' => 48],
        'urgent' => ['hours' => 24],
    ];

    public function resolveDueAt(array $requestData): mixed
    {
        $enrollment = filled($requestData['client_service_enrollment_id'] ?? null)
            ? ClientServiceEnrollment::query()->find($requestData['client_service_enrollment_id'])
            : null;

        if ($enrollment) {
            return $enrollment->calculateDueAt((string) ($requestData['priority'] ?? 'normal'));
        }

        return $this->calculateDefaultDueAt((string) ($requestData['priority'] ?? 'normal'));
    }

    public function status(BillingWorkItem $request): string
    {
        if (! $request->due_at) {
            return 'not_set';
        }

        if ($request->normalized_status === BillingWorkItem::STATUS_DONE) {
            return 'closed';
        }

        if ($request->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            return 'paused_waiting_clinic';
        }

        if ($request->normalized_status === BillingWorkItem::STATUS_INCOMPLETE) {
            return 'incomplete';
        }

        if ($request->due_at->isPast()) {
            return 'overdue';
        }

        if ($request->due_at->isToday()) {
            return 'due_today';
        }

        return 'on_track';
    }

    public function snapshot(BillingWorkItem $request): array
    {
        $status = $this->status($request);

        return [
            'status' => $status,
            'label' => $this->label($status),
            'due_at' => optional($request->due_at)->format('M d, Y h:i A') ?: '-',
            'relative' => $this->relativeDueLabel($request),
            'is_paused' => $status === 'paused_waiting_clinic',
            'pause_reason' => $status === 'paused_waiting_clinic' ? 'Waiting on clinic response' : '-',
            'paused_for' => $this->pausedForLabel($request),
            'priority' => BillingWorkItem::PRIORITY_OPTIONS[$request->priority] ?? str((string) $request->priority)->headline()->toString(),
        ];
    }

    public function label(string $status): string
    {
        return match ($status) {
            'overdue' => 'Overdue',
            'due_today' => 'Due Today',
            'paused_waiting_clinic' => 'Waiting on Clinic',
            'incomplete' => 'Incomplete',
            'on_track' => 'On Track',
            'closed' => 'Closed',
            default => 'Not Set',
        };
    }

    public function calculateDefaultDueAt(string $priority): CarbonInterface
    {
        $window = self::DEFAULT_PRIORITY_WINDOWS[$priority] ?? self::DEFAULT_PRIORITY_WINDOWS['normal'];
        $dueAt = now();

        if (isset($window['hours'])) {
            return $dueAt->addHours((int) $window['hours']);
        }

        return $dueAt->addDays((int) ($window['days'] ?? 3));
    }

    protected function relativeDueLabel(BillingWorkItem $request): string
    {
        if (! $request->due_at) {
            return 'No due date set';
        }

        if ($request->normalized_status === BillingWorkItem::STATUS_DONE) {
            return 'Completed';
        }

        if ($request->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            return 'Paused while waiting on clinic';
        }

        if ($request->normalized_status === BillingWorkItem::STATUS_INCOMPLETE) {
            return 'Required answers missing';
        }

        return $request->due_at->isPast()
            ? 'Overdue by ' . $request->due_at->diffForHumans(now(), true)
            : 'Due in ' . now()->diffForHumans($request->due_at, true);
    }

    protected function pausedForLabel(BillingWorkItem $request): string
    {
        $seconds = (int) ($request->sla_paused_seconds ?? 0);

        if (filled($request->sla_pause_started_at)) {
            $seconds += max(0, $request->sla_pause_started_at->diffInSeconds(now()));
        }

        if ($seconds <= 0) {
            return '-';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
        }

        return max(1, $minutes) . 'm';
    }
}
