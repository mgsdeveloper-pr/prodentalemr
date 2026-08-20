@php
    $record = $record ?? $this->getRecord();
    $sla = $sla ?? app(\App\Services\Verification\SLAService::class)->snapshot($record);

    $tone = match ($sla['status'] ?? null) {
        'overdue' => ['#fecdd3', '#fff1f2', '#be123c'],
        'due_today' => ['#fed7aa', '#fff7ed', '#b45309'],
        'paused_waiting_clinic' => ['#dbe4ee', '#f8fafc', '#475569'],
        'on_track' => ['#bbf7d0', '#ecfdf5', '#15803d'],
        'closed' => ['#cbd5e1', '#f8fafc', '#334155'],
        default => ['#dbe4ee', '#ffffff', '#475569'],
    };

    $rows = [
        ['label' => 'Due At', 'value' => $sla['due_at'] ?? '-'],
        ['label' => 'Remaining', 'value' => $sla['relative'] ?? '-'],
        ['label' => 'Priority', 'value' => $sla['priority'] ?? '-'],
        ['label' => 'Paused For', 'value' => $sla['paused_for'] ?? '-'],
    ];
@endphp

<section style="border: 1px solid #dbe4ee; border-radius: 22px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05); overflow: hidden;">
    <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; background: #f8fafc; border-bottom: 1px solid #edf2f7;">
        <div>
            <div style="font-size: 11px; font-weight: 900; letter-spacing: 0.16em; text-transform: uppercase; color: #0f766e;">SLA</div>
            <div style="margin-top: 4px; font-size: 13px; line-height: 1.6; color: #64748b;">
                Track due timing, paused clinic wait time, and overdue risk for this verification request.
            </div>
        </div>
        <span style="display: inline-flex; align-items: center; justify-content: center; padding: 7px 12px; border-radius: 999px; border: 1px solid {{ $tone[0] }}; background: {{ $tone[1] }}; color: {{ $tone[2] }}; font-size: 11px; font-weight: 900;">
            {{ $sla['label'] ?? 'Not Set' }}
        </span>
    </div>

    <div style="padding: 16px 18px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
        @foreach ($rows as $row)
            <div style="border: 1px solid #e5e7eb; border-radius: 16px; padding: 12px; background: #ffffff;">
                <div style="margin-bottom: 5px; font-size: 10px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">{{ $row['label'] }}</div>
                <div style="font-size: 13px; font-weight: 800; color: #0f172a; line-height: 1.5;">{{ $row['value'] }}</div>
            </div>
        @endforeach
    </div>

    @if (($sla['is_paused'] ?? false) && ($sla['pause_reason'] ?? '-') !== '-')
        <div style="padding: 0 18px 16px;">
            <div style="border: 1px solid #fed7aa; border-radius: 14px; background: #fff7ed; padding: 12px 14px; color: #92400e; font-size: 12px; font-weight: 800;">
                {{ $sla['pause_reason'] }}
            </div>
        </div>
    @endif
</section>
