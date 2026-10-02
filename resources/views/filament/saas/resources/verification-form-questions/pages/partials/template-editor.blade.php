@php
    $flatSections = collect($selectedTemplateVersion['sections'])->flatMap(fn ($section) => [$section, ...$section['children']]);
    $activeKey = $editorSectionKey ?: data_get($flatSections->first(), 'key');
    $activeSection = $flatSections->firstWhere('key', $activeKey);
    $visibleKeys = collect([$activeKey])->merge(collect($activeSection['children'] ?? [])->pluck('key'));
    $previewGroups = collect($selectedTemplateVersion['preview_sections'])->whereIn('key', $visibleKeys);
    $templateThreeInput = 'width:100%;min-height:38px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;padding:8px 10px;font-size:14px;color:#142e25;';
    $templateThreeReadonly = $templateThreeInput;
    $templateThreeFrequencyFieldLabels = array_merge(\App\Models\VerificationFormQuestion::FREQUENCY_BASE_RESPONSE_FIELDS, \App\Models\VerificationFormQuestion::FREQUENCY_CURRENT_OPTIONAL_FIELDS, \App\Models\VerificationFormQuestion::FREQUENCY_ADVANCED_OPTIONAL_FIELDS);
    $templateThreeFrequencySelectFields = ['coverage_status' => ['' => 'Select status', 'Covered' => 'Covered', 'Not Covered' => 'Not Covered', 'Conditional' => 'Conditional'], 'pre_auth_required' => ['' => 'Select pre-auth', 'Yes' => 'Yes', 'No' => 'No'], 'downgrade_applies' => ['' => 'Select downgrade', 'Yes' => 'Yes', 'No' => 'No']];
    $templateThreeFrequencyTextareaFields = ['payment_guideline', 'notes'];
    $templateThreeFrequencyPlaceholders = array_map(fn ($label) => $label, $templateThreeFrequencyFieldLabels);
