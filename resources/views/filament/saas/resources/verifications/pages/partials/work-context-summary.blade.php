@props(['record', 'quickReference' => [], 'copyText' => null])

<x-filament::section class="verification-work-context">
    <x-slot name="heading">Work Context</x-slot>
    <x-slot name="description">Patient, insurance, assignment, and workflow cues for this verification.</x-slot>

    <x-pds.stack gap="sm">
        <x-pds.grid columns="2" gap="sm">
            <x-pds.readonly-display label="Patient" :value="$quickReference['patient'] ?? ($record->verificationProfile?->patient_full_name ?: $record->patient?->full_name ?: '-')" />
            <x-pds.readonly-display label="Member ID" :value="$quickReference['member_id'] ?? $record->verificationProfile?->patient_identifier ?? '-'" />
            <x-pds.readonly-display label="Insurance" :value="$quickReference['insurance_name'] ?? $record->insurancePolicy?->insurance_company ?? '-'" />
            <x-pds.readonly-display label="Status" :value="\App\Models\BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? str($record->normalized_status)->headline()->toString()" />
            <x-pds.readonly-display label="Assigned User" :value="$record->assignedTo?->name ?? 'Unassigned'" />
            <x-pds.readonly-display label="Due Date" :value="$record->due_at?->format('M d, Y h:i A') ?? '-'" />
        </x-pds.grid>

        <x-pds.action-toolbar>
            <x-pds.status-pill :status="match ($record->normalized_status) {
                \App\Models\BillingWorkItem::STATUS_DONE => 'success',
                \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK => 'danger',
                \App\Models\BillingWorkItem::STATUS_REVIEW,
                \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => 'warning',
                \App\Models\BillingWorkItem::STATUS_IN_PROGRESS => 'info',
                default => 'pending',
            }">
                {{ \App\Models\BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? str($record->normalized_status)->headline()->toString() }}
            </x-pds.status-pill>

            <x-pds.priority-indicator :priority="$record->priority === 'urgent' ? 'urgent' : 'normal'">
                {{ \App\Models\BillingWorkItem::PRIORITY_OPTIONS[$record->priority] ?? str($record->priority)->headline()->toString() }}
            </x-pds.priority-indicator>

            @if (filled($copyText))
                <x-pds.button
                    type="button"
                    variant="toolbar"
                    size="sm"
                    onclick="copyVerificationQuickReference(@js($copyText), this)"
                >
                    Copy Context
                </x-pds.button>
            @endif
        </x-pds.action-toolbar>
    </x-pds.stack>
</x-filament::section>
