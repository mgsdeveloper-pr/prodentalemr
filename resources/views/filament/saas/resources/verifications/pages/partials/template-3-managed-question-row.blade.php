@php
    $isChildQuestion = (bool) ($question['is_child'] ?? false);
@endphp

<div
    class="uel2-managed-question {{ $question['has_note'] ? 'uel2-managed-question--with-note' : '' }} {{ $isChildQuestion ? 'uel2-managed-question--child' : '' }}"
    wire:key="template-three-question-{{ $question['id'] }}"
    data-required="{{ ! empty($question['required']) ? 'true' : 'false' }}"
>
    <div class="uel2-question-copy">
        <div class="uel2-question-label">
            {{ $question['label'] }}@if (! empty($question['required']))<span aria-hidden="true"> *</span>@endif
        </div>

        @if (filled($question['help_text']))
            <div class="uel2-question-help">{{ $question['help_text'] }}</div>
        @endif
    </div>

    <div class="uel2-question-response">
        @switch($question['type'])
            @case('textarea')
                <textarea
                    class="uel2-response-textarea"
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: 'Enter details' }}"
                    aria-label="{{ $question['label'] }}"
                    style="{{ $templateThreeInput }}"
                ></textarea>
                @break

            @case('yes_no')
                <div class="uel2-segmented" role="radiogroup" aria-label="{{ $question['label'] }}">
                    @foreach (['Yes', 'No'] as $answerOption)
                        <label>
                            <input
                                type="radio"
                                name="template-three-question-{{ $question['id'] }}"
                                wire:model.live="data.{{ $question['field'] }}"
                                value="{{ $answerOption }}"
                            >
                            <span>{{ $answerOption }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case('select')
                <select wire:model.blur="data.{{ $question['field'] }}" aria-label="{{ $question['label'] }}" style="{{ $templateThreeInput }}">
                    <option value="">Select</option>
                    @foreach ($question['options'] as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                @break

            @case('multi_select')
                <div class="uel2-choice-grid">
                    @foreach ($question['options'] as $option)
                        <label class="uel2-choice-option">
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
                <div class="uel2-input-addon uel2-input-addon--prefix">
                    <span>$</span>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        wire:model.blur="data.{{ $question['field'] }}"
                        placeholder="{{ $question['placeholder'] ?: '0.00' }}"
                        aria-label="{{ $question['label'] }}"
                    >
                </div>
                @break

            @case('percent')
                <div class="uel2-input-addon uel2-input-addon--suffix">
                    <input
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        wire:model.blur="data.{{ $question['field'] }}"
                        placeholder="{{ $question['placeholder'] ?: '0' }}"
                        aria-label="{{ $question['label'] }}"
                    >
                    <span>%</span>
                </div>
                @break

            @case('month')
            @case('month_year')
                <input
                    type="month"
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: 'MM/YYYY' }}"
                    aria-label="{{ $question['label'] }}"
                    style="{{ $templateThreeInput }}"
                >
                @break

            @case('month_only')
                <select wire:model.blur="data.{{ $question['field'] }}" aria-label="{{ $question['label'] }}" style="{{ $templateThreeInput }}">
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
                    aria-label="{{ $question['label'] }}"
                    style="{{ $templateThreeInput }}"
                >
                @break

            @default
                <input
                    type="{{ in_array($question['type'], ['date', 'time', 'email', 'tel', 'number'], true) ? $question['type'] : 'text' }}"
                    wire:model.blur="data.{{ $question['field'] }}"
                    placeholder="{{ $question['placeholder'] ?: 'Enter response' }}"
                    aria-label="{{ $question['label'] }}"
                    style="{{ $templateThreeInput }}"
                >
        @endswitch
    </div>

    @if ($question['has_note'])
        <div class="uel2-field uel2-question-note">
            <label for="template-three-question-note-{{ $question['id'] }}">{{ $question['note_label'] }}</label>
            <textarea
                id="template-three-question-note-{{ $question['id'] }}"
                wire:model.blur="data.{{ $question['note_field'] }}"
                placeholder="{{ $question['note_placeholder'] }}"
                style="{{ $templateThreeInput }}"
            ></textarea>
        </div>
    @endif
</div>
