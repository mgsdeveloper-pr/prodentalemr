@include('filament.saas.resources.verifications.pages.partials.template-3-structured-styles')
@php
    $structuredSections = $this->getStructuredTemplateSections();
    $structuredCounts = collect($structuredSections)->mapWithKeys(function ($section) {
        $groups = collect([$section, ...$section['children']]);
        $questions = $groups->flatMap(fn ($group) => $group['questions']);
        $benefits = $groups->flatMap(fn ($group) => $group['benefits']);
        return [$section['key'] => $this->templateGroupProgress([
            'questions' => $questions->all(), 'benefits' => $benefits->all(),
        ])];
    });
    $structuredTotal = $structuredCounts->sum('total');
    $structuredAnswered = $structuredCounts->sum('answered');
@endphp
<div class="uel2-page uel2-structured">
    <section class="uel2-shell">
        <div class="uel2-shell__inner">
            @if ($this->focusMode)
                @include('filament.saas.resources.verifications.pages.partials.template-3-quick-reference-strip')
            @endif
            <details class="uel2-progress-disclosure">
                <summary>
                    <span class="uel2-progress-disclosure__summary">Verification Progress</span>
                    <span class="uel2-progress-disclosure__status">
                        <span>{{ $structuredTotal ? round($structuredAnswered / $structuredTotal * 100) : 0 }}% answered</span>
                        <span class="uel2-pill">{{ $structuredAnswered }}/{{ $structuredTotal }}</span>
                    </span>
                </summary>
                <div class="uel2-progress-disclosure__body">
                    <div class="uel2-progress-list">
                        @foreach ($structuredSections as $progressSection)
                            @php
                                $count = $structuredCounts[$progressSection['key']];
                            @endphp
                            <div class="uel2-progress-item {{ $count['total'] > 0 && $count['answered'] === $count['total'] ? 'uel2-progress-item--done' : '' }}">
                                <div class="uel2-progress-item__meta">
                                    <span class="uel2-progress-item__dot"></span>
                                    <span class="uel2-progress-item__label">{{ $progressSection['label'] }}</span>
                                </div>
                                <span class="uel2-progress-item__count">{{ $count['answered'] }}/{{ $count['total'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </details>
            @foreach ($structuredSections as $section)
                <section class="uel2-section" wire:key="structured-{{ $section['key'] }}">
                    <div class="uel2-header"><h2>{{ $section['label'] }}</h2><span class="uel2-pill">{{ $structuredCounts[$section['key']]['answered'] }}/{{ $structuredCounts[$section['key']]['total'] }} Completed</span></div>
                    <div class="uel2-body">
                        @foreach ([$section, ...$section['children']] as $group)
                            @if (count($group['questions']) || count($group['benefits']))
                                @php
                                    $fields = collect($group['questions'])->pluck('field');
                                    $isAmounts = count($group['questions']) && collect($group['questions'])->every(fn ($q) => $q['type'] === 'currency');
                                    $isDetails = $fields->contains(fn ($field) => in_array($field, [
                                        'context_clinic_name', 'vf_patient_full_name', 'vf_patient_dob',
                                        'vf_subscriber_name', 'vf_insurance_provider_name', 'vf_effective_date',
                                        'vf_group_number', 'vf_verification_date', 'vf_verified_by',
                                    ], true));
                                    $layout = $isAmounts ? 'amounts' : ($isDetails ? 'details' : 'rows');
                                    $detailOrder = [
                                        'vf_patient_full_name', 'vf_patient_dob', 'vf_patient_identifier', 'vf_insured_relation',
                                        'vf_subscriber_name', 'vf_subscriber_dob', 'vf_subscriber_id',
                                        'context_clinic_name', 'vf_appointment_date', 'vf_network_status',
                                        'vf_insurance_provider_name', 'vf_insurance_company_phone_number', 'vf_payer_id', 'vf_group_number',
                                        'vf_effective_date', 'vf_future_termination_date', 'vf_plan_renewal_month', 'vf_group_name',
                                        'vf_fee_schedule', 'vf_insurance_claim_mailing_address',
                                    ];
                                    $displayQuestions = $isDetails
                                        ? collect($group['questions'])->sortBy(fn ($q) => array_search($q['field'], $detailOrder, true) === false ? 100 : array_search($q['field'], $detailOrder, true))
                                        : collect($group['questions']);
                                @endphp
                                <div class="uel2-subsection uel2-structured-group {{ count($group['benefits']) ? 'uel2-structured-group--benefits' : '' }}" wire:key="structured-group-{{ $group['key'] }}">
                                    @if ($group['key'] !== $section['key'])
                                        @php
                                            $groupProgress = $this->templateGroupProgress($group);
                                        @endphp
                                        <div class="uel2-subsection__header"><h3>{{ $group['label'] }}</h3><span class="uel2-pill">{{ $groupProgress['answered'] }}/{{ $groupProgress['total'] }} Completed</span></div>
                                    @endif
                                    <div class="uel2-structured-fields uel2-structured-fields--{{ $layout }}">
                                        @foreach ($displayQuestions as $question)
                                            <div class="uel2-structured-field {{ $question['type'] === 'textarea' || $question['has_note'] ? 'uel2-structured-field--wide' : '' }}">
                                                <div class="uel2-managed-question" wire:key="structured-question-{{ $question['id'] }}">
                                                    <div class="uel2-question-copy">
                                                        <div class="uel2-question-label">{{ $question['label'] }}@if ($question['required'] ?? false)<span aria-hidden="true"> *</span>@endif</div>
                                                        @if (filled($question['help_text']))<div class="uel2-question-help">{{ $question['help_text'] }}</div>@endif
                                                    </div>
                                                    <div class="uel2-question-response {{ filled($question['secondary_field'] ?? null) ? 'uel2-question-response--paired' : '' }}">
                                                        @include('filament.saas.resources.verifications.pages.partials.template-3-answer-control')
                                                        @if (filled($question['secondary_field'] ?? null))
                                                            @include('filament.saas.resources.verifications.pages.partials.template-3-answer-control', ['question' => array_replace($question, [
                                                                'id' => $question['id'].'-secondary', 'field' => $question['secondary_field'],
                                                                'type' => $question['secondary_type'], 'label' => $question['label'].' - additional response',
                                                                'readonly' => false,
                                                            ])])
                                                        @endif
                                                    </div>
                                                    @if ($question['has_note'])
                                                        <div class="uel2-question-note"><label>{{ $question['note_label'] }}</label><textarea wire:model.blur="data.{{ $question['note_field'] }}" aria-label="{{ $question['label'] }} - {{ $question['note_label'] }}" placeholder="{{ $question['note_placeholder'] }}" style="{{ $templateThreeInput }}"></textarea></div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if (count($group['benefits']))
                                        @include('filament.saas.resources.verifications.pages.partials.template-3-benefit-table', ['benefitRows' => $group['benefits']])
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </section>
</div>
