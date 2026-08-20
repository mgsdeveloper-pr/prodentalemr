@php
    $activeQueue = request()->query('queue_preset', 'all');
    $queueUrl = fn (?string $preset = null): string => \App\Filament\Saas\Resources\Verifications\VerificationRequestResource::getUrl(
        'index',
        filled($preset) ? ['queue_preset' => $preset] : [],
    );
@endphp

<div class="verification-queue-kpis" role="navigation" aria-label="Verification request queues">
    <a href="{{ $queueUrl() }}" @class(['verification-queue-kpi', 'is-active' => $activeQueue === 'all'])>All requests</a>
    <a href="{{ $queueUrl('unassigned') }}" @class(['verification-queue-kpi', 'is-active' => $activeQueue === 'unassigned'])>Unassigned</a>
    <a href="{{ $queueUrl('in_progress') }}" @class(['verification-queue-kpi', 'is-active' => $activeQueue === 'in_progress'])>In progress</a>
    <a href="{{ $queueUrl('waiting_clinic') }}" @class(['verification-queue-kpi', 'is-active' => $activeQueue === 'waiting_clinic'])>Waiting on clinic</a>
    <a href="{{ $queueUrl('ready_for_audit') }}" @class(['verification-queue-kpi', 'is-active' => $activeQueue === 'ready_for_audit'])>Ready for audit</a>
    <a href="{{ $queueUrl('overdue') }}" @class(['verification-queue-kpi', 'is-active' => $activeQueue === 'overdue'])>Overdue</a>
</div>
