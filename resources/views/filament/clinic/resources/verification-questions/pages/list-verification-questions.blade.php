<x-filament-panels::page>
    @php
        $selectedClinicName = $this->getSelectedClinicName();
        $versionSummary = $this->getVersionSummary();
        $builderSections = $this->getTemplateBuilderSections();
        $builderCounts = $this->getBuilderCounts();
        $selectedSection = $this->getSelectedBuilderSection();
        $builderQuestions = $this->getFilteredBuilderQuestions();
        $templateVersionHistory = $this->getTemplateVersionHistory();
    @endphp

    <style>
        .tb-page { --tb-teal:#0f8f86; --tb-teal-dark:#08756f; --tb-navy:#101936; --tb-text:#334155; --tb-muted:#64748b; --tb-line:#dfe7f1; --tb-soft:#f7f9fc; display:grid; gap:16px; color:var(--tb-text); }
        .tb-header { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; padding:20px 22px; border:1px solid var(--tb-line); border-radius:8px; background:#fff; }
        .tb-eyebrow { color:var(--tb-teal-dark); font-size:11px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
        .tb-title { margin:5px 0 0; color:var(--tb-navy); font-size:25px; line-height:1.2; font-weight:800; }
        .tb-subtitle { margin:7px 0 0; max-width:760px; color:var(--tb-muted); font-size:13px; line-height:1.55; }
        .tb-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
        .tb-button { display:inline-flex; min-height:38px; align-items:center; justify-content:center; gap:7px; padding:8px 13px; border:1px solid #cfd9e7; border-radius:7px; background:#fff; color:var(--tb-navy); font-size:12px; font-weight:800; text-decoration:none; cursor:pointer; }
        .tb-button:hover { border-color:#9fb2c8; background:#f8fafc; }
        .tb-button--primary { border-color:var(--tb-teal-dark); background:var(--tb-teal-dark); color:#fff; }
        .tb-button--primary:hover { background:#06645f; color:#fff; }
        .tb-button[disabled] { opacity:.55; cursor:not-allowed; }
        .tb-strip { display:grid; grid-template-columns:1.35fr .8fr .8fr .8fr 1fr; border:1px solid var(--tb-line); border-radius:8px; background:#fff; overflow:hidden; }
        .tb-stat { min-width:0; padding:14px 16px; border-right:1px solid var(--tb-line); }
        .tb-stat:last-child { border-right:0; }
        .tb-label { color:var(--tb-muted); font-size:10px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
        .tb-value { margin-top:6px; overflow:hidden; color:var(--tb-navy); font-size:13px; font-weight:800; text-overflow:ellipsis; white-space:nowrap; }
        .tb-pill { display:inline-flex; align-items:center; padding:4px 8px; border:1px solid #cfe6e3; border-radius:999px; background:#effaf8; color:#08756f; font-size:10px; font-weight:800; }
        .tb-pill--draft { border-color:#f5d58b; background:#fff9e8; color:#8a5b00; }
        .tb-notice { display:flex; gap:10px; align-items:flex-start; padding:11px 14px; border:1px solid {{ $versionSummary['showing_draft'] ? '#f2d28b' : '#d9e2ed' }}; border-radius:7px; background:{{ $versionSummary['showing_draft'] ? '#fffbeb' : '#f8fafc' }}; color:#475569; font-size:12px; line-height:1.5; }
        .tb-notice-dot { width:8px; height:8px; margin-top:5px; flex:0 0 auto; border-radius:999px; background:{{ $versionSummary['showing_draft'] ? '#d99a16' : '#7b8ba3' }}; }
        .tb-workspace { display:grid; grid-template-columns:270px minmax(0,1fr); min-height:650px; border:1px solid var(--tb-line); border-radius:8px; background:#fff; overflow:hidden; }
        .tb-tree { border-right:1px solid var(--tb-line); background:#fbfcfe; }
        .tb-tree-head { padding:16px; border-bottom:1px solid var(--tb-line); }
        .tb-tree-list { display:grid; gap:4px; padding:10px; }
        .tb-section-button { width:100%; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:8px; align-items:center; padding:10px 11px; border:1px solid transparent; border-radius:6px; background:transparent; color:#334155; text-align:left; cursor:pointer; }
        .tb-section-button:hover { background:#f0f5f9; }
        .tb-section-button.is-active { border-color:#b9ddd8; background:#eaf8f6; color:#08756f; }
        .tb-section-name { min-width:0; font-size:12px; font-weight:800; line-height:1.35; }
        .tb-section-count { color:#7b8ba3; font-size:10px; font-weight:800; white-space:nowrap; }
        .tb-subsections { display:grid; gap:3px; margin:1px 0 4px 15px; padding-left:9px; border-left:1px solid #d6e0eb; }
        .tb-subsections .tb-section-button { padding:8px 9px; }
        .tb-main { min-width:0; }
        .tb-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px; border-bottom:1px solid var(--tb-line); }
        .tb-toolbar-title { color:var(--tb-navy); font-size:17px; font-weight:800; }
        .tb-toolbar-meta { margin-top:3px; color:var(--tb-muted); font-size:11px; }
        .tb-segment { display:inline-flex; padding:3px; border:1px solid var(--tb-line); border-radius:7px; background:#f8fafc; }
        .tb-segment button { min-height:31px; padding:6px 10px; border:0; border-radius:5px; background:transparent; color:#64748b; font-size:11px; font-weight:800; cursor:pointer; }
        .tb-segment button.is-active { background:#fff; color:#08756f; box-shadow:0 1px 3px rgba(15,23,42,.12); }
        .tb-filters { display:grid; grid-template-columns:minmax(220px,1fr) 150px 150px auto; gap:8px; align-items:center; padding:11px 16px; border-bottom:1px solid var(--tb-line); background:#fbfcfe; }
        .tb-input,.tb-select { width:100%; min-height:38px; border:1px solid #cfd9e7; border-radius:7px; background:#fff; padding:8px 10px; color:#1e293b; font-size:12px; }
        .tb-table-wrap { overflow:auto; }
        .tb-table { width:100%; min-width:790px; border-collapse:collapse; table-layout:fixed; }
        .tb-table th { padding:11px 13px; border-bottom:1px solid var(--tb-line); background:#f7f9fc; color:#65758f; font-size:10px; font-weight:800; letter-spacing:.08em; text-align:left; text-transform:uppercase; }
        .tb-table td { padding:12px 13px; border-bottom:1px solid #e8eef5; color:#40506a; font-size:12px; vertical-align:top; }
        .tb-question { color:var(--tb-navy); font-size:13px; font-weight:800; line-height:1.45; }
        .tb-question-meta { display:flex; flex-wrap:wrap; gap:5px; margin-top:6px; }
        .tb-mini { display:inline-flex; padding:3px 6px; border:1px solid #dbe4ee; border-radius:999px; background:#f8fafc; color:#687892; font-size:9px; font-weight:800; }
        .tb-mini--system { border-color:#cfe0f6; background:#eff6ff; color:#2563a6; }
        .tb-row-actions { display:flex; justify-content:flex-end; align-items:center; gap:6px; flex-wrap:wrap; }
        .tb-icon-button { display:inline-flex; width:auto; min-width:29px; height:29px; align-items:center; justify-content:center; padding:0 7px; border:1px solid #d6e0eb; border-radius:6px; background:#fff; color:#506078; font-size:11px; font-weight:900; text-decoration:none; cursor:pointer; }
        .tb-reorder-note { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:11px 16px; border-bottom:1px solid var(--tb-line); background:#effaf8; color:#315a57; font-size:11px; line-height:1.5; }
        .tb-order-number { display:inline-flex; min-width:42px; min-height:30px; align-items:center; justify-content:center; border:1px solid #d5dfeb; border-radius:6px; background:#f8fafc; color:#334155; font-size:11px; font-weight:800; }
        .tb-empty { padding:42px 20px; color:#64748b; font-size:13px; line-height:1.6; text-align:center; }
        .tb-preview { display:grid; gap:12px; padding:18px; background:#f8fafc; }
        .tb-preview-row { padding:13px 14px; border:1px solid #dfe7f1; border-radius:7px; background:#fff; }
        .tb-preview-label { margin-bottom:8px; color:var(--tb-navy); font-size:12px; font-weight:800; }
        .tb-preview-control { min-height:37px; border:1px solid #d5dfeb; border-radius:6px; background:#fbfcfe; color:#94a3b8; padding:9px 11px; font-size:11px; }
        .tb-history { border:1px solid var(--tb-line); border-radius:8px; background:#fff; }
        .tb-history summary { display:flex; justify-content:space-between; gap:12px; padding:14px 16px; color:var(--tb-navy); font-size:13px; font-weight:800; cursor:pointer; list-style:none; }
        .tb-history-list { display:grid; gap:8px; padding:0 16px 16px; }
        .tb-history-row { display:flex; justify-content:space-between; gap:12px; padding:10px 12px; border:1px solid #e3eaf2; border-radius:6px; color:#475569; font-size:11px; }
        .tb-modal-backdrop { position:fixed; inset:0; z-index:70; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.45); }
        .tb-modal { width:min(600px,100%); max-height:calc(100vh - 40px); overflow:auto; border:1px solid var(--tb-line); border-radius:8px; background:#fff; box-shadow:0 24px 70px rgba(15,23,42,.24); }
        .tb-modal-head,.tb-modal-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:15px 18px; border-bottom:1px solid var(--tb-line); }
        .tb-modal-foot { justify-content:flex-end; border-top:1px solid var(--tb-line); border-bottom:0; }
        .tb-modal-body { display:grid; gap:14px; padding:18px; }
        .tb-confirm-box { display:grid; gap:7px; padding:13px 14px; border:1px solid #dbe5ef; border-radius:7px; background:#f8fafc; }
        .tb-confirm-label { color:var(--tb-muted); font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .tb-confirm-value { color:var(--tb-navy); font-size:13px; font-weight:800; }
        .tb-confirm-note { margin:0; color:#52627a; font-size:12px; line-height:1.55; }
        .tb-field { display:grid; gap:6px; }
        @media(max-width:1050px){ .tb-strip{grid-template-columns:repeat(2,minmax(0,1fr));}.tb-stat{border-bottom:1px solid var(--tb-line)}.tb-workspace{grid-template-columns:220px minmax(0,1fr)}.tb-filters{grid-template-columns:1fr 1fr}.tb-filters .tb-search{grid-column:1/-1} }
        @media(max-width:760px){ .tb-header{display:grid}.tb-actions{justify-content:flex-start}.tb-strip{grid-template-columns:1fr}.tb-stat{border-right:0}.tb-workspace{grid-template-columns:1fr}.tb-tree{border-right:0;border-bottom:1px solid var(--tb-line)}.tb-tree-list{max-height:280px;overflow:auto}.tb-toolbar{align-items:flex-start;flex-direction:column}.tb-filters{grid-template-columns:1fr}.tb-filters .tb-search{grid-column:auto}.tb-history-row{display:grid} }
    </style>

    <div class="tb-page">
        <section class="tb-header">
            <div>
                <div class="tb-eyebrow">Clinic Template Builder</div>
                <h2 class="tb-title">{{ $versionSummary['showing_draft'] ? $versionSummary['working_name'] : $versionSummary['active_name'] }}</h2>
                <p class="tb-subtitle">Build the clinic verification form by section, manage clinic questions, confirm ordering, and preview the form before publishing.</p>
            </div>
            <div class="tb-actions">
                <a href="{{ \App\Filament\Clinic\Pages\VerificationSettings::getUrl(['section' => 'template-management']) }}" wire:navigate class="tb-button">Back to Templates</a>
                @if ($selectedClinicName && $versionSummary['can_manage'])
                    @if (! $versionSummary['has_draft'])
                        <button type="button" class="tb-button tb-button--primary" wire:click="openCreateDraftModal" wire:loading.attr="disabled">Create Draft</button>
                    @elseif (! $versionSummary['showing_draft'])
                        <button type="button" class="tb-button tb-button--primary" wire:click="openDraftVersion" wire:loading.attr="disabled">Open Draft</button>
                    @else
                        <button type="button" class="tb-button" wire:click="closeDraftVersion">View Published</button>
                        <button type="button" class="tb-button tb-button--primary" wire:click="publishDraftVersion" wire:confirm="Publish this clinic template draft?" wire:loading.attr="disabled">Publish Draft</button>
                    @endif
                @endif
            </div>
        </section>

        @if ($selectedClinicName)
            <section class="tb-strip">
                <div class="tb-stat"><div class="tb-label">Clinic</div><div class="tb-value">{{ $selectedClinicName }}</div></div>
                <div class="tb-stat"><div class="tb-label">Template ID</div><div class="tb-value">{{ $versionSummary['template_id'] }}</div></div>
                <div class="tb-stat"><div class="tb-label">Status</div><div class="tb-value"><span class="tb-pill {{ $versionSummary['showing_draft'] ? 'tb-pill--draft' : '' }}">{{ $versionSummary['showing_draft'] ? 'Draft' : 'Published & Active' }}</span></div></div>
                <div class="tb-stat"><div class="tb-label">Form Type</div><div class="tb-value">{{ $versionSummary['showing_draft'] ? $versionSummary['working_form_type'] : $versionSummary['active_form_type'] }}</div></div>
                <div class="tb-stat"><div class="tb-label">Structure</div><div class="tb-value">{{ $builderCounts['main_sections'] }} main / {{ $builderCounts['sub_sections'] }} sub / {{ $builderCounts['active_questions'] }}/{{ $builderCounts['questions'] }} active</div></div>
            </section>

            <div class="tb-notice"><span class="tb-notice-dot"></span><span><strong>{{ $versionSummary['showing_draft'] ? 'Draft editing is active.' : 'Published template is protected.' }}</strong> {{ $versionSummary['showing_draft'] ? 'Clinic changes remain isolated until this draft is published.' : 'Create or open a draft to add, edit, or reorder clinic questions. Existing verification snapshots never change automatically.' }}</span></div>

            <section class="tb-workspace">
                <aside class="tb-tree">
                    <div class="tb-tree-head"><div class="tb-eyebrow">Template Structure</div><div class="tb-toolbar-meta">{{ $builderCounts['main_sections'] }} main sections and {{ $builderCounts['sub_sections'] }} nested sub-sections</div></div>
                    <div class="tb-tree-list">
                        @foreach ($builderSections as $section)
                            <button type="button" wire:click="selectBuilderSection('{{ $section['key'] }}')" class="tb-section-button {{ ($selectedSection['key'] ?? null) === $section['key'] ? 'is-active' : '' }}"><span class="tb-section-name">{{ $section['title'] }}</span><span class="tb-section-count">{{ $section['total_active_count'] }}/{{ $section['total_count'] }}</span></button>
                            @if (count($section['children']) > 0)
                                <div class="tb-subsections">
                                    @foreach ($section['children'] as $child)
                                        <button type="button" wire:click="selectBuilderSection('{{ $child['key'] }}')" class="tb-section-button {{ ($selectedSection['key'] ?? null) === $child['key'] ? 'is-active' : '' }}"><span class="tb-section-name">{{ str($child['title'])->afterLast(' / ') }}</span><span class="tb-section-count">{{ $child['active_count'] }}/{{ $child['count'] }}</span></button>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                    @if ($versionSummary['showing_draft'])
                        <div style="display:grid;gap:7px;padding:12px;border-top:1px solid var(--tb-line);"><button type="button" class="tb-button" wire:click="openTemplateSectionModal('section')">Add Section</button><button type="button" class="tb-button" wire:click="openTemplateSectionModal('sub_section')">Add Sub-section</button></div>
                    @endif
                </aside>

                <div class="tb-main">
                    <div class="tb-toolbar">
                        <div><div class="tb-toolbar-title">{{ $selectedSection['title'] ?? 'Template Questions' }}</div><div class="tb-toolbar-meta">{{ $builderQuestions->count() }} matching questions · {{ $versionSummary['showing_draft'] ? 'Draft workspace' : 'Published review' }}</div></div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <div class="tb-segment" aria-label="Builder view"><button type="button" wire:click="setBuilderView('questions')" class="{{ $builderView === 'questions' ? 'is-active' : '' }}">Questions</button><button type="button" wire:click="setBuilderView('reorder')" class="{{ $builderView === 'reorder' ? 'is-active' : '' }}">{{ $versionSummary['showing_draft'] ? 'Reorder' : 'Create Draft to Reorder' }}</button><button type="button" wire:click="setBuilderView('preview')" class="{{ $builderView === 'preview' ? 'is-active' : '' }}">Form Preview</button></div>
                            @if ($selectedSection)
                                @if ($versionSummary['showing_draft'])
                                    <a href="{{ $this->getCreateUrl($selectedSection['key']) }}" wire:navigate class="tb-button tb-button--primary">Add Question</a>
                                @elseif ($versionSummary['can_manage'])
                                    <button type="button" wire:click="beginTemplateChange('questions')" wire:loading.attr="disabled" class="tb-button tb-button--primary">Create Draft to Add Question</button>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="tb-filters">
                        <input type="search" wire:model.live.debounce.250ms="questionSearch" class="tb-input tb-search" placeholder="Search questions in this section">
                        <select wire:model.live="questionStatus" class="tb-select" aria-label="Question status"><option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
                        <select wire:model.live="questionOwnership" class="tb-select" aria-label="Question ownership"><option value="all">All questions</option><option value="system">Inherited from Master</option><option value="clinic">Added by Clinic</option></select>
                        <button type="button" wire:click="clearQuestionFilters" class="tb-button">Clear</button>
                    </div>

                    @if ($builderView === 'reorder')
                        <div class="tb-reorder-note"><span><strong>Reorder {{ $selectedSection['title'] ?? 'questions' }}</strong><br>Use the arrow controls to move each question. Changes remain inside the draft until it is published.</span><button type="button" wire:click="setBuilderView('questions')" class="tb-button">Done</button></div>
                        <div class="tb-table-wrap"><table class="tb-table" style="min-width:640px"><thead><tr><th style="width:13%">Position</th><th>Question</th><th style="width:22%;text-align:right">Move</th></tr></thead><tbody>
                            @forelse ($builderQuestions as $question)
                                <tr>
                                    <td><span class="tb-order-number">{{ $question['sort_order'] }}</span></td>
                                    <td><div class="tb-question">{{ $question['prompt'] }}</div><div class="tb-question-meta"><span class="tb-mini">{{ $question['section_title'] }}</span><span class="tb-mini {{ $question['is_builtin'] ? 'tb-mini--system' : '' }}">{{ $question['is_builtin'] ? 'Inherited from Master' : 'Added by Clinic' }}</span></div></td>
                                    <td><div class="tb-row-actions"><button type="button" class="tb-icon-button" title="Move question up" aria-label="Move {{ $question['prompt'] }} up" wire:click="repositionQuestion({{ $question['id'] }}, 'up')">↑</button><button type="button" class="tb-icon-button" title="Move question down" aria-label="Move {{ $question['prompt'] }} down" wire:click="repositionQuestion({{ $question['id'] }}, 'down')">↓</button></div></td>
                                </tr>
                            @empty<tr><td colspan="3"><div class="tb-empty">No questions are available to reorder in this section.</div></td></tr>@endforelse
                        </tbody></table></div>
                    @elseif ($builderView === 'questions')
                        <div class="tb-table-wrap"><table class="tb-table"><thead><tr><th style="width:48%">Question</th><th style="width:14%">Form</th><th style="width:14%">Answer</th><th style="width:10%">Status</th><th style="width:14%;text-align:right">Actions</th></tr></thead><tbody>
                            @forelse ($builderQuestions as $question)
                                <tr>
                                    <td><div class="tb-question">{{ $question['prompt'] }}</div><div class="tb-question-meta"><span class="tb-mini">{{ $question['section_title'] }}</span><span class="tb-mini">Order {{ $question['sort_order'] }}</span><span class="tb-mini {{ $question['is_builtin'] ? 'tb-mini--system' : '' }}">{{ $question['is_builtin'] ? 'Inherited from Master' : 'Added by Clinic' }}</span></div></td>
                                    <td>{{ $question['form_type'] }}</td><td>{{ $question['input_type'] }}</td><td><span class="tb-pill {{ $question['is_active'] ? '' : 'tb-pill--draft' }}">{{ $question['is_active'] ? 'Active' : 'Inactive' }}</span></td>
                                    <td><div class="tb-row-actions">
                                        @if ($versionSummary['showing_draft'])
                                            @if (! $question['is_builtin'])<a href="{{ $this->getEditUrl($question['id']) }}" wire:navigate class="tb-icon-button" title="Edit question">Edit</a><button type="button" class="tb-icon-button" title="Delete question" wire:click="deleteQuestion({{ $question['id'] }})" wire:confirm="Delete this clinic question?">×</button>@else<span class="tb-mini tb-mini--system">Locked</span>@endif
                                        @else<span class="tb-mini">Read-only</span>@endif
                                    </div></td>
                                </tr>
                            @empty<tr><td colspan="5"><div class="tb-empty">No questions match this section and filter.{{ $versionSummary['showing_draft'] ? ' Add a clinic question here or clear the filters.' : '' }}</div></td></tr>@endforelse
                        </tbody></table></div>
                    @else
                        <div class="tb-preview">
                            @forelse ($builderQuestions->where('is_active', true) as $question)
                                <div class="tb-preview-row"><div class="tb-preview-label">{{ $question['prompt'] }}</div>
                                    @if (in_array($question['input_type'], ['Checkbox', 'Boolean', 'Toggle'], true))<label style="display:flex;align-items:center;gap:8px;color:#64748b;font-size:11px;"><input type="checkbox" disabled> Yes</label>
                                    @elseif ($question['input_type'] === 'Textarea')<div class="tb-preview-control" style="min-height:66px;">Response</div>
                                    @elseif (in_array($question['input_type'], ['Dropdown', 'Select'], true))<div class="tb-preview-control">Select an option</div>
                                    @else<div class="tb-preview-control">{{ $question['input_type'] }} response</div>@endif
                                </div>
                            @empty<div class="tb-empty">No active questions are available for preview.</div>@endforelse
                        </div>
                    @endif
                </div>
            </section>

            <details class="tb-history"><summary><span>Previous Template Versions</span><span style="color:#64748b;font-size:11px;">{{ count($templateVersionHistory) }} records</span></summary><div class="tb-history-list">
                @forelse ($templateVersionHistory as $version)<div class="tb-history-row"><span><strong style="color:var(--tb-navy)">{{ $version['name'] }}</strong> · {{ $version['status'] }} · {{ $version['form_type'] }}</span><span>{{ $version['published_at'] ? 'Published '.$version['published_at'] : 'Not published' }}</span></div>@empty<div class="tb-empty">No previous versions are available.</div>@endforelse
            </div></details>
        @else
            <div class="tb-empty" style="border:1px dashed #cbd5e1;border-radius:8px;background:#fff;">Select a clinic from Clinic Scope to manage its template.</div>
        @endif

        @if ($showCreateDraftModal)
            <div class="tb-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="draft-confirm-title" x-on:keydown.escape.window="$wire.closeCreateDraftModal()"><form wire:submit.prevent="submitCreateDraftVersion" class="tb-modal" style="width:min(520px,100%);">
                <div class="tb-modal-head"><div><div id="draft-confirm-title" class="tb-title" style="font-size:19px;">Create an editable copy?</div><div class="tb-subtitle">The published clinic template cannot be changed directly.</div></div><button type="button" class="tb-icon-button" wire:click="closeCreateDraftModal" aria-label="Close">×</button></div>
                <div class="tb-modal-body">
                    <p class="tb-confirm-note">A protected working draft will be created automatically. Existing verification requests, completed forms, and historical snapshots will remain unchanged.</p>
                    <div class="tb-confirm-box"><span class="tb-confirm-label">Continue with</span><span class="tb-confirm-value">{{ $pendingBuilderAction === 'reorder' ? 'Reorder questions' : ($pendingBuilderAction === 'questions' ? 'Add question to '.($selectedSection['title'] ?? 'selected section') : 'Edit clinic template') }}</span></div>
                </div>
                <div class="tb-modal-foot"><button type="button" class="tb-button" wire:click="closeCreateDraftModal" wire:loading.attr="disabled" wire:target="submitCreateDraftVersion">Cancel</button><button type="submit" class="tb-button tb-button--primary" wire:loading.attr="disabled" wire:target="submitCreateDraftVersion" autofocus><span wire:loading.remove wire:target="submitCreateDraftVersion">{{ $pendingBuilderAction === 'reorder' ? 'Continue to Reorder' : ($pendingBuilderAction === 'questions' ? 'Continue to Add Question' : 'Create Working Draft') }}</span><span wire:loading wire:target="submitCreateDraftVersion">Preparing draft...</span></button></div>
            </form></div>
        @endif

        @if ($showTemplateSectionModal)
            <div class="tb-modal-backdrop" role="dialog" aria-modal="true"><form wire:submit.prevent="submitTemplateSection" class="tb-modal">
                <div class="tb-modal-head"><div><div class="tb-title" style="font-size:19px;">{{ $templateSectionMode === 'sub_section' ? 'Add Sub-section' : 'Add Section' }}</div><div class="tb-subtitle">This structure change applies only to the open clinic draft.</div></div><button type="button" class="tb-icon-button" wire:click="closeTemplateSectionModal">×</button></div>
                <div class="tb-modal-body">@if ($templateSectionMode === 'sub_section')<div class="tb-field"><label class="tb-label" for="parent-section">Parent section</label><select id="parent-section" class="tb-select" wire:model.defer="newTemplateSectionData.parent_section_key"><option value="">Select a main section</option>@foreach ($builderSections as $section)<option value="{{ $section['key'] }}">{{ $section['title'] }}</option>@endforeach</select>@error('newTemplateSectionData.parent_section_key')<span style="color:#b91c1c;font-size:11px;">{{ $message }}</span>@enderror</div>@endif<div class="tb-field"><label class="tb-label" for="section-name">{{ $templateSectionMode === 'sub_section' ? 'Sub-section name' : 'Section name' }}</label><input id="section-name" class="tb-input" wire:model.defer="newTemplateSectionData.label">@error('newTemplateSectionData.label')<span style="color:#b91c1c;font-size:11px;">{{ $message }}</span>@enderror</div></div>
                <div class="tb-modal-foot"><button type="button" class="tb-button" wire:click="closeTemplateSectionModal">Cancel</button><button type="submit" class="tb-button tb-button--primary" wire:loading.attr="disabled">Add to Draft</button></div>
            </form></div>
        @endif
    </div>
</x-filament-panels::page>
