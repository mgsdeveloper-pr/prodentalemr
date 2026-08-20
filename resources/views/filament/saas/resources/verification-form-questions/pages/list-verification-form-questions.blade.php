<x-filament-panels::page>
    @php
        $versionSummary = $this->getVersionSummary();
        $workspaceStats = $this->getTemplateWorkspaceStats();
        $templateVersionHistory = collect($this->getTemplateVersionHistory());
        $publishedCount = $templateVersionHistory->where('status', 'Published')->count();
        $draftCount = $templateVersionHistory->where('status', 'Draft')->count();
        $archivedCount = max(0, $templateVersionHistory->count() - $publishedCount - $draftCount);
        $hasDraft = (bool) ($versionSummary['has_draft'] ?? false);
        $selectedTemplateVersion = $this->getSelectedTemplateVersionDetail();
    @endphp

    <style>
        .df-page {
            max-width: 1600px;
            margin: 0 auto;
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

        .df-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .df-card,
        .df-panel {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
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

        .df-filter {
            margin-top: 24px;
            padding: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
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
            margin-top: 24px;
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

        .df-table-wrap {
            overflow-x: auto;
        }

        .df-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
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
            .df-grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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

            .df-grid-4,
            .df-detail-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .df-preview-row {
                grid-template-columns: minmax(0, 1fr);
            }

            .df-form-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="df-page">
        <section class="df-header">
            <div>
                <h1 class="df-title">Master Template</h1>
                <p class="df-subtitle">Create, manage, preview, and publish the platform Master Template from one workspace.</p>
            </div>

            <div class="df-actions">
                @if ($this->canManageTemplateVersions())
                    <button type="button" class="df-button" wire:click="openCreateDraftModal" wire:loading.attr="disabled" wire:target="openCreateDraftModal">
                        Create Draft Template
                    </button>
                    @if ($selectedTemplateVersion && ($selectedTemplateVersion['is_draft'] ?? false))
                        <button type="button" class="df-button" style="background:#0f766e;color:#ffffff;border-color:#0f766e;" wire:click="openPublishDraftModal" wire:loading.attr="disabled" wire:target="openPublishDraftModal">
                            Publish This Draft
                        </button>
                    @endif
                @endif
            </div>
        </section>

        <section class="df-grid-4" aria-label="Template version summary">
            <div class="df-card">
                <div class="df-label">Total Versions</div>
                <div class="df-value">{{ $templateVersionHistory->count() }}</div>
            </div>
            <div class="df-card">
                <div class="df-label">Published</div>
                <div class="df-value" style="color:#059669;">{{ $publishedCount }}</div>
            </div>
            <div class="df-card">
                <div class="df-label">Drafts</div>
                <div class="df-value" style="color:#d97706;">{{ $draftCount }}</div>
            </div>
            <div class="df-card">
                <div class="df-label">Archived</div>
                <div class="df-value" style="color:#64748b;">{{ $archivedCount }}</div>
            </div>
        </section>

        <section class="df-panel df-filter" aria-label="Template filters">
            <input class="df-input" type="text" value="" placeholder="Search sections or questions from the table below..." disabled>
            <select class="df-select" disabled>
                <option>{{ $hasDraft ? 'Draft Active' : 'Published Only' }}</option>
            </select>
            <select class="df-select" disabled>
                <option>Master Scope</option>
            </select>
            <select class="df-select" disabled>
                <option>{{ $versionSummary['working_version'] }}</option>
            </select>
            <button type="button" class="df-button" disabled>Filters</button>
        </section>

        <section class="df-panel">
            <div class="df-panel-header">
                <div>
                    <h2 class="df-panel-title">Template List</h2>
                    <div class="df-panel-meta">{{ $templateVersionHistory->count() }} master template records found</div>
                </div>
                <div class="df-actions">
                    <button type="button" class="df-button" disabled>Export</button>
                    <button type="button" class="df-button" disabled>Columns</button>
                </div>
            </div>

            <div class="df-table-wrap">
                <table class="df-table">
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Scope</th>
                            <th>Mode</th>
                            <th>Clinic Visibility</th>
                            <th>Version</th>
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
                                'draft' => 'Working Draft',
                                'previous' => 'Template History',
                            ];
                        @endphp
                        @forelse ($templateVersionHistory as $version)
                            @if ($currentTemplateGroup !== $version['row_group'])
                                @php $currentTemplateGroup = $version['row_group']; @endphp
                                <tr>
                                    <td colspan="8" style="padding:10px 16px;background:#fbfdff;color:#64748b;font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;">
                                        {{ $templateGroupLabels[$version['row_group']] ?? 'Templates' }}
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td>
                                    <div class="df-name">{{ $version['name'] }}</div>
                                    <div class="df-small">{{ $version['description'] }}</div>
                                </td>
                                <td>
                                    <span class="df-badge df-badge--blue">Master</span>
                                </td>
                                <td>{{ $version['form_type'] }}</td>
                                <td>{{ $version['clinic_visibility'] }}</td>
                                <td>{{ $version['version'] }}</td>
                                <td>
                                    <span class="df-badge {{ $version['status'] === 'Draft' ? 'df-badge--amber' : ($version['is_active'] ? 'df-badge--teal' : '') }}">
                                        <span class="df-dot"></span>
                                        {{ $version['is_working_draft'] ? 'Working Draft' : $version['status'] }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $version['updated_at'] }}</div>
                                    <div class="df-small">Published: {{ $version['published_at'] ?: '-' }}</div>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                        <button type="button" class="df-button" wire:click="selectTemplateVersion({{ $version['id'] }})" title="Open Version">
                                            {{ $version['is_draft'] ? 'Open Draft' : 'View' }}
                                        </button>
                                        <button type="button" class="df-button" wire:click="showTemplateVersionPreview({{ $version['id'] }})" title="Preview">
                                            Preview
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;color:#64748b;padding:24px;">No templates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($selectedTemplateVersion)
            <section class="df-panel">
                <div class="df-panel-header">
                    <div>
                        <h2 class="df-panel-title">{{ $selectedTemplateVersion['name'] }}</h2>
                        <div class="df-panel-meta">
                            {{ $selectedTemplateVersion['version'] }} · {{ $selectedTemplateVersion['status'] }} · {{ $selectedTemplateVersion['scope'] }}
                        </div>
                    </div>
                    <div class="df-actions">
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
                            ['label' => 'Clinic Visibility', 'value' => $selectedTemplateVersion['clinic_visibility']],
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
                                <div class="df-table-slot" style="padding:16px 0 0;">
                                    {{ $this->table }}
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($showCreateDraftModal)
            <div class="df-modal-backdrop" wire:key="create-draft-modal">
                <div class="df-modal">
                    <div class="df-panel-header">
                        <div>
                            <h2 class="df-panel-title">Create Template Draft</h2>
                            <div class="df-panel-meta">Name the draft, choose the form type, and decide how it should start.</div>
                        </div>
                        <button type="button" class="df-button" wire:click="closeCreateDraftModal">Close</button>
                    </div>
                    <div class="df-modal-body">
                        <div class="df-form-grid">
                            <div class="df-field df-field--full">
                                <label for="new-draft-name">Template name</label>
                                <input id="new-draft-name" class="df-input" type="text" wire:model.defer="newDraftData.template_name">
                                @error('newDraftData.template_name') <div class="df-small" style="color:#dc2626;">{{ $message }}</div> @enderror
                            </div>

                            <div class="df-field">
                                <label for="new-draft-form-type">Type of form</label>
                                <select id="new-draft-form-type" class="df-select" wire:model.defer="newDraftData.form_type">
                                    @foreach (\App\Models\VerificationTemplateVersion::FORM_TYPE_OPTIONS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('newDraftData.form_type') <div class="df-small" style="color:#dc2626;">{{ $message }}</div> @enderror
                            </div>

                            <div class="df-field">
                                <label for="new-draft-visibility">Clinic visibility</label>
                                <select id="new-draft-visibility" class="df-select" wire:model.defer="newDraftData.clinic_visibility">
                                    @foreach (\App\Models\VerificationTemplateVersion::CLINIC_VISIBILITY_OPTIONS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('newDraftData.clinic_visibility') <div class="df-small" style="color:#dc2626;">{{ $message }}</div> @enderror
                            </div>

                            <div class="df-field df-field--full">
                                <label for="new-draft-starting-point">Starting point</label>
                                <select id="new-draft-starting-point" class="df-select" wire:model.live="newDraftData.starting_point">
                                    <option value="current_master">Start from current Master Template</option>
                                    <option value="fresh">Start fresh</option>
                                    <option value="specific_version">Replicate from a specific version</option>
                                </select>
                                @error('newDraftData.starting_point') <div class="df-small" style="color:#dc2626;">{{ $message }}</div> @enderror
                                @if (($newDraftData['starting_point'] ?? null) === 'fresh')
                                    <div class="df-small" style="color:#b45309;">Fresh drafts start empty except for the standard section structure.</div>
                                @endif
                            </div>

                            @if (($newDraftData['starting_point'] ?? null) === 'specific_version')
                                <div class="df-field df-field--full">
                                    <label for="new-draft-source-version">Template version</label>
                                    <select id="new-draft-source-version" class="df-select" wire:model.defer="newDraftData.source_version_id">
                                        <option value="">Select a version</option>
                                        @foreach ($this->draftSourceVersionOptions() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('newDraftData.source_version_id') <div class="df-small" style="color:#dc2626;">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="df-panel-header" style="border-top:1px solid #e2e8f0;border-bottom:0;">
                        <div class="df-small">This creates a draft only. It will not affect active clinic forms until it is published.</div>
                        <div class="df-actions">
                            <button type="button" class="df-button" wire:click="closeCreateDraftModal">Cancel</button>
                            <button type="button" class="df-button" style="background:#0f766e;color:#ffffff;border-color:#0f766e;" wire:click="submitCreateDraftVersion" wire:loading.attr="disabled" wire:target="submitCreateDraftVersion">
                                <span wire:loading.remove wire:target="submitCreateDraftVersion">Create Draft</span>
                                <span wire:loading wire:target="submitCreateDraftVersion">Creating...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($showPublishDraftModal)
            <div class="df-modal-backdrop" wire:key="publish-draft-modal">
                <div class="df-modal">
                    <div class="df-panel-header">
                        <div>
                            <h2 class="df-panel-title">Publish Template Draft</h2>
                            <div class="df-panel-meta">Give this published version a clear name and describe what changed.</div>
                        </div>
                        <button type="button" class="df-button" wire:click="closePublishDraftModal">Close</button>
                    </div>
                    <div class="df-modal-body">
                        <div class="df-form-grid">
                            <div class="df-field df-field--full">
                                <label for="publish-draft-name">Version name</label>
                                <input id="publish-draft-name" class="df-input" type="text" wire:model.defer="publishDraftData.version_name">
                                @error('publishDraftData.version_name') <div class="df-small" style="color:#dc2626;">{{ $message }}</div> @enderror
                            </div>
                            <div class="df-field df-field--full">
                                <label for="publish-draft-description">Change description</label>
                                <textarea id="publish-draft-description" class="df-input df-textarea" wire:model.defer="publishDraftData.change_description"></textarea>
                                @error('publishDraftData.change_description') <div class="df-small" style="color:#dc2626;">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="df-panel-header" style="border-top:1px solid #e2e8f0;border-bottom:0;">
                        <div class="df-small">Existing verification requests keep their saved template until refreshed.</div>
                        <div class="df-actions">
                            <button type="button" class="df-button" wire:click="closePublishDraftModal">Cancel</button>
                            <button type="button" class="df-button" style="background:#0f766e;color:#ffffff;border-color:#0f766e;" wire:click="submitPublishDraftVersion" wire:loading.attr="disabled" wire:target="submitPublishDraftVersion">
                                <span wire:loading.remove wire:target="submitPublishDraftVersion">Publish Draft</span>
                                <span wire:loading wire:target="submitPublishDraftVersion">Publishing...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
</x-filament-panels::page>
