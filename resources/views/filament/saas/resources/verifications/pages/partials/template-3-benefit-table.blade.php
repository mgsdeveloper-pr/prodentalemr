<table class="uel2-table uel2-benefit-table">
    <thead><tr><th style="width: 140px;">Code</th><th>Description</th><th style="width: 140px;">%</th><th style="width: 220px;">Frequency</th><th style="width: 48%;">Response Details</th></tr></thead>
    <tbody>
        @foreach ($benefitRows as $benefitRow)
            @php
                $rowIndex = $benefitRow['index'];
                $row = $benefitRow['row'];
                $responseMode = data_get($this->codeCoverageData, $rowIndex . '.frequency_response_mode') ?: data_get($row, 'frequency_response_mode', 'current');
                $configuredFields = data_get($this->codeCoverageData, $rowIndex . '.frequency_response_fields');
                $configuredFields = is_array($configuredFields)
                    ? $configuredFields
                    : data_get($row, 'frequency_response_fields');
                $configuredFields = is_array($configuredFields)
                    ? $configuredFields
                    : \App\Models\VerificationFormQuestion::defaultFrequencyResponseFields($responseMode);
                $responseConfiguration = data_get($row, 'response_configuration');
                $responseConfiguration = is_array($responseConfiguration) ? $responseConfiguration : [];
                $primaryFields = data_get($responseConfiguration, 'primary_fields', ['coverage_percent', 'frequency']);
                $fieldOrder = [
                    'coverage_status',
                    'service_history',
                    'pre_auth_required',
                    'pre_auth_details',
                    'downgrade_applies',
                    'downgrade_to',
                    'age_limit',
                    'waiting_period',
                    'payment_guideline',
                    'notes',
                ];
                $detailFields = collect(data_get($responseConfiguration, 'detail_fields', $configuredFields))
                    ->reject(fn (string $field): bool => in_array($field, $primaryFields, true))
                    ->sortBy(fn (string $field): int => array_search($field, $fieldOrder, true) === false ? 999 : array_search($field, $fieldOrder, true))
                    ->values()
                    ->all();
                $preAuthRequiredForRow = data_get($this->codeCoverageData, $rowIndex . '.pre_auth_required') === 'Yes';
                $downgradeAppliesForRow = data_get($this->codeCoverageData, $rowIndex . '.downgrade_applies') === 'Yes';
                $fieldLabels = array_replace($templateThreeFrequencyFieldLabels, data_get($responseConfiguration, 'field_labels', []));
                $fieldPlaceholders = array_replace($templateThreeFrequencyPlaceholders, data_get($responseConfiguration, 'field_placeholders', []));
                $yesNoFields = data_get($responseConfiguration, 'yes_no_fields', []);
                $singleLineFields = data_get($responseConfiguration, 'single_line_fields', []);

                if (in_array('pre_auth_required', $detailFields, true) && ! in_array('pre_auth_details', $detailFields, true)) {
                    $detailFields[] = 'pre_auth_details';
                }

                if (in_array('downgrade_applies', $detailFields, true) && ! in_array('downgrade_to', $detailFields, true)) {
                    $detailFields[] = 'downgrade_to';
                }

                $detailFields = collect($detailFields)
                    ->sortBy(fn (string $field): int => array_search($field, $fieldOrder, true) === false ? 999 : array_search($field, $fieldOrder, true))
                    ->values()
                    ->all();
                $benefitCode = trim((string) data_get($this->codeCoverageData, $rowIndex . '.code'));
                $hasCoverageResponse = in_array('coverage_percent', $primaryFields, true);
                $hasFrequencyResponse = in_array('frequency', $primaryFields, true);
                $isRequiredResponse = (bool) data_get($row, 'required', false);
            @endphp
                <tr
                    class="{{ $hasCoverageResponse ? '' : 'uel2-benefit-row--no-coverage' }} {{ $hasFrequencyResponse ? '' : 'uel2-benefit-row--no-frequency' }}"
                    data-required="{{ $isRequiredResponse ? 'true' : 'false' }}"
                >
                    <td data-label="Code"><span class="uel2-benefit-code">{{ filled($benefitCode) ? $benefitCode : 'Question' }}</span></td>
                    <td data-label="Description">
                        <div class="uel2-benefit-description">{{ data_get($this->codeCoverageData, $rowIndex . '.description') }}</div>
                        <span class="uel2-benefit-kind">{{ filled($benefitCode) ? 'Procedure benefit' : 'Plan question' }}</span>
                    </td>
                    <td data-label="%">
                        @if ($hasCoverageResponse)
                            <div class="uel2-input-addon uel2-input-addon--suffix">
                                <input type="number" min="0" max="100" wire:model.blur="codeCoverageData.{{ $rowIndex }}.coverage_percent" placeholder="Coverage" aria-label="Coverage percentage for {{ data_get($this->codeCoverageData, $rowIndex . '.description') }}">
                                <span>%</span>
                            </div>
                        @else
                            <span class="uel2-benefit-empty" aria-hidden="true"></span>
                        @endif
                    </td>
                    <td data-label="Frequency">
                        @if ($hasFrequencyResponse)
                            <input wire:model.blur="codeCoverageData.{{ $rowIndex }}.frequency" placeholder="Frequency">
                        @else
                            <span class="uel2-benefit-empty" aria-hidden="true"></span>
                        @endif
                    </td>
                    <td data-label="Response Details">
                        @if (empty($detailFields))
                            <span class="uel2-benefit-empty" aria-hidden="true"></span>
                        @else
                            <div
                                class="uel2-benefit-details"
                                x-data="{ preAuth: @js(data_get($this->codeCoverageData, $rowIndex . '.pre_auth_required')), downgrade: @js(data_get($this->codeCoverageData, $rowIndex . '.downgrade_applies')) }"
                            >
                                @foreach ($detailFields as $field)
                                    @php
                                        $label = $fieldLabels[$field] ?? str($field)->headline()->toString();
                                        $placeholder = $fieldPlaceholders[$field] ?? $label;
                                        $selectOptions = in_array($field, $yesNoFields, true)
                                            ? ['' => 'Select an option', 'Yes' => 'Yes', 'No' => 'No']
                                            : (data_get($responseConfiguration, 'field_options.' . $field) ?? $templateThreeFrequencySelectFields[$field] ?? null);
                                        $currentValue = data_get($this->codeCoverageData, $rowIndex . '.' . $field);
                                        if (is_array($selectOptions) && filled($currentValue) && ! array_key_exists($currentValue, $selectOptions)) {
                                            $selectOptions[$currentValue] = 'Previously recorded: ' . $currentValue;
                                        }
                                    @endphp
                                    <div
                                        @if ($field === 'pre_auth_details')
                                            x-show="preAuth === 'Yes'"
                                            x-cloak
                                        @elseif ($field === 'downgrade_to')
                                            x-show="downgrade === 'Yes'"
                                            x-cloak
                                        @endif
                                    >
                                        <label style="display:block;margin:0 0 5px;color:#50655d;font-size:10px;font-weight:900;letter-spacing:.07em;text-transform:uppercase;">{{ $label }}</label>
                                        @if (in_array($field, $yesNoFields, true))
                                            <div class="uel2-segmented" role="radiogroup" aria-label="{{ $label }}">
                                                @foreach (['Yes', 'No'] as $answerOption)
                                                    <label>
                                                        <input
                                                            type="radio"
                                                            name="coverage-{{ $rowIndex }}-{{ $field }}"
                                                            wire:model.live="codeCoverageData.{{ $rowIndex }}.{{ $field }}"
                                                            value="{{ $answerOption }}"
                                                        >
                                                        <span>{{ $answerOption }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @elseif (is_array($selectOptions))
                                            <select
                                                @if ($field === 'pre_auth_required')
                                                    x-model="preAuth"
                                                    wire:model.live="codeCoverageData.{{ $rowIndex }}.{{ $field }}"
                                                @elseif ($field === 'downgrade_applies')
                                                    x-model="downgrade"
                                                    wire:model.live="codeCoverageData.{{ $rowIndex }}.{{ $field }}"
                                                @else
                                                    wire:model.blur="codeCoverageData.{{ $rowIndex }}.{{ $field }}"
                                                @endif
                                            >
                                                @foreach ($selectOptions as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                        @elseif (in_array($field, $templateThreeFrequencyTextareaFields, true) && ! in_array($field, $singleLineFields, true))
                                            <textarea wire:model.blur="codeCoverageData.{{ $rowIndex }}.{{ $field }}" placeholder="{{ $placeholder }}" rows="2"></textarea>
                                        @else
                                            <input wire:model.blur="codeCoverageData.{{ $rowIndex }}.{{ $field }}" placeholder="{{ $placeholder }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                </tr>
        @endforeach
    </tbody>
</table>
