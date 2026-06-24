@if (! empty($questions))
    <div class="uel2-managed-questions">
        @foreach ($questions as $question)
            <div class="uel2-managed-question" wire:key="template-two-question-{{ $question['id'] }}">
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
                                style="{{ $templateTwoInput }}"
                            ></textarea>
                            @break

                        @case('yes_no')
                            <select wire:model.blur="data.{{ $question['field'] }}" style="{{ $templateTwoInput }}">
                                <option value="">Select</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                            @break

                        @case('select')
                            <select wire:model.blur="data.{{ $question['field'] }}" style="{{ $templateTwoInput }}">
                                <option value="">Select</option>
                                @foreach ($question['options'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                            @break

                        @case('currency')
                            <input
                                type="number"
                                step="0.01"
                                wire:model.blur="data.{{ $question['field'] }}"
                                placeholder="{{ $question['placeholder'] ?: '0.00' }}"
                                style="{{ $templateTwoInput }}"
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
                                style="{{ $templateTwoInput }}"
                            >
                            @break

                        @default
                            <input
                                type="{{ in_array($question['type'], ['date', 'month', 'time', 'email', 'tel', 'number'], true) ? $question['type'] : 'text' }}"
                                wire:model.blur="data.{{ $question['field'] }}"
                                placeholder="{{ $question['placeholder'] ?: 'Enter response' }}"
                                style="{{ $templateTwoInput }}"
                            >
                    @endswitch
                </div>

                @if ($question['has_note'])
                    <div class="uel2-field">
                        <label>{{ $question['note_label'] }}</label>
                        <textarea
                            wire:model.blur="data.{{ $question['note_field'] }}"
                            placeholder="{{ $question['note_placeholder'] }}"
                            style="{{ $templateTwoInput }}"
                        ></textarea>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