@endphp
<style>
    .te-workspace {background:#fff;border-top:1px solid #dce3ea;}
    .te-toolbar {display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 0;flex-wrap:wrap;}
    .te-layout {display:grid;grid-template-columns:240px minmax(0,1fr);border-top:1px solid #dce3ea;}
    .te-nav {border-right:1px solid #dce3ea;padding:16px 12px 16px 0;}
    .te-nav button {display:flex;width:100%;text-align:left;justify-content:space-between;gap:12px;padding:10px;border:0;background:transparent;font-size:13px;line-height:1.4;cursor:pointer;color:#334155;}
    .te-nav button[aria-current=true] {background:#e8f5f2;color:#096b62;font-weight:700;border-left:3px solid #0d9488;}
    .te-nav .te-child {padding-left:24px;font-size:12px;}
    .te-review {color:#9a5b08;font-size:11px;}
    .te-main {padding:20px;min-width:0;}
    .te-question {display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid #e2e8f0;}
    .te-subheading {font-size:14px;font-weight:700;margin:24px 0 0;color:#0f766e;}
    .te-preview .uel2-question-note {grid-column:1/-1;}
    .te-preview h3 {font-size:17px;margin:20px 0 12px;font-weight:700;}
    .te-preview h4 {font-size:14px;margin:16px 0 8px;font-weight:700;color:#0f766e;}
    .te-preview .uel2-managed-question {display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,1fr);gap:20px;padding:16px 0;border-bottom:1px solid #e2e8f0;align-items:start;}
    .te-preview .uel2-question-label {font-size:14px;font-weight:600;}
    .te-preview .uel2-question-help {display:block;font-size:12px;color:#64748b;margin:4px 0;}
    .te-preview .uel2-segmented {display:flex;gap:16px;align-items:center;min-height:38px;}
    .te-preview .uel2-segmented label {display:flex;gap:6px;align-items:center;}
    .te-preview .uel2-input-addon {display:flex;align-items:center;gap:8px;}
    .te-preview input:not([type=radio]):not([type=checkbox]), .te-preview select, .te-preview textarea {width:100%;min-width:0;min-height:38px;border:1px solid #cbd5e1;border-radius:6px;padding:8px;font-size:14px;background:#fff;}
    .te-preview .uel2-choice-option {display:flex;gap:8px;align-items:center;padding:4px;}
    .te-benefits {overflow-x:auto;}
    .te-benefits table {width:100%;min-width:960px;table-layout:fixed;border-collapse:collapse;font-size:13px;}
    .te-benefits th:nth-child(1) {width:80px !important;}
    .te-benefits th:nth-child(2) {width:260px;}
    .te-benefits th:nth-child(3) {width:85px !important;}
    .te-benefits th:nth-child(4) {width:150px !important;}
    .te-benefits th:nth-child(5) {width:auto !important;}
    .te-benefits td,.te-benefits th {padding:8px;border:1px solid #e2e8f0;vertical-align:top;}
    .te-benefits th {background:#f1f5f9;text-align:left;}
    .te-empty {padding:16px 0;color:#64748b;font-size:13px;}
    @media(max-width:900px) {.te-layout{grid-template-columns:190px minmax(0,1fr);} .te-preview .uel2-managed-question{grid-template-columns:1fr;gap:10px;}}
    @media(max-width:640px) {.te-layout{grid-template-columns:1fr;} .te-nav{border-right:0;border-bottom:1px solid #dce3ea;max-height:220px;overflow:auto;} .te-main{padding:12px;} .te-question{grid-template-columns:1fr;}}
</style>
<section id="master-template-builder" class="te-workspace">
    <div class="te-toolbar">
        <div><h2 class="df-panel-title">{{ $selectedTemplateVersion['name'] }}</h2><div class="df-small">{{ $selectedTemplateVersion['form_type'] }} · {{ $selectedTemplateVersion['version'] }} · {{ $selectedTemplateVersion['status'] }}</div></div>
        <div class="df-actions">
            <button type="button" class="df-button" wire:click="closeTemplateVersionPanel">Back to templates</button>
            @if ($selectedTemplateVersion['can_edit_directly'])<button type="button" class="df-button" wire:click="mountAction('editDraftDetails')">Template details</button>@endif
            @if ($selectedTemplateVersion['is_draft'] && auth()->user()?->canPublishVerificationTemplate())<button type="button" class="df-button df-button--primary" wire:click="reviewSelectedTemplate">Review & Publish</button>@endif
        </div>
    </div>
    <details><summary class="df-small" style="cursor:pointer;padding-bottom:12px;">Version details · {{ $selectedTemplateVersion['section_count'] }} sections · {{ $selectedTemplateVersion['question_count'] }} questions</summary><p class="df-small">{{ $selectedTemplateVersion['notes'] }} · Created by {{ $selectedTemplateVersion['created_by'] }} · {{ $selectedTemplateVersion['clinic_visibility'] }}</p>@if ($selectedTemplateVersion['can_edit_directly'])<button type="button" class="df-button" style="margin:12px 0;color:#b91c1c;" wire:click="mountAction('deleteUnusedDraft')">Delete unused draft</button>@endif</details>
    <div class="te-layout">
        <nav class="te-nav" aria-label="Template sections">
            @foreach ($selectedTemplateVersion['sections'] as $section)
                <button type="button" aria-current="{{ $activeKey === $section['key'] ? 'true' : 'false' }}" wire:click="selectEditorSection('{{ $section['key'] }}')"><span>{{ $section['title'] }}</span><span>{{ $section['tree_count'] }}</span></button>
                @foreach ($section['children'] as $child)
                    <button type="button" class="te-child" aria-current="{{ $activeKey === $child['key'] ? 'true' : 'false' }}" wire:click="selectEditorSection('{{ $child['key'] }}')"><span>{{ $child['title'] }}{{ ! $child['is_active'] ? ' (Inactive)' : '' }}@if ($selectedTemplateVersion['uses_section_layout'] && $child['is_active'] && ! $child['active_count'] && ! $child['allow_empty'])<span class="te-review"> · Review</span>@endif</span><span>{{ $child['count'] }}</span></button>
                @endforeach
            @endforeach
            @if ($selectedTemplateVersion['can_add_questions'])<button type="button" wire:click="openTemplateSectionModal">+ Add section</button>@endif
        </nav>
        <div class="te-main">
            <div class="te-toolbar" style="padding-top:0;">
                <h3 class="df-panel-title">{{ $activeSection['title'] ?? 'Select a section' }}</h3>
                <div class="df-mode-tabs">
                    <button type="button" class="df-mode-tab {{ ! $showTemplatePreview ? 'is-active' : '' }}" wire:click="showTemplateVersionStructure">Build</button>
                    @if ($selectedTemplateVersion['supports_full'])<button type="button" class="df-mode-tab {{ $showTemplatePreview && $templatePreviewFormType === 'full_form' ? 'is-active' : '' }}" wire:click="setTemplatePreviewFormType('full_form')">{{ $selectedTemplateVersion['supports_short'] ? 'Full Preview' : 'Form Preview' }}</button>@endif
                    @if ($selectedTemplateVersion['supports_short'])<button type="button" class="df-mode-tab {{ $showTemplatePreview && $templatePreviewFormType === 'short_form' ? 'is-active' : '' }}" wire:click="setTemplatePreviewFormType('short_form')">{{ $selectedTemplateVersion['supports_full'] ? 'Short Preview' : 'Form Preview' }}</button>@endif
                </div>
            </div>
            @error('template')<div role="alert" style="color:#b91c1c;">{{ $message }}</div>@enderror
            @error('pdf_layout')<div role="alert" style="color:#b91c1c;">{{ $message }}</div>@enderror
            @if ($showTemplatePreview)
                <div class="df-actions" style="margin-bottom:16px;">
                    <select aria-label="PDF preview format" wire:model="previewOutputMode" class="df-input" style="width:200px;max-width:100%;flex:none;">
                        @foreach (\App\Support\VerificationResultPdf::OUTPUT_MODE_OPTIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                    <button type="button" class="df-button" wire:click="downloadPreviewPdf" wire:loading.attr="disabled">PDF Preview</button>
                    <span class="df-small">Unsaved preview answers</span>
                </div>
                <div class="te-preview">
                    @foreach ($previewGroups as $group)
                        @if ($group['is_subsection'])<h4>{{ $group['title'] }}</h4>@else<h3>{{ $group['title'] }}</h3>@endif
                        @php
                            $previewRuns = collect($group['questions'])
                                ->filter(fn ($question) => $this->previewQuestionVisible($question))
                                ->chunkWhile(fn ($question, $key, $chunk) => $question['type'] === 'frequency_row' && $chunk->last()['type'] === 'frequency_row');
                        @endphp
                        @foreach ($previewRuns as $run)
                            @if ($run->first()['type'] === 'frequency_row')
                                <div class="te-benefits">@include('filament.saas.resources.verifications.pages.partials.template-3-benefit-table', ['benefitRows' => $run->map(fn ($question) => ['index' => $question['id'], 'row' => $codeCoverageData[$question['id']] ?? []])->all()])</div>
                            @else
                                @php($question = $run->first())
                                @include('filament.saas.resources.verifications.pages.partials.template-3-managed-question-row')
                            @endif
                        @endforeach
                        @if (empty($group['questions']) && $group['is_subsection'])<div class="te-empty">{{ $group['allow_empty'] ? 'Intentionally empty' : ($selectedTemplateVersion['uses_section_layout'] ? 'No active questions - review required' : 'No active questions') }}</div>@endif
                    @endforeach
                </div>
            @else
                @if ($selectedTemplateVersion['can_add_questions'] && $activeKey)
                    <div class="df-actions"><a class="df-button" href="{{ $this->getCreateQuestionUrl($activeKey) }}">Add question</a>@if ($this->canAddSubSectionToSection($activeKey))<button type="button" class="df-button" wire:click="openTemplateSectionModal('{{ $activeKey }}')">Add subsection</button>@endif</div>
                @endif
                @foreach ($visibleKeys as $buildKey)
                    @php($buildSection = $flatSections->firstWhere('key', $buildKey))
                    @if ($buildKey !== $activeKey)<h4 class="te-subheading">{{ $buildSection['title'] }}</h4>@endif
                    @forelse (collect($selectedTemplateVersion['question_rows'])->where('section_key', $buildKey) as $question)
                    <div class="te-question"><div><strong>{{ $question['prompt'] }}</strong><div class="df-small">{{ $question['answer_type'] }} · {{ $question['is_active'] ? 'Active' : 'Inactive' }}</div></div>@if ($question['edit_url'])<a class="df-button" href="{{ $question['edit_url'] }}">Edit question</a>@endif</div>
                    @empty
                        @if (! ($buildSection['child_count'] ?? 0))<div class="te-empty">{{ ($buildSection['allow_empty'] ?? false) ? 'Intentionally empty' : 'No questions in this section.' }}</div>@endif
                    @endforelse
                @endforeach
                @if ($selectedTemplateVersion['uses_section_layout'] && $activeSection && ! $activeSection['child_count'] && ! $activeSection['active_count'] && $selectedTemplateVersion['can_edit_directly'] && $activeKey !== 'custom_layout_needs_mapping')
                    <button type="button" class="df-button" wire:click="confirmEmptySection('{{ $activeKey }}')">{{ $activeSection['allow_empty'] ? 'Reopen completeness review' : 'Confirm intentionally empty' }}</button>
                @endif
            @endif
        </div>
    </div>
</section>
