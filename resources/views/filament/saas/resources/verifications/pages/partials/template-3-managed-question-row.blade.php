@php
    $isChildQuestion = (bool) ($question['is_child'] ?? false);
@endphp

<div
    class="uel2-managed-question"
    wire:key="template-three-question-{{ $question['id'] }}"
    @if ($isChildQuestion)
        style="grid-column:1 / -1;margin-left:18px;border-left:3px solid #60a5fa;background:#f8fbff;"
    @endif
>
    <div class="uel2-field">
        <label>{{ $question['label'] }}</label>

        @if (filled($question['help_text']))
            <div class="uel2-question-help">{{ $question['help_text'] }}</div>
        @endif

        @switch($question['type'])
            @case('textarea')
                <textarea
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: 'Enter details' }}"
                    style="{{ $templateThreeInput }}"
                ></textarea>
                @break

            @case('yes_no')
                <select wire:model.blur="data.{{ $question['field'] }}" style="{{ $templateThreeInput }}">
                    <option value="">Select</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                @break

            @case('select')
                <select wire:model.blur="data.{{ $question['field'] }}" style="{{ $templateThreeInput }}">
                    <option value="">Select</option>
                    @foreach ($question['options'] as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                @break

            @case('multi_select')
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;">
                    @foreach ($question['options'] as $option)
                        <label style="display:flex;align-items:center;gap:8px;min-height:38px;padding:8px 10px;border:1px solid #dce8e3;border-radius:10px;background:#ffffff;color:#142e25;font-size:13px;font-weight:700;">
                            <input
                                type="checkbox"
                                wire:model.live="data.{{ $question['field'] }}"
                                value="{{ $option }}"
                                style="width:16px;height:16px;accent-color:#0b6b4f;"
                            >
                            <span>{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case('currency')
                <input
                    type="number"
                    step="0.01"
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: '0.00' }}"
                    style="{{ $templateThreeInput }}"
                >
                @break

            @case('percent')
                <input
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: '0' }}"
                    style="{{ $templateThreeInput }}"
                >
                @break

            @case('month')
            @case('month_year')
                <input
                    type="month"
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: 'MM/YYYY' }}"
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
    </div>

    @if ($question['has_note'])
        <div class="uel2-field">
            <label>{{ $question['note_label'] }}</label>
            <textarea
                wire:model.blur="data.{{ $question['note_field'] }}"
                placeholder="{{ $question['note_placeholder'] }}"
                style="{{ $templateThreeInput }}"
            ></textarea>
        </div>
    @endif
</div>
