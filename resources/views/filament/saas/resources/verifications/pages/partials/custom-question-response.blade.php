@php
    $field = $row['field'];
    $type = $row['type'];
    $options = $row['options'] ?? [];
@endphp

@if ($type === 'textarea')
    <textarea wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $textareaStyle }}"></textarea>
@elseif ($type === 'date')
    <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
@elseif ($type === 'yes_no')
    <select wire:model.blur="data.{{ $field }}" style="{{ $selectStyle }}">
        <option value="">Select</option>
        <option value="Yes">Yes</option>
        <option value="No">No</option>
    </select>
@elseif ($type === 'select')
    <select wire:model.blur="data.{{ $field }}" style="{{ $selectStyle }}">
        <option value="">Select</option>
        @foreach ($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </select>
@elseif ($type === 'multi_select')
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;">
        @foreach ($options as $option)
            <label style="display:flex;align-items:center;gap:8px;min-height:38px;padding:8px 10px;border:1px solid #d6dde8;border-radius:10px;background:#ffffff;color:#0f172a;font-size:13px;font-weight:600;">
                <input
                    type="checkbox"
                    wire:model.live="data.{{ $field }}"
                    value="{{ $option }}"
                    style="width:16px;height:16px;accent-color:#0f766e;"
                >
                <span>{{ $option }}</span>
            </label>
        @endforeach
    </div>
@elseif ($type === 'currency')
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 42px; border: 1px solid #d6dde8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700;">$</span>
        <input type="number" step="0.01" wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
    </div>
@else
    <input type="{{ in_array($type, ['number', 'percent'], true) ? 'number' : 'text' }}" @if ($type === 'percent') step="0.01" @endif wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
@endif
