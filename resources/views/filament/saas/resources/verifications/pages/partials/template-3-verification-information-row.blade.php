@php
    $isWide = $question['type'] === 'textarea' || $question['has_note'];
@endphp

<div
    class="uel2-field {{ $isWide ? 'uel2-wide' : '' }}"
    wire:key="template-three-verification-question-{{ $question['id'] }}"
>
    <label>{{ $question['label'] }}</label>

    @if (filled($question['help_text']))
        <div class="uel2-question-help">{{ $question['help_text'] }}</div>
    @endif

    @if ($question['readonly'])
        <div style="{{ $templateThreeReadonly }}">{{ $question['value'] }}</div>
    @else
        @switch($question['type'])
            @case('textarea')
                <textarea
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: 'Enter details' }}"
                    style="{{ $templateThreeInput }}"
                ></textarea>
                @break

            @case('yes_no')
                <select
                    @if ($question['reactive'] ?? false)
                        wire:model.live="data.{{ $question['field'] }}"
                    @else
                        wire:model.blur="data.{{ $question['field'] }}"
                    @endif
                    style="{{ $templateThreeInput }}"
                >
                    <option value="">Select</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                @break

            @case('select')
                <select
                    @if ($question['reactive'] ?? false)
                        wire:model.live="data.{{ $question['field'] }}"
                    @else
                        wire:model.blur="data.{{ $question['field'] }}"
                    @endif
                    style="{{ $templateThreeInput }}"
                >
                    <option value="">Select</option>
                    @foreach ($question['options'] as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                @break

            @case('multi_select')
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;">
                    @foreach ($question['options'] as $option)
                        <label style="display:flex;align-items:center;gap:8px;min-height:38px;padding:8px 10px;border:1px solid #dce8e3;border-radius:10px;background:#fff;color:#142e25;font-size:13px;font-weight:700;">
                            <input type="checkbox" wire:model.live="data.{{ $question['field'] }}" value="{{ $option }}" style="width:16px;height:16px;accent-color:#0b6b4f;">
                            <span>{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case('currency')
            @case('percent')
                <input
                    type="number"
                    step="0.01"
                    @if ($question['type'] === 'percent') min="0" max="100" @endif
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: ($question['type'] === 'percent' ? '0' : '0.00') }}"
                    style="{{ $templateThreeInput }}"
                >
                @break

            @case('month')
            @case('month_year')
                <input
                    type="month"
                    wire:model.blur="data.{{ $question['field'] }}"
                    style="{{ $templateThreeInput }}"
                >
                @break

            @case('month_only')
                <select wire:model.blur="data.{{ $question['field'] }}" style="{{ $templateThreeInput }}">
                    <option value="">Select month</option>
                    @foreach (['Jan', 'Feb', 'March', 'April', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'] as $month)
                        <option value="{{ $month }}">{{ $month }}</option>
                    @endforeach
                </select>
                @break

            @case('year_only')
                <input
                    type="number"
                    min="1900"
                    max="2100"
                    step="1"
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: 'YYYY' }}"
                    style="{{ $templateThreeInput }}"
                >
                @break

            @default
                <input
                    type="{{ in_array($question['type'], ['date', 'time', 'email', 'tel', 'number'], true) ? $question['type'] : 'text' }}"
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: 'Enter response' }}"
                    style="{{ $templateThreeInput }}"
                >
        @endswitch
    @endif

    @if ($question['has_note'])
        <div class="uel2-field" style="margin-top:12px;">
            <label>{{ $question['note_label'] }}</label>
            <textarea
                wire:model.blur="data.{{ $question['note_field'] }}"
                placeholder="{{ $question['note_placeholder'] }}"
                style="{{ $templateThreeInput }}"
            ></textarea>
        </div>
    @endif
</div>
