<x-filament-panels::page>
    @php
        $templateVersionHistory = collect($this->getTemplateVersionHistory());
        $publishedCount = $templateVersionHistory->where('status', 'Published')->count();
        $draftCount = $templateVersionHistory->where('status', 'Draft')->count();
        $archivedCount = max(0, $templateVersionHistory->count() - $publishedCount - $draftCount);
        $activeTemplate = $templateVersionHistory->first(fn (array $version): bool => (bool) ($version['is_active'] ?? false));
        $previousPublishedCount = max(0, $publishedCount - ($activeTemplate ? 1 : 0));
        $selectedTemplateVersion = $this->getSelectedTemplateVersionDetail();
    @endphp

    <style>
        .df-page {
            display: grid;
            width: 100%;
            max-width: none;
            margin: 0;
            gap: 18px;
            color: #0f172a;
        }

        .df-page * {
            letter-spacing: 0;
        }

        .df-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .df-title {
            margin: 0;
            color: #0f172a;
            font-size: 24px;
            line-height: 1.2;
            font-weight: 700;
        }

        .df-subtitle {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.55;
        }

        .df-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .df-card,
        .df-panel {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: none;
        }

        .df-card {
            padding: 16px;
        }

        .df-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .df-value {
            margin-top: 8px;
            font-size: 28px;
            line-height: 1;
            font-weight: 700;
            color: #0f172a;
        }

        .df-summary {
            display: grid;
            grid-template-columns: minmax(220px, 1.5fr) repeat(3, minmax(130px, 0.7fr));
            margin-top: 0;
            overflow: hidden;
            order: 1;
        }

        .df-version-list {
            order: 3;
        }

        .df-selected-builder {
            order: 2;
        }

        .df-summary__item {
            min-width: 0;
            padding: 14px 16px;
            border-right: 1px solid #e2e8f0;
        }

        .df-summary__item:last-child {
            border-right: 0;
        }

        .df-summary__value {
            margin-top: 5px;
            color: #0f172a;
            font-size: 15px;
            line-height: 1.35;
            font-weight: 700;
        }

        .df-summary__note {
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
        }

        .df-input,
        .df-select {
            min-height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            font-size: 14px;
        }

        .df-input {
            flex: 1 1 320px;
            padding: 10px 12px;
        }

        .df-select {
            padding: 10px 12px;
        }

        .df-panel {
            margin-top: 0;
            overflow: hidden;
        }

        .df-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .df-panel-title {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
        }

        .df-panel-meta {
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
        }

        .df-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .df-button--primary {
            border-color: #0f766e;
            background: #0f766e;
            color: #ffffff;
        }

        .df-button--primary:hover {
            border-color: #115e59;
            background: #115e59;
        }

        .df-table-wrap {
            overflow-x: auto;
        }

        .df-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .df-table--versions {
            min-width: 820px;
        }

        .df-table--versions th:nth-child(1),
        .df-table--versions td:nth-child(1) {
            width: 34%;
        }

        .df-table--versions th:nth-child(4),
        .df-table--versions td:nth-child(4) {
            min-width: 170px;
        }

        .df-table--versions th:last-child,
        .df-table--versions td:last-child {
            min-width: 190px;
        }

        .df-row-actions {
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            gap: 6px;
        }

        .df-row-menu {
            position: relative;
        }

        .df-row-menu > summary {
            list-style: none;
        }

        .df-row-menu > summary::-webkit-details-marker {
            display: none;
        }

        .df-row-menu__panel {
            position: absolute;
            z-index: 20;
            top: calc(100% + 6px);
            right: 0;
            display: grid;
            min-width: 180px;
            padding: 6px;
            border: 1px solid #dbe4ee;
            border-radius: 6px;
            background: #ffffff;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14);
        }

        .df-row-menu__action {
            padding: 9px 10px;
            border: 0;
            border-radius: 4px;
            background: transparent;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
        }

        .df-row-menu__action:hover {
            background: #f1f5f9;
            color: #0f766e;
        }

        .df-table thead {
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .df-table th {
            padding: 12px 20px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .df-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            vertical-align: top;
        }

        .df-table tbody tr:hover {
            background: #f8fafc;
        }

        .df-name {
            color: #0f172a;
            font-weight: 700;
        }

        .df-small {
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
        }

        .df-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .df-badge--teal {
            background: #f0fdfa;
            color: #0f766e;
        }

        .df-badge--blue {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .df-badge--amber {
            background: #fffbeb;
            color: #b45309;
        }

        .df-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: currentColor;
        }

        .df-table-slot {
            padding: 0 20px 20px;
        }

        .df-detail-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .df-detail-cell {
            padding: 13px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .df-preview-section {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
        }

        .df-preview-section__head {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
        }

        .df-preview-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 170px;
            gap: 12px;
            padding: 11px 14px;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
        }

        .df-preview-row:last-child {
            border-bottom: 0;
        }

        .df-mode-tabs {
            display: inline-flex;
            gap: 6px;
            padding: 4px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .df-mode-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 6px 10px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .df-mode-tab.is-active {
            background: #ffffff;
            color: #0f766e;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .df-row-button {
            min-height: 32px;
            padding: 6px 10px;
            font-size: 12px;
        }

        .df-child-row td:first-child {
            padding-left: 48px;
        }

        .df-collapse-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            margin-right: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
        }

        .df-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.42);
        }

        .df-modal {
            width: min(680px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            border: 1px solid #dbe4ee;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.24);
        }

        .df-modal-body {
            padding: 18px 20px;
        }

        .df-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .df-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .df-field--full {
            grid-column: 1 / -1;
        }

        .df-field label {
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .df-field .df-input,
        .df-field .df-select {
            width: 100%;
            flex: 0 0 auto;
        }

        .df-textarea {
            min-height: 92px;
            resize: vertical;
        }

        @media (max-width: 1100px) {
            .df-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .df-summary__item:nth-child(2) {
                border-right: 0;
            }

            .df-summary__item:nth-child(-n+2) {
                border-bottom: 1px solid #e2e8f0;
            }
        }

        @media (max-width: 720px) {
            .df-header,
            .df-panel-header {
                flex-direction: column;
                align-items: stretch;
            }

            .df-actions {
                justify-content: flex-start;
            }

            .df-summary,
            .df-detail-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .df-summary__item,
            .df-summary__item:nth-child(2) {
                border-right: 0;
                border-bottom: 1px solid #e2e8f0;
            }

            .df-summary__item:last-child {
                border-bottom: 0;
            }

            .df-preview-row {
                grid-template-columns: minmax(0, 1fr);
            }

            .df-form-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div
        class="df-page"
        x-data
        x-on:master-template-version-opened.window="$nextTick(() => setTimeout(() => document.getElementById('master-template-builder')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 75))"
    >
        <section class="df-panel df-summary" aria-label="Master Template summary">
            <div class="df-summary__item">
                <div class="df-label">Current Master Template</div>
                <div class="df-summary__value">{{ $activeTemplate['version'] ?? 'Not published' }}</div>
                <div class="df-summary__note">
                    {{ $activeTemplate ? ($activeTemplate['form_type'] . ' · ' . $activeTemplate['clinic_visibility']) : 'Publish a draft to activate the template.' }}
                </div>
            </div>
            <div class="df-summary__item">
                <div class="df-label">Draft Templates</div>
                <div class="df-summary__value">{{ $draftCount }}</div>
                <div class="df-summary__note">Editable working copies</div>
            </div>
            <div class="df-summary__item">
                <div class="df-label">Previous Published</div>
                <div class="df-summary__value">{{ $previousPublishedCount }}</div>
                <div class="df-summary__note">Retained for history</div>
            </div>
            <div class="df-summary__item">
                <div class="df-label">Archived</div>
                <div class="df-summary__value">{{ $archivedCount }}</div>
                <div class="df-summary__note">No longer in use</div>
            </div>
        </section>

        <section class="df-panel df-version-list">
            <div class="df-panel-header">
                <div>
                    <h2 class="df-panel-title">Template Versions</h2>
                    <div class="df-panel-meta">Open a draft to manage sections and questions, or preview any retained version.</div>
                </div>
            </div>

            <div class="df-table-wrap">
                <table class="df-table df-table--versions">
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Clinic Availability</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $currentTemplateGroup = null;
                            $templateGroupLabels = [
                                'active' => 'Active Master Template',
                                'draft' => 'Draft Templates',
                                'previous' => 'Template History',
                                'archived' => 'Archived Templates',
                            ];
                        @endphp
                        @forelse ($templateVersionHistory as $version)
                            @if ($currentTemplateGroup !== $version['row_group'])
                                @php $currentTemplateGroup = $version['row_group']; @endphp
                                <tr>
                                    <td colspan="5" style="padding:10px 16px;background:#fbfdff;color:#64748b;font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;">
                                        {{ $templateGroupLabels[$version['row_group']] ?? 'Templates' }}
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td>
                                    <div class="df-name">{{ $version['name'] }}</div>
                                    <div class="df-small">{{ $version['description'] }}</div>
                                    <div class="df-small">{{ $version['version'] }} &middot; {{ $version['form_type'] }}</div>
                                </td>
                                <td>{{ $version['clinic_visibility'] }}</td>
                                <td>
                                    <span class="df-badge {{ $version['is_draft'] ? 'df-badge--amber' : (($version['is_active'] && $version['is_available_to_clinics']) ? 'df-badge--teal' : '') }}">
                                        <span class="df-dot"></span>
                                        {{ $version['status_label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $version['updated_at'] }}</div>
                                    <div class="df-small">Published: {{ $version['published_at'] ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="df-row-actions">
                                        <button type="button" class="df-button" wire:click="selectTemplateVersion({{ $version['id'] }})" title="Open Version">
                                            {{ $version['is_draft'] ? 'Open Builder' : 'View Structure' }}
                                        </button>
                                        <details class="df-row-menu">
                                            <summary class="df-button" title="More template actions">More</summary>
                                            <div class="df-row-menu__panel">
                                                @if ($version['is_draft'] && $version['can_edit_directly'] && ! $version['is_working_draft'])
                                                    <button
                                                        type="button"
                                                        class="df-row-menu__action"
                                                        wire:click="setWorkingDraft({{ $version['id'] }})"
                                                        wire:confirm="Use this as the working Master Template draft for new question entry?"
                                                    >
                                                        Set as Working Draft
                                                    </button>
                                                @endif
                                                @if ($version['is_draft'] && $version['can_edit_directly'])
                                                    <button
                                                        type="button"
                                                        class="df-row-menu__action"
                                                        wire:click="archiveDraft({{ $version['id'] }})"
                                                        wire:confirm="Archive this draft? Published templates and saved verification snapshots will not be affected."
                                                    >
                                                        Archive Draft
                                                    </button>
                                                @elseif ($version['row_group'] === 'archived' && ($version['can_restore'] ?? false))
                                                    <button type="button" class="df-row-menu__action" wire:click="restoreArchivedDraft({{ $version['id'] }})">
                                                        Restore Draft
                                                    </button>
                                                @endif
                                                <button type="button" class="df-row-menu__action" wire:click="showTemplateVersionPreview({{ $version['id'] }})">
                                                    Preview Form
                                                </button>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;color:#64748b;padding:24px;">No templates found. Create a draft to begin.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($selectedTemplateVersion)
            <section class="df-panel df-selected-builder" id="master-template-builder" style="scroll-margin-top:90px;">
                <div class="df-panel-header">
                    <div>
                        <h2 class="df-panel-title">{{ $selectedTemplateVersion['name'] }}</h2>
                        <div class="df-panel-meta">
                            {{ $selectedTemplateVersion['version'] }} · {{ $selectedTemplateVersion['status'] }} · {{ $selectedTemplateVersion['scope'] }}
                        </div>
                    </div>
                    <div class="df-actions">
                        @if ($selectedTemplateVersion['is_draft'])
                            <button
                                type="button"
                                class="df-button df-button--primary"
                                wire:click="mountAction('publishDraftVersion')"
                            >
                                Publish Draft
                            </button>
                        @endif
                        @if ($selectedTemplateVersion['can_edit_directly'])
                            <button type="button" class="df-button" wire:click="mountAction('editDraftDetails')">
                                Edit Draft Details
                            </button>
                        @endif
                        @if ($selectedTemplateVersion['can_delete_permanently'])
                            <button type="button" class="df-button" style="border-color:#fecdd3;color:#be123c;background:#fff1f2;" wire:click="mountAction('deleteUnusedDraft')">
                                Delete Draft
                            </button>
                        @elseif ($selectedTemplateVersion['is_draft'] && filled($selectedTemplateVersion['lock_reason']))
                            <span class="df-small" style="max-width:300px;text-align:right;">{{ $selectedTemplateVersion['lock_reason'] }}</span>
                        @endif
                        @if ($selectedTemplateVersion['can_add_questions'])
                            <a href="{{ $this->getCreateQuestionUrl() }}" class="df-button" style="background:#0f766e;color:#ffffff;border-color:#0f766e;text-decoration:none;">
                                New Question
                            </a>
                        @endif
                        <button type="button" class="df-button" wire:click="showTemplateVersionPreview({{ $selectedTemplateVersion['id'] }}, 'full_form')">Preview Full</button>
                        <button type="button" class="df-button" wire:click="showTemplateVersionPreview({{ $selectedTemplateVersion['id'] }}, 'short_form')">Preview Short</button>
                        <button type="button" class="df-button" wire:click="closeTemplateVersionPanel">Close</button>
                    </div>
                </div>

                <div style="padding:20px;">
                    <div class="df-detail-grid">
                        @foreach ([
                            ['label' => 'Sections', 'value' => $selectedTemplateVersion['section_count']],
                            ['label' => 'Sub-sections', 'value' => $selectedTemplateVersion['sub_section_count']],
                            ['label' => 'Questions', 'value' => $selectedTemplateVersion['question_count']],
                            ['label' => 'Active Questions', 'value' => $selectedTemplateVersion['active_question_count']],
                            ['label' => 'Full Form', 'value' => $selectedTemplateVersion['full_question_count']],
                            ['label' => 'Short Form', 'value' => $selectedTemplateVersion['short_question_count']],
                            ['label' => 'Form Type', 'value' => $selectedTemplateVersion['form_type']],
                            ['label' => 'Clinic Availability', 'value' => $selectedTemplateVersion['clinic_visibility']],
                            ['label' => 'Published', 'value' => $selectedTemplateVersion['published_at']],
                            ['label' => 'Created By', 'value' => $selectedTemplateVersion['created_by']],
                        ] as $detail)
                            <div class="df-detail-cell">
                                <div class="df-label">{{ $detail['label'] }}</div>
                                <div style="margin-top:6px;font-size:15px;font-weight:700;color:#0f172a;">{{ $detail['value'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if (filled($selectedTemplateVersion['notes']) && $selectedTemplateVersion['notes'] !== '-')
                        <div style="margin-top:14px;padding:14px;border:1px solid #e2e8f0;border-radius:10px;background:#ffffff;">
                            <div class="df-label">Change Description</div>
                            <div style="margin-top:6px;color:#475569;font-size:13px;line-height:1.55;">{{ $selectedTemplateVersion['notes'] }}</div>
                        </div>
                    @endif

                    <div style="margin-top:18px;">
                        <div class="df-panel-header" style="padding:0 0 12px;border-bottom:0;">
                            <div>
                                <h3 class="df-panel-title">{{ $showTemplatePreview ? 'Template Preview' : 'Version Structure' }}</h3>
                                <div class="df-panel-meta">
                                    {{ $showTemplatePreview ? (($templatePreviewFormType === 'short_form' ? 'Short Form' : 'Full Form') . ' preview') : 'Sections and sub-sections in this version' }}
                                </div>
                            </div>
                            <div class="df-actions">
                                @if ($selectedTemplateVersion['can_add_questions'])
                                    <button type="button" class="df-button" wire:click="openTemplateSectionModal">Add Section</button>
                                @endif
                                <div class="df-mode-tabs">
                                    <button type="button" class="df-mode-tab {{ ! $showTemplatePreview ? 'is-active' : '' }}" wire:click="showTemplateVersionStructure">Structure</button>
                                    <button type="button" class="df-mode-tab {{ $showTemplatePreview && $templatePreviewFormType === 'full_form' ? 'is-active' : '' }}" wire:click="setTemplatePreviewFormType('full_form')">Preview Full</button>
                                    <button type="button" class="df-mode-tab {{ $showTemplatePreview && $templatePreviewFormType === 'short_form' ? 'is-active' : '' }}" wire:click="setTemplatePreviewFormType('short_form')">Preview Short</button>
                                </div>
                            </div>
                        </div>

                        @if ($showTemplatePreview)
                            <div style="display:flex;flex-direction:column;gap:12px;">
                                @forelse ($selectedTemplateVersion['preview_sections'] as $previewSection)
                                    <div class="df-preview-section">
                                        <div class="df-preview-section__head">{{ $previewSection['title'] }}</div>
                                        @foreach ($previewSection['questions'] as $question)
                                            <div class="df-preview-row">
                                                <div>
                                                    <div style="font-size:13px;font-weight:700;color:#0f172a;line-height:1.4;">{{ $question['prompt'] }}</div>
                                                    @if (filled($question['help_text']))
                                                        <div class="df-small">{{ $question['help_text'] }}</div>
                                                    @endif
                                                </div>
                                                <div style="min-height:36px;border:1px solid #cbd5e1;border-radius:8px;background:#ffffff;color:#64748b;font-size:12px;display:flex;align-items:center;padding:8px 10px;">
                                                    {{ $question['input_type'] }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @empty
                                    <div style="padding:18px;border:1px dashed #cbd5e1;border-radius:12px;text-align:center;color:#64748b;">
                                        No active questions are available for this preview.
                                    </div>
                                @endforelse
                            </div>
                        @else
                            <div class="df-table-wrap">
                                <table class="df-table">
                                    <thead>
                                        <tr>
                                            <th>Section</th>
                                            <th>Type</th>
                                            <th>Order</th>
                                            <th>Questions</th>
                                            <th>Status</th>
                                            <th style="text-align:right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($selectedTemplateVersion['sections'] as $section)
                                            @php
                                                $isExpanded = in_array($section['key'], $expandedTemplateSectionKeys, true);
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if ($section['child_count'] > 0)
                                                        <button type="button" class="df-collapse-button" wire:click="toggleTemplateSection('{{ $section['key'] }}')" title="{{ $isExpanded ? 'Collapse sub-sections' : 'Expand sub-sections' }}">
                                                            {{ $isExpanded ? '-' : '+' }}
                                                        </button>
                                                    @endif
                                                    <span class="df-name">{{ $section['title'] }}</span>
                                                    <div class="df-small">{{ $section['child_count'] > 0 ? $section['child_count'] . ' sub-sections' : 'Direct section' }}</div>
                                                </td>
                                                <td>Main Section</td>
                                                <td>#{{ $section['sort_order'] }}</td>
                                                <td>{{ $section['tree_active_count'] }}/{{ $section['tree_count'] }} active</td>
                                                <td><span class="df-badge df-badge--teal">Active</span></td>
                                                <td>
                                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap;">
                                                        @if ($this->canAddSubSectionToSection($section['key']))
                                                            <button type="button" class="df-button df-row-button" wire:click="openTemplateSectionModal('{{ $section['key'] }}')">Add Sub-section</button>
                                                        @endif
                                                        @if ($selectedTemplateVersion['can_add_questions'])
                                                            <a href="{{ $this->getCreateQuestionUrl($section['key']) }}" class="df-button df-row-button" style="text-decoration:none;">New Question</a>
                                                        @else
                                                            <span class="df-small" style="margin:0;">Read only</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>

                                            @if ($isExpanded)
                                                @foreach ($section['children'] as $child)
                                                    <tr class="df-child-row">
                                                        <td>
                                                            <div class="df-name" style="font-size:13px;">{{ $child['title'] }}</div>
                                                            <div class="df-small">Sub-section of {{ $section['title'] }}</div>
                                                        </td>
                                                        <td>Sub-section</td>
                                                        <td>#{{ $child['sort_order'] }}</td>
                                                        <td>{{ $child['active_count'] }}/{{ $child['count'] }} active</td>
                                                        <td><span class="df-badge df-badge--teal">Active</span></td>
                                                        <td>
                                                            <div style="display:flex;align-items:center;justify-content:flex-end;">
                                                                @if ($selectedTemplateVersion['can_add_questions'])
                                                                    <a href="{{ $this->getCreateQuestionUrl($child['key']) }}" class="df-button df-row-button" style="text-decoration:none;">New Question</a>
                                                                @else
                                                                    <span class="df-small" style="margin:0;">Read only</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="6" style="text-align:center;color:#64748b;padding:24px;">No sections found in this template version.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <details style="margin-top:16px;">
                                <summary class="df-button" style="width:max-content;cursor:pointer;">Question Library</summary>
                                <div class="df-table-wrap" style="margin-top:12px;">
                                    <table class="df-table">
                                        <thead>
                                            <tr>
                                                <th>Question</th>
                                                <th>Section</th>
                                                <th>Form</th>
                                                <th>Answer</th>
                                                <th>Order</th>
                                                <th>Status</th>
                                                <th style="text-align:right;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($selectedTemplateVersion['question_rows'] as $question)
                                                <tr>
                                                    <td><div class="df-name">{{ $question['prompt'] }}</div></td>
                                                    <td>{{ $question['section'] }}</td>
                                                    <td>{{ $question['form_type'] }}</td>
                                                    <td>{{ $question['answer_type'] }}</td>
                                                    <td>#{{ $question['sort_order'] }}</td>
                                                    <td>
                                                        <span class="df-badge {{ $question['is_active'] ? 'df-badge--teal' : '' }}">
                                                            {{ $question['is_active'] ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td style="text-align:right;">
                                                        @if ($question['edit_url'])
                                                            <a href="{{ $question['edit_url'] }}" class="df-button df-row-button" style="text-decoration:none;">Edit</a>
                                                        @else
                                                            <span class="df-small" style="margin:0;">Read only</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="7" style="padding:22px;text-align:center;color:#64748b;">No questions are stored in this version.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($showTemplateSectionModal)
            <div class="df-modal-backdrop" wire:key="template-section-modal">
                <div class="df-modal">
                    <div class="df-panel-header">
                        <div>
                            <div class="df-badge df-badge--teal">{{ filled($templateSectionParentKey) ? 'Add Sub-section' : 'Add Section' }}</div>
                            <h2 class="df-panel-title" style="margin-top:10px;">
                                {{ filled($templateSectionParentLabel) ? $templateSectionParentLabel : 'Master Template Draft' }}
                            </h2>
                            <div class="df-panel-meta">
                                {{ filled($templateSectionParentLabel) ? 'This sub-section will be added under the selected parent section.' : 'This section will be added to the open draft template.' }}
                            </div>
                        </div>
                        <button type="button" class="df-button" wire:click="closeTemplateSectionModal">Close</button>
                    </div>
                    <div class="df-modal-body">
                        <div class="df-form-grid">
                            <div class="df-field df-field--full">
                                <label for="new-template-section-label">{{ filled($templateSectionParentKey) ? 'Sub-section Name' : 'Section Name' }}</label>
                                <input id="new-template-section-label" class="df-input" style="width:100%;flex:auto;" type="text" wire:model.defer="newTemplateSectionData.label" placeholder="Example: Implant Coverage">
                                @error('newTemplateSectionData.label') <span class="df-small" style="color:#dc2626;">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="df-actions" style="margin-top:18px;">
                            <button type="button" class="df-button" wire:click="closeTemplateSectionModal">Cancel</button>
                            <button type="button" class="df-button" style="background:#0f766e;color:#ffffff;border-color:#0f766e;" wire:click="createSelectedTemplateSection" wire:loading.attr="disabled" wire:target="createSelectedTemplateSection">
                                <span wire:loading.remove wire:target="createSelectedTemplateSection">Save</span>
                                <span wire:loading wire:target="createSelectedTemplateSection">Saving...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($showSectionQuestionModal)
            <div class="df-modal-backdrop" wire:key="section-question-modal">
                <div class="df-modal">
                    <div class="df-panel-header">
                        <div>
                            <div class="df-badge df-badge--teal">Add Question</div>
                            <h2 class="df-panel-title" style="margin-top:10px;">{{ $questionSectionLabel }}</h2>
                            <div class="df-panel-meta">This question will be added to the open draft template.</div>
                        </div>
                        <button type="button" class="df-button" wire:click="closeSectionQuestionModal">Close</button>
                    </div>
                    <div class="df-modal-body">
                        <div class="df-form-grid">
                            <div class="df-field df-field--full">
                                <label for="new-question-prompt">Question</label>
                                <input id="new-question-prompt" class="df-input" style="width:100%;flex:auto;" type="text" wire:model.defer="newQuestionData.prompt" placeholder="Example: Is there any waiting period on this plan?">
                                @error('newQuestionData.prompt') <span class="df-small" style="color:#dc2626;">{{ $message }}</span> @enderror
                            </div>
                            <div class="df-field">
                                <label for="new-question-input">Answer Type</label>
                                <select id="new-question-input" class="df-select" wire:model.defer="newQuestionData.input_type">
                                    @foreach (\App\Models\VerificationFormQuestion::INPUT_TYPE_OPTIONS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="df-field">
                                <label for="new-question-form">Form Type</label>
                                <select id="new-question-form" class="df-select" wire:model.defer="newQuestionData.form_type">
                                    @foreach (\App\Models\VerificationFormQuestion::FORM_TYPE_OPTIONS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="df-field">
                                <label for="new-question-placeholder">Placeholder</label>
                                <input id="new-question-placeholder" class="df-input" style="width:100%;flex:auto;" type="text" wire:model.defer="newQuestionData.placeholder" placeholder="Optional answer placeholder">
                            </div>
                            <div class="df-field">
                                <label for="new-question-help">Instruction</label>
                                <input id="new-question-help" class="df-input" style="width:100%;flex:auto;" type="text" wire:model.defer="newQuestionData.help_text" placeholder="Optional helper text">
                            </div>
                            <div class="df-field df-field--full">
                                <label for="new-question-options">Dropdown Options</label>
                                <textarea id="new-question-options" class="df-input df-textarea" wire:model.defer="newQuestionData.select_options" placeholder="Only for Dropdown or Multi Response. Add one option per line."></textarea>
                            </div>
                        </div>
                        <div class="df-actions" style="margin-top:18px;">
                            <button type="button" class="df-button" wire:click="closeSectionQuestionModal">Cancel</button>
                            <button type="button" class="df-button" style="background:#0f766e;color:#ffffff;border-color:#0f766e;" wire:click="createSectionQuestion" wire:loading.attr="disabled" wire:target="createSectionQuestion">
                                <span wire:loading.remove wire:target="createSectionQuestion">Save Question</span>
                                <span wire:loading wire:target="createSectionQuestion">Saving...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
