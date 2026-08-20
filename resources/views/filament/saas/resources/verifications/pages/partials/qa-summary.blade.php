@php
    $record = $record ?? $this->getRecord();
    $showQaActions = (bool) ($showQaActions ?? false);
    $canSubmitToQa = (bool) ($canSubmitToQa ?? false);
    $canApproveQa = (bool) ($canApproveQa ?? false);
    $canReturnForRework = (bool) ($canReturnForRework ?? false);

    $qaState = match ($record->normalized_status) {
        \App\Models\BillingWorkItem::STATUS_REVIEW => [
            'label' => 'In Audit Review',
            'description' => 'This request is ready for audit review.',
            'border' => '#bfdbfe',
            'background' => '#eff6ff',
            'color' => '#1d4ed8',
        ],
        \App\Models\BillingWorkItem::STATUS_DONE => [
            'label' => 'Audit Approved',
            'description' => 'This request has completed audit review.',
            'border' => '#bbf7d0',
            'background' => '#ecfdf5',
            'color' => '#15803d',
        ],
        \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK => [
            'label' => 'Returned for Correction',
            'description' => 'This request needs correction before it can be approved.',
            'border' => '#fecdd3',
            'background' => '#fff1f2',
            'color' => '#be123c',
        ],
        default => [
            'label' => 'Not in Audit',
            'description' => 'Send this request to Audit after verification work is ready.',
            'border' => '#dbe4ee',
            'background' => '#f8fafc',
            'color' => '#475569',
        ],
    };

    $qaRows = [
        ['label' => 'Audit Status', 'value' => $qaState['label']],
        ['label' => 'Reviewer', 'value' => $record->reviewedBy?->name ?: '-'],
        ['label' => 'Reviewed At', 'value' => optional($record->completed_at)->format('M d, Y h:i A') ?: '-'],
        ['label' => 'Correction Note', 'value' => $record->return_reason ?: '-'],
    ];
@endphp

<section style="border: 1px solid #dbe4ee; border-radius: 22px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05); overflow: hidden;">
    <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; background: #f8fafc; border-bottom: 1px solid #edf2f7;">
        <div>
            <div style="font-size: 11px; font-weight: 900; letter-spacing: 0.16em; text-transform: uppercase; color: #0f766e;">Audit Review</div>
            <div style="margin-top: 4px; font-size: 13px; line-height: 1.6; color: #64748b;">{{ $qaState['description'] }}</div>
        </div>
        <span style="display: inline-flex; align-items: center; justify-content: center; padding: 7px 12px; border-radius: 999px; border: 1px solid {{ $qaState['border'] }}; background: {{ $qaState['background'] }}; color: {{ $qaState['color'] }}; font-size: 11px; font-weight: 900;">
            {{ $qaState['label'] }}
        </span>
    </div>

    <div style="padding: 16px 18px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
        @foreach ($qaRows as $row)
            <div style="border: 1px solid #e5e7eb; border-radius: 16px; padding: 12px; background: #ffffff;">
                <div style="margin-bottom: 5px; font-size: 10px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">{{ $row['label'] }}</div>
                <div style="font-size: 13px; font-weight: 800; color: #0f172a; line-height: 1.5;">{{ $row['value'] }}</div>
            </div>
        @endforeach
    </div>

    @if ($showQaActions && ($canSubmitToQa || $canApproveQa || $canReturnForRework))
        <div style="padding: 0 18px 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end;">
            @if ($canSubmitToQa)
                <button type="button" wire:click="saveAndTransition('{{ \App\Models\BillingWorkItem::STATUS_REVIEW }}')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 138px; padding: 10px 14px; border-radius: 12px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 900; cursor: pointer;">
                    Send to Audit
                </button>
            @endif
            @if ($canApproveQa)
                <button type="button" wire:click="saveAndTransition('{{ \App\Models\BillingWorkItem::STATUS_DONE }}')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 132px; padding: 10px 14px; border-radius: 12px; border: 1px solid #bbf7d0; background: #ecfdf5; color: #15803d; font-size: 12px; font-weight: 900; cursor: pointer;">
                    Approve Audit
                </button>
            @endif
            @if ($canReturnForRework)
                <button type="button" onclick="openWorkflowModal('rework-reason-modal')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 160px; padding: 10px 14px; border-radius: 12px; border: 1px solid #fecdd3; background: #fff1f2; color: #be123c; font-size: 12px; font-weight: 900; cursor: pointer;">
                    Return for Correction
                </button>
            @endif
        </div>
    @endif
</section>
