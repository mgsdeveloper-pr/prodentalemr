@php
    $assessment = $this->data['coverage_assessment'] ?? [];
    $coverageService = app(\App\Services\Verification\CoverageAssessmentService::class);
    $coverageMissing = $coverageService->missing($assessment);
    $evidenceTotal = count($coverageService::REQUIRED);
    $evidenceAnswered = collect($coverageService::REQUIRED)->filter(fn ($key) => filled($assessment[$key] ?? null))->count();
@endphp
<section class="coverage-assessment" aria-label="Coverage assessment">
    <style>
        .coverage-assessment { background:#fff; border:1px solid #dce8e3; border-radius:6px; padding:18px; color:#142e25; }
        .coverage-assessment h2 { font-size:16px; margin:0 0 14px; }
        .coverage-assessment-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
        .coverage-assessment label { display:block; font-size:12px; font-weight:600; margin-bottom:5px; }
        .coverage-assessment input,.coverage-assessment select,.coverage-assessment textarea { width:100%; min-height:38px; border:1px solid #dce8e3; border-radius:6px; background:#fff; padding:8px; font-size:14px; }
        .coverage-assessment fieldset { border:0; padding:0; margin:0; min-width:0; }
        .coverage-assessment-note { margin-top:12px; font-size:13px; color:#725016; }
        .coverage-assessment-error { color:#b42318; font-size:13px; }
        @media(max-width:700px) { .coverage-assessment-grid { grid-template-columns:1fr; } }
    </style>
    <h2>Coverage Assessment</h2>
    <fieldset @disabled(! $this->canSubmitForm())>
        <label for="coverage-outcome">Coverage outcome</label>
        <select id="coverage-outcome" wire:model.live="data.coverage_assessment.outcome">
            @if ($assessment === [])<option value="">Not recorded (existing request)</option>@endif
            @foreach ($coverageService::OUTCOMES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
        </select>
        @if ($assessment !== [])
            <div class="coverage-assessment-grid" style="margin-top:14px">
                @foreach ($coverageService::FIELDS as $key => $label)
                    <div>
                        <label for="coverage-{{ $key }}">{{ $label }}{{ in_array($key, $coverageService::REQUIRED, true) ? ' *' : '' }}</label>
                        @if ($key === 'reason')
                            <textarea id="coverage-{{ $key }}" wire:model.blur="data.coverage_assessment.{{ $key }}" rows="2"></textarea>
                        @elseif ($key === 'source')
                            <select id="coverage-source" wire:model.live="data.coverage_assessment.source">
                                <option value="">Select source</option>
                                @foreach ($coverageService::SOURCES as $source)<option>{{ $source }}</option>@endforeach
                            </select>
                        @else
                            <input id="coverage-{{ $key }}" type="{{ in_array($key, ['service_date','termination_date'], true) ? 'date' : 'text' }}" wire:model.blur="data.coverage_assessment.{{ $key }}">
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </fieldset>
    @if (($assessment['outcome'] ?? '') === 'inactive')
        <p class="coverage-assessment-note">Benefit questions not assessed: coverage inactive. Previously saved answers are retained for reference, not confirmed benefits.</p>
        <p style="margin-top:8px">Evidence: {{ $evidenceAnswered }}/{{ $evidenceTotal }} completed. {{ $coverageMissing === [] ? 'Evidence complete; review required.' : 'Evidence incomplete.' }}</p>
    @elseif (($assessment['outcome'] ?? '') === 'unconfirmed')
        <p class="coverage-assessment-note">Coverage unconfirmed. Follow-up required before review or completion.</p>
    @endif
    @foreach ($errors->all() as $message)<p class="coverage-assessment-error">{{ $message }}</p>@endforeach
</section>
