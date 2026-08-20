@props([
    'lifecycle' => [],
])

@php
    $items = collect($lifecycle['items'] ?? [])->values();
    $activeLabel = $lifecycle['active_label'] ?? 'Request';
@endphp

<section style="border: 1px solid #d7e5df; border-radius: 22px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05); overflow: hidden;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 15px 18px; border-bottom: 1px solid #edf2f7; background: #f8fcfa;">
        <div>
            <div style="font-size: 11px; font-weight: 900; letter-spacing: 0.16em; text-transform: uppercase; color: #0f766e;">Verification Lifecycle</div>
            <div style="margin-top: 4px; font-size: 13px; font-weight: 700; color: #475569;">Current stage: {{ $activeLabel }}</div>
        </div>
        <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #bde8dc; background: #ecfdf5; color: #0f766e; font-size: 12px; font-weight: 900;">
            {{ $activeLabel }}
        </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat({{ max($items->count(), 1) }}, minmax(92px, 1fr)); gap: 0; overflow-x: auto;">
        @forelse ($items as $item)
            @php
                $isActive = (bool) ($item['active'] ?? false);
                $isCompleted = (bool) ($item['completed'] ?? false);
                $borderColor = $isActive ? '#0f766e' : ($isCompleted ? '#a7f3d0' : '#e5e7eb');
                $background = $isActive ? '#f0fdfa' : ($isCompleted ? '#f7fefb' : '#ffffff');
                $dotBackground = $isActive ? '#0f766e' : ($isCompleted ? '#10b981' : '#cbd5e1');
                $textColor = $isActive ? '#0f766e' : ($isCompleted ? '#047857' : '#64748b');
            @endphp
            <div style="min-width: 104px; padding: 13px 12px; border-right: 1px solid #edf2f7; border-top: 3px solid {{ $borderColor }}; background: {{ $background }};">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="width: 9px; height: 9px; border-radius: 999px; background: {{ $dotBackground }}; flex: 0 0 auto;"></span>
                    <span style="font-size: 12px; font-weight: 900; color: {{ $textColor }}; white-space: nowrap;">{{ $item['label'] ?? 'Step' }}</span>
                </div>
            </div>
        @empty
            <div style="padding: 14px 16px; font-size: 13px; color: #64748b;">Workflow stage will appear once the request is loaded.</div>
        @endforelse
    </div>
</section>
