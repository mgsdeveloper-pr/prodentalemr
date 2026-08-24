<x-filament-panels::page>
    @php
        $clinic = $this->getSelectedClinic();
        $currentMode = \App\Support\VerificationResultPdf::normalizeOutputMode($this->data['verification_pdf_output_mode'] ?? 'standard');
        $currentModeLabel = $this->getPdfOutputModeOptions()[$currentMode] ?? 'Standard';
        $isCustomOutputMode = \App\Support\VerificationResultPdf::isCustomOutputMode($currentMode);
        $selectedSections = is_array($this->data['verification_pdf_output_sections'] ?? null) ? $this->data['verification_pdf_output_sections'] : [];
        $availableQuestionSections = $this->getAvailableQuestionSectionsForSelection();
        $previewPdfUrl = $this->getPreviewPdfUrl();
        $summaryRows = $this->getSummaryRows();
        $templateUrl = $this->getManageQuestionsUrl();
        $clinicTemplateRows = $this->getClinicTemplateVersionRows();
        $canManageClinicTemplate = $this->canManageSelectedClinicTemplate();
        $hasWorkingClinicDraft = collect($clinicTemplateRows)->contains(fn (array $row): bool => $row['is_working_draft']);
        $activeSettingsSection = $this->activeSettingsSection;
        $settingsItems = [
            ['key' => 'template-selection', 'label' => 'Template Selection', 'description' => 'Choose the verification form this clinic will use.', 'active' => $activeSettingsSection === 'template-selection', 'icon' => '01', 'url' => null],
            ['key' => 'template-management', 'label' => 'Template Management', 'description' => 'Manage templates, sections, questions, and preview.', 'active' => $activeSettingsSection === 'template-management', 'icon' => '02', 'url' => null],
            ['key' => 'pdf-settings', 'label' => 'PDF Settings', 'description' => 'Select the user PDF output and preset profile.', 'active' => $activeSettingsSection === 'pdf-settings', 'icon' => '03', 'url' => null],
        ];
    @endphp

    <style>
        .vs-page {
            --vs-teal: #0f8f86;
            --vs-teal-dark: #08756f;
            --vs-navy: #111936;
            --vs-text: #25345d;
            --vs-muted: #667697;
            --vs-border: #dfe7f3;
            --vs-soft: #f6f9fd;
            --vs-teal-soft: #eafaf8;
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0 0 34px;
            color: var(--vs-text);
        }

        .vs-header,
        .vs-strip,
        .vs-card {
            border: 1px solid var(--vs-border);
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.04);
        }

        .vs-header {
            border: 0;
            border-bottom: 1px solid var(--vs-border);
            box-shadow: none;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin: -24px -24px 20px;
            padding: 20px 28px;
        }

        .vs-title {
            margin: 0;
            color: var(--vs-navy);
            font-size: 28px;
            line-height: 1.15;
            font-weight: 900;
            letter-spacing: 0;
        }

        .vs-subtitle {
            margin: 8px 0 0;
            color: var(--vs-text);
            font-size: 14px;
            line-height: 1.5;
        }

        .vs-breadcrumb {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 10px;
            color: var(--vs-muted);
            font-size: 12px;
        }

        .vs-breadcrumb svg {
            width: 14px;
            height: 14px;
        }

        .vs-breadcrumb strong {
            color: var(--vs-navy);
        }

        .vs-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .vs-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 16px;
            border: 1px solid var(--vs-border);
            border-radius: 9px;
            background: #ffffff;
            color: var(--vs-navy);
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .vs-button--primary {
            border-color: var(--vs-teal);
            background: linear-gradient(180deg, #10a399 0%, var(--vs-teal-dark) 100%);
            color: #ffffff;
            box-shadow: 0 10px 22px rgba(15, 143, 134, 0.2);
        }

        .vs-save-note {
            margin-top: 8px;
            color: var(--vs-muted);
            font-size: 12px;
            text-align: right;
        }

        .vs-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0;
            margin-bottom: 20px;
            padding: 0;
        }

        .vs-strip-item {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
            padding: 14px 18px;
            border-right: 1px solid var(--vs-border);
        }

        .vs-strip-item:first-child {
            padding-left: 18px;
        }

        .vs-strip-item:last-child {
            border-right: 0;
            padding-right: 18px;
        }

        .vs-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background: var(--vs-teal-soft);
            color: var(--vs-teal-dark);
            font-size: 13px;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .vs-icon--purple { background: #f2eafe; color: #6d4aff; }
        .vs-icon--orange { background: #fff2df; color: #f97316; }
        .vs-icon--blue { background: #edf4ff; color: #2563eb; }

        .vs-label {
            color: var(--vs-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .vs-value {
            margin-top: 5px;
            color: var(--vs-navy);
            font-size: 14px;
            line-height: 1.35;
            font-weight: 900;
        }

        .vs-small {
            margin-top: 5px;
            color: var(--vs-muted);
            font-size: 12px;
            line-height: 1.4;
        }

        .vs-workspace {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr) 300px;
            gap: 18px;
            align-items: start;
        }

        .vs-workspace--wide {
            grid-template-columns: 280px minmax(0, 1fr);
        }

        .vs-workspace--pdf {
            grid-template-columns: 280px minmax(0, 1fr) 300px;
        }

        .vs-card {
            overflow: hidden;
        }

        .vs-card-header {
            padding: 20px 22px 14px;
        }

        .vs-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 22px 0 14px;
        }

        .vs-section-intro {
            margin-top: 0;
            padding: 14px 16px;
            border: 1px solid var(--vs-border);
            border-radius: 12px;
            background: #f8fafc;
            color: var(--vs-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .vs-eyebrow {
            color: var(--vs-navy);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .vs-card-title {
            margin: 0;
            color: var(--vs-navy);
            font-size: 17px;
            line-height: 1.25;
            font-weight: 900;
        }

        .vs-card-subtitle {
            margin: 8px 0 0;
            color: var(--vs-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .vs-settings-nav {
            padding: 14px;
        }

        .vs-settings-item {
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr) 18px;
            gap: 12px;
            align-items: center;
            padding: 13px 12px;
            border-bottom: 1px solid #edf2f7;
            color: var(--vs-text);
        }

        a.vs-settings-item {
            text-decoration: none;
            cursor: pointer;
        }

        button.vs-settings-item {
            width: 100%;
            border-left: 0;
            border-top: 0;
            border-right: 0;
            text-align: left;
            background: transparent;
            cursor: pointer;
        }

        a.vs-settings-item:hover {
            background: var(--vs-soft);
        }

        button.vs-settings-item:hover {
            background: var(--vs-soft);
        }

        .vs-settings-item:last-child {
            border-bottom: 0;
        }

        .vs-settings-item > span:last-child {
            font-size: 0;
        }

        .vs-settings-item > span:last-child::after {
            content: ">";
            font-size: 13px;
            font-weight: 900;
        }

        .vs-settings-item.is-active {
            border: 1px solid #bde8e4;
            border-left: 3px solid var(--vs-teal);
            border-radius: 10px;
            background: linear-gradient(90deg, var(--vs-teal-soft) 0%, #ffffff 100%);
            box-shadow: 0 10px 26px rgba(15, 143, 134, 0.06);
        }

        .vs-settings-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 9px;
            color: #29436d;
            font-size: 11px;
            font-weight: 900;
        }

        .vs-settings-item.is-active .vs-settings-icon {
            color: var(--vs-teal-dark);
        }

        .vs-form-card {
            padding: 0;
        }

        .vs-form-body {
            padding: 8px 22px 22px;
        }

        .vs-form-body--active {
            min-height: 460px;
        }

        .vs-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 24px;
        }

        .vs-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 18px 24px;
        }

        .vs-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .vs-field label {
            color: var(--vs-navy);
            font-size: 12px;
            font-weight: 800;
        }

        .vs-required {
            color: #dc2626;
        }

        .vs-input,
        .vs-select,
        .vs-textarea {
            width: 100%;
            min-height: 42px;
            border: 1px solid #d7e0ec;
            border-radius: 9px;
            background: #ffffff;
            padding: 10px 12px;
            color: var(--vs-navy);
            font-size: 13px;
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02);
        }

        .vs-textarea {
            min-height: 72px;
            resize: vertical;
        }

        .vs-segment {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #d7e0ec;
            border-radius: 9px;
            overflow: hidden;
            min-height: 42px;
            background: #ffffff;
        }

        .vs-segment button {
            border: 0;
            background: #ffffff;
            color: var(--vs-text);
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .vs-segment button.is-active {
            border: 1px solid #8ed8d1;
            background: var(--vs-teal-soft);
            color: var(--vs-teal-dark);
        }

        .vs-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 20px;
            padding: 14px 16px;
            border: 1px solid var(--vs-border);
            border-radius: 10px;
            background: #fbfdff;
        }

        .vs-toggle {
            width: 42px;
            height: 24px;
            accent-color: var(--vs-teal);
        }

        .vs-included {
            margin-top: 24px;
            border: 1px solid var(--vs-border);
            border-radius: 12px;
            overflow: hidden;
        }

        .vs-included-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--vs-border);
            background: #fbfdff;
        }

        .vs-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px 24px;
            padding: 18px;
        }

        .vs-check {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--vs-text);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
        }

        .vs-check input {
            width: 16px;
            height: 16px;
            accent-color: var(--vs-teal);
        }

        .vs-question-group {
            margin-top: 18px;
            display: grid;
            gap: 12px;
        }

        .vs-question-card {
            border: 1px solid #e1e8f2;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
        }

        .vs-question-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            background: #fbfdff;
            border-bottom: 1px solid #edf2f7;
        }

        .vs-question-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 14px;
            padding: 14px;
        }

        .vs-preview-paper {
            width: 186px;
            min-height: 250px;
            margin: 0 auto;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            background: #ffffff;
            padding: 18px 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .vs-preview-logo {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--vs-navy);
            font-size: 11px;
            font-weight: 900;
        }

        .vs-preview-mark {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            background: var(--vs-teal);
        }

        .vs-preview-line {
            height: 7px;
            border-radius: 999px;
            background: #e6edf6;
            margin-top: 9px;
        }

        .vs-summary {
            display: grid;
            gap: 12px;
            padding: 18px 20px 20px;
        }

        .vs-summary-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            color: var(--vs-muted);
            font-size: 12px;
        }

        .vs-summary-row strong {
            color: var(--vs-navy);
            font-size: 12px;
            text-align: right;
        }

        .vs-table-wrap {
            margin-top: 16px;
            border: 1px solid var(--vs-border);
            border-radius: 12px;
            overflow-x: auto;
            background: #ffffff;
        }

        .vs-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }

        .vs-table th {
            padding: 13px 16px;
            background: #f8fafc;
            border-bottom: 1px solid var(--vs-border);
            color: var(--vs-muted);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-align: left;
            text-transform: uppercase;
        }

        .vs-table td {
            padding: 15px 16px;
            border-bottom: 1px solid #edf2f7;
            color: var(--vs-text);
            font-size: 13px;
            vertical-align: top;
        }

        .vs-table tbody tr:hover td {
            background: #fbfdff;
        }

        .vs-table tr:last-child td {
            border-bottom: 0;
        }

        .vs-table-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .vs-table-actions .vs-button {
            min-height: 36px;
            padding: 8px 12px;
            font-size: 12px;
        }

        .vs-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .vs-pill--draft {
            border-color: #fed7aa;
            background: #fff7ed;
            color: #c2410c;
        }

        .vs-pill--published {
            border-color: #99f6e4;
            background: #f0fdfa;
            color: var(--vs-teal-dark);
        }

        .vs-pill--muted {
            border-color: #e2e8f0;
            background: #f8fafc;
            color: var(--vs-muted);
        }

        .vs-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: grid;
            place-items: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
        }

        .vs-modal {
            width: min(680px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            border: 1px solid var(--vs-border);
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 26px 70px rgba(15, 23, 42, 0.24);
        }

        .vs-modal-header,
        .vs-modal-footer {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
        }

        .vs-modal-header {
            border-bottom: 1px solid var(--vs-border);
        }

        .vs-modal-footer {
            align-items: center;
            border-top: 1px solid var(--vs-border);
            background: #fbfdff;
        }

        .vs-modal-title {
            margin: 0;
            color: var(--vs-navy);
            font-size: 18px;
            font-weight: 900;
            line-height: 1.2;
        }

        .vs-modal-body {
            display: grid;
            gap: 18px;
            padding: 20px;
        }

        .vs-error {
            color: #dc2626;
            font-size: 12px;
            font-weight: 700;
        }

        .vs-alert {
            padding: 12px 14px;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
            line-height: 1.5;
        }

        @media (max-width: 1280px) {
            .vs-workspace {
                grid-template-columns: 240px minmax(0, 1fr);
            }

            .vs-right {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }
        }

        @media (max-width: 900px) {
            .vs-header,
            .vs-strip {
                grid-template-columns: minmax(0, 1fr);
            }

            .vs-header {
                flex-direction: column;
            }

            .vs-actions {
                justify-content: flex-start;
            }

            .vs-strip-item {
                padding: 14px 0;
                border-right: 0;
                border-bottom: 1px solid var(--vs-border);
            }

            .vs-strip-item:last-child {
                border-bottom: 0;
            }

            .vs-workspace,
            .vs-right,
            .vs-grid,
            .vs-grid-3,
            .vs-checkbox-grid,
            .vs-question-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="vs-page">
        <section class="vs-header">
            <div>
                <h1 class="vs-title">Verification Settings</h1>
                <p class="vs-subtitle">Choose the clinic verification form, manage template structure, and control PDF output from one place.</p>
                <nav class="vs-breadcrumb" aria-label="Breadcrumb">
                    <span>Clinic</span>
                    <x-heroicon-o-chevron-right />
                    <strong>Verification Settings</strong>
                </nav>
            </div>
            @if (in_array($activeSettingsSection, ['template-selection', 'pdf-settings'], true))
                <div>
                    <div class="vs-actions">
                        <button type="button" wire:click.prevent="save" class="vs-button vs-button--primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Save Settings</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                    <div class="vs-save-note">Changes save when you click Save Settings</div>
                </div>
            @endif
        </section>

        <section class="vs-strip">
            <div class="vs-strip-item">
                <span class="vs-icon">CL</span>
                <span>
                    <div class="vs-label">Clinic</div>
                    <div class="vs-value">{{ $clinic?->clinic_name ?: 'Select clinic scope' }}</div>
                    <div class="vs-small">{{ $clinic?->organization?->name ?: 'Clinic workspace' }}</div>
                </span>
            </div>
            <div class="vs-strip-item">
                <span class="vs-icon vs-icon--purple">PDF</span>
                <span>
                    <div class="vs-label">Output Format</div>
                    <div class="vs-value">{{ $currentModeLabel }}</div>
                    <div class="vs-small">PDF Output</div>
                </span>
            </div>
            <div class="vs-strip-item">
                <span class="vs-icon vs-icon--orange">PR</span>
                <span>
                    <div class="vs-label">Preset Profile</div>
                    <div class="vs-value">{{ $this->data['verification_pdf_preset_name'] ?? 'Full Verification Report' }}</div>
                    <div class="vs-small">{{ ($this->data['verification_pdf_preset_is_default'] ?? true) ? 'Default Profile' : 'Custom Profile' }}</div>
                </span>
            </div>
            <div class="vs-strip-item">
                <span class="vs-icon vs-icon--blue">UP</span>
                <span>
                    <div class="vs-label">Last Updated</div>
                    <div class="vs-value">{{ optional($clinic?->updated_at)->format('M d, Y') ?: '-' }}</div>
                    <div class="vs-small">By {{ auth()->user()?->name ?? 'Current user' }}</div>
                </span>
            </div>
        </section>

        <section class="vs-workspace {{ $activeSettingsSection === 'pdf-settings' ? 'vs-workspace--pdf' : 'vs-workspace--wide' }}">
            <aside class="vs-card">
                <div class="vs-card-header">
                    <div class="vs-eyebrow">Settings</div>
                </div>
                <nav class="vs-settings-nav">
                    @foreach ($settingsItems as $item)
                        @if (filled($item['url']))
                            <a href="{{ $item['url'] }}" wire:navigate class="vs-settings-item">
                        @else
                            <button type="button" wire:click="showSettingsSection('{{ $item['key'] }}')" class="vs-settings-item{{ $item['active'] ? ' is-active' : '' }}">
                        @endif
                            <span class="vs-settings-icon">{{ $item['icon'] }}</span>
                            <span>
                                <div style="font-size:13px;font-weight:900;color:var(--vs-navy);">{{ $item['label'] }}</div>
                                <div class="vs-small">{{ $item['description'] }}</div>
                            </span>
                            <span style="color:{{ $item['active'] || filled($item['url'] ?? null) ? 'var(--vs-teal-dark)' : '#94a3b8' }};"></span>
                        @if (filled($item['url']))
                            </a>
                        @else
                            </button>
                        @endif
                    @endforeach
                </nav>
            </aside>

            <main class="vs-card vs-form-card">
                <div id="template-selection" class="vs-card-header" style="{{ $activeSettingsSection === 'template-selection' ? '' : 'display:none;' }}">
                    <h2 class="vs-card-title">Template Selection</h2>
                    <p class="vs-card-subtitle">Choose the verification form this clinic will use, then manage its template structure from the same workflow.</p>
                </div>

                <div class="vs-form-body vs-form-body--active">
                    <div class="vs-grid" style="{{ $activeSettingsSection === 'template-selection' ? '' : 'display:none;' }}">
                        <div class="vs-field">
                            <label>Clinic Scope</label>
                            <select class="vs-select" disabled>
                                <option>{{ $clinic?->clinic_name ? $clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '') : 'Select clinic scope' }}</option>
                            </select>
                        </div>

                        <div class="vs-field">
                            <label>Verification Form</label>
                            <select class="vs-select" wire:model.live="data.verification_template_version_id">
                                @forelse ($this->getClinicTemplateOptions() as $templateVersionId => $templateName)
                                    <option value="{{ $templateVersionId }}">{{ $templateName }}</option>
                                @empty
                                    <option value="">No published clinic template found</option>
                                @endforelse
                            </select>
                            <div class="vs-small">
                                Save Settings after changing this selection. Only published clinic templates can be selected here.
                            </div>
                        </div>
                    </div>

                    @if ($activeSettingsSection === 'template-selection')
                        <div class="vs-section-intro" style="margin-top:18px;">
                            Template Selection controls which published clinic template is used for new verification requests. Draft templates stay in Template Management until published.
                        </div>
                    @endif

                    <div style="{{ $activeSettingsSection === 'template-management' ? '' : 'display:none;' }}">
                    <div id="template-management" class="vs-section-head">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;width:100%;">
                            <span>
                                <h2 class="vs-card-title">Template Management</h2>
                                <p class="vs-card-subtitle">Create, update, organize, re-order, and preview the clinic verification template.</p>
                            </span>
                            @if ($canManageClinicTemplate)
                                <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                                    @if ($hasWorkingClinicDraft)
                                        <a href="{{ $templateUrl }}?draft=1" wire:navigate class="vs-button">Open Working Draft</a>
                                    @endif
                                    <button type="button" wire:click.prevent="createClinicTemplateDraft" wire:loading.attr="disabled" wire:target="createClinicTemplateDraft" class="vs-button">
                                        <span wire:loading.remove wire:target="createClinicTemplateDraft">Create Draft Template</span>
                                        <span wire:loading wire:target="createClinicTemplateDraft">Preparing...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="vs-section-intro">
                        <span>
                            <div style="color:var(--vs-navy);font-size:13px;font-weight:900;">Clinic template workspace</div>
                            <div class="vs-small">Only one clinic template can be active. Active templates can be edited; older templates stay as Not Active and can be archived only when no requests use them.</div>
                        </span>
                    </div>

                    <div class="vs-table-wrap">
                        <table class="vs-table">
                            <thead>
                                <tr>
                                    <th>Template</th>
                                    <th>Template ID</th>
                                    <th>Status</th>
                                    <th>Form Type</th>
                                    <th>Structure</th>
                                    <th>Updated</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $currentTemplateGroup = null;
                                    $templateGroupLabels = [
                                        'active' => 'Active Template',
                                        'draft' => 'Working Draft',
                                        'previous' => 'Template History',
                                    ];
                                @endphp
                                @forelse ($clinicTemplateRows as $row)
                                    @if ($currentTemplateGroup !== $row['row_group'])
                                        @php $currentTemplateGroup = $row['row_group']; @endphp
                                        <tr>
                                            <td colspan="7" style="padding:10px 16px;background:#fbfdff;color:var(--vs-muted);font-size:11px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;">
                                                {{ $templateGroupLabels[$row['row_group']] ?? 'Templates' }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td>
                                            <div style="color:var(--vs-navy);font-size:14px;font-weight:900;">{{ $row['name'] }}</div>
                                            <div class="vs-small">{{ $row['visibility'] }}</div>
                                        </td>
                                        <td>
                                            <span class="vs-pill vs-pill--muted">{{ $row['template_id'] }}</span>
                                        </td>
                                        <td>
                                            <span class="vs-pill {{ $row['is_draft'] ? 'vs-pill--draft' : ($row['is_active'] ? 'vs-pill--published' : 'vs-pill--muted') }}">{{ $row['status'] }}</span>
                                            @if ($row['is_working_draft'])
                                                <span class="vs-pill vs-pill--draft" style="margin-left:6px;">Working Draft</span>
                                            @elseif ($row['is_active'])
                                                <span class="vs-pill vs-pill--published" style="margin-left:6px;">Active</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['form_type'] }}</td>
                                        <td>
                                            <strong style="color:var(--vs-navy);">{{ $row['sections'] }}</strong> main
                                            <span style="color:var(--vs-muted);"> / </span>
                                            <strong style="color:var(--vs-navy);">{{ $row['sub_sections'] }}</strong> sub-sections
                                            <span style="color:var(--vs-muted);"> / </span>
                                            <strong style="color:var(--vs-navy);">{{ $row['active_questions'] }}/{{ $row['questions'] }}</strong> active questions
                                            @if ($row['used_request_count'] > 0)
                                                <div class="vs-small">{{ $row['used_request_count'] }} request(s) using this template</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $row['updated_at'] }}</div>
                                            <div class="vs-small">Published: {{ $row['published_at'] }}</div>
                                        </td>
                                        <td>
                                            <div class="vs-table-actions">
                                                <a href="{{ $templateUrl }}{{ $row['is_draft'] ? '?draft=1' : '' }}" wire:navigate class="vs-button">
                                                    {{ $row['is_draft'] ? 'Open Builder' : 'View Builder' }}
                                                </a>
                                                @if ($row['is_draft'] && $row['can_edit'])
                                                    <button type="button" wire:click.prevent="publishClinicTemplateDraft" wire:confirm="Publish this clinic template draft?" wire:loading.attr="disabled" wire:target="publishClinicTemplateDraft" class="vs-button vs-button--primary">
                                                        <span wire:loading.remove wire:target="publishClinicTemplateDraft">Publish</span>
                                                        <span wire:loading wire:target="publishClinicTemplateDraft">Publishing...</span>
                                                    </button>
                                                @endif
                                                @if ($row['can_archive'])
                                                    <button
                                                        type="button"
                                                        wire:click.prevent="archiveClinicTemplateVersion({{ $row['id'] }})"
                                                        wire:confirm="Archive this clinic template? It will be removed from this active list."
                                                        wire:loading.attr="disabled"
                                                        wire:target="archiveClinicTemplateVersion"
                                                        class="vs-button"
                                                        style="border-color:#fecdd3;color:#be123c;background:#fff1f2;"
                                                    >
                                                        Archive
                                                    </button>
                                                @elseif ($row['archive_block_reason'])
                                                    <span class="vs-small" style="max-width:190px;text-align:right;">{{ $row['archive_block_reason'] }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div style="padding:22px;text-align:center;color:var(--vs-muted);">No clinic templates found for the selected clinic.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    </div>

                    <div style="{{ $activeSettingsSection === 'pdf-settings' ? '' : 'display:none;' }}">
                    <div id="pdf-settings" class="vs-section-head">
                        <span>
                            <h2 class="vs-card-title">PDF Settings</h2>
                            <p class="vs-card-subtitle">Choose the user PDF output and preset profile for this clinic.</p>
                        </span>
                        <button type="button" wire:click.prevent="createNewPreset" wire:loading.attr="disabled" wire:target="createNewPreset" class="vs-button">
                            <span wire:loading.remove wire:target="createNewPreset">Create New Preset</span>
                            <span wire:loading wire:target="createNewPreset">Preparing...</span>
                        </button>
                    </div>

                    <div class="vs-grid">
                        <div class="vs-field">
                            <label>PDF Preset Profile</label>
                            <select
                                class="vs-select"
                                wire:model.live="data.verification_pdf_preset_id"
                                wire:change="loadPreset($event.target.value)"
                            >
                                @foreach ($this->getPdfPresetOptions() as $presetId => $presetName)
                                    <option value="{{ $presetId }}">{{ $presetName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="vs-field">
                            <label>Preset Name <span class="vs-required">*</span></label>
                            <input class="vs-input" type="text" wire:model.defer="data.verification_pdf_preset_name">
                        </div>

                        <div class="vs-field">
                            <label>Preset Description</label>
                            <textarea class="vs-textarea" wire:model.defer="data.verification_pdf_preset_description" placeholder="Complete verification report with all standard output."></textarea>
                        </div>
                    </div>

                    <div class="vs-grid-3" style="margin-top:22px;">
                        <div class="vs-field">
                            <label>Paper Size</label>
                            <select class="vs-select" disabled>
                                <option>Letter (8.5 x 11 in)</option>
                            </select>
                        </div>

                        <div class="vs-field">
                            <label>Orientation</label>
                            <div class="vs-segment">
                                <button
                                    type="button"
                                    class="{{ $currentMode !== 'custom_landscape' ? 'is-active' : '' }}"
                                    wire:click.prevent="$set('data.verification_pdf_output_mode', '{{ $currentMode === 'standard' ? 'standard' : 'custom_portrait' }}')"
                                >
                                    Portrait
                                </button>
                                <button
                                    type="button"
                                    class="{{ $currentMode === 'custom_landscape' ? 'is-active' : '' }}"
                                    wire:click.prevent="$set('data.verification_pdf_output_mode', 'custom_landscape')"
                                >
                                    Landscape
                                </button>
                            </div>
                        </div>

                        <div class="vs-field">
                            <label>PDF Layout</label>
                            <select class="vs-select" wire:model.live="data.verification_pdf_output_mode">
                                @foreach ($this->getPdfOutputModeOptions() as $mode => $label)
                                    <option value="{{ $mode }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="vs-toggle-row">
                        <span>
                            <div style="color:var(--vs-navy);font-size:13px;font-weight:900;">Use as clinic default preset</div>
                            <div class="vs-small">New verifications will use this preset by default.</div>
                        </span>
                        <input class="vs-toggle" type="checkbox" wire:model.live="data.verification_pdf_preset_is_default">
                    </div>

                    <div class="vs-toggle-row">
                        <span>
                            <div style="color:var(--vs-navy);font-size:13px;font-weight:900;">Show blank rows</div>
                            <div class="vs-small">Turn off for more compact custom PDFs.</div>
                        </span>
                        <input class="vs-toggle" type="checkbox" wire:model.live="data.verification_pdf_show_blank_rows">
                    </div>

                    <div class="vs-toggle-row">
                        <span>
                            <div style="color:var(--vs-navy);font-size:13px;font-weight:900;">Allow verification manager template edits</div>
                            <div class="vs-small">Managers can draft and publish clinic-specific template changes.</div>
                        </span>
                        <input class="vs-toggle" type="checkbox" wire:model.live="data.allow_verification_manager_template_edits">
                    </div>

                    <div class="vs-included">
                        <div class="vs-included-head">
                            <span>
                                <h3 class="vs-card-title">Included Content</h3>
                                <p class="vs-card-subtitle">Choose which content should appear in the generated PDF.</p>
                            </span>
                            <span class="vs-check">
                                <input type="checkbox" checked disabled>
                                Select All
                            </span>
                        </div>
                        <div class="vs-checkbox-grid">
                            @foreach ($this->getPdfSectionOptions() as $sectionKey => $label)
                                <label class="vs-check">
                                    <input
                                        type="checkbox"
                                        value="{{ $sectionKey }}"
                                        wire:model.live="data.verification_pdf_output_sections"
                                    >
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if ($isCustomOutputMode)
                        <div class="vs-question-group">
                            @if (empty($selectedSections))
                                <div class="vs-question-card" style="padding:18px;color:var(--vs-muted);font-size:13px;line-height:1.6;">
                                    Select one or more content sections above to choose specific questions.
                                </div>
                            @elseif ($availableQuestionSections->isEmpty())
                                <div class="vs-question-card" style="padding:18px;color:var(--vs-muted);font-size:13px;line-height:1.6;">
                                    No active questions are available in the selected sections yet.
                                </div>
                            @else
                                @foreach ($availableQuestionSections as $section)
                                    <div class="vs-question-card">
                                        <div class="vs-question-head">
                                            <span>
                                                <div style="color:var(--vs-navy);font-size:14px;font-weight:900;">{{ $section['title'] }}</div>
                                                <div class="vs-small">{{ $section['selected_count'] }} of {{ $section['count'] }} questions selected</div>
                                            </span>
                                            <span style="display:flex;gap:8px;flex-wrap:wrap;">
                                                <button type="button" class="vs-button" style="min-height:32px;padding:6px 10px;font-size:12px;" wire:click.prevent="selectAllQuestionsForSection('{{ $section['key'] }}')">Select all</button>
                                                <button type="button" class="vs-button" style="min-height:32px;padding:6px 10px;font-size:12px;" wire:click.prevent="clearQuestionsForSection('{{ $section['key'] }}')">Clear</button>
                                            </span>
                                        </div>
                                        <div class="vs-question-grid">
                                            @foreach ($section['questions'] as $question)
                                                <label class="vs-check" style="align-items:flex-start;">
                                                    <input
                                                        type="checkbox"
                                                        value="{{ $question['id'] }}"
                                                        wire:model.live="data.verification_pdf_output_question_ids_by_section.{{ $section['key'] }}"
                                                    >
                                                    <span>{{ $question['prompt'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endif
                    </div>

                </div>
            </main>

            <aside class="vs-right" style="{{ $activeSettingsSection === 'pdf-settings' ? 'display:grid;gap:18px;' : 'display:none;' }}">
                <section class="vs-card">
                    <div class="vs-card-header">
                        <div class="vs-eyebrow">Preview Sample</div>
                        <p class="vs-card-subtitle">See how your PDF will look with current settings.</p>
                    </div>
                    <div style="padding:0 20px 20px;text-align:center;">
                        <div class="vs-preview-paper">
                            <div class="vs-preview-logo">
                                <span class="vs-preview-mark"></span>
                                ProDental
                            </div>
                            <div style="margin-top:22px;color:var(--vs-teal-dark);font-size:12px;font-weight:900;">Verification Report</div>
                            <div class="vs-preview-line" style="width:70%;"></div>
                            <div class="vs-preview-line" style="width:46%;"></div>
                            <div style="margin-top:22px;border:1px solid #e5edf6;border-radius:6px;padding:8px;">
                                @foreach ([70, 52, 80, 58, 74] as $width)
                                    <div class="vs-preview-line" style="width:{{ $width }}%;height:6px;margin-top:7px;"></div>
                                @endforeach
                            </div>
                        </div>
                        @if ($previewPdfUrl)
                            <a href="{{ $previewPdfUrl }}" target="_blank" rel="noopener" class="vs-button" style="margin-top:14px;background:var(--vs-teal-soft);border-color:#bde8e4;color:var(--vs-teal-dark);">
                                Preview Full PDF
                            </a>
                        @else
                            <button type="button" class="vs-button" disabled style="margin-top:14px;background:var(--vs-soft);border-color:var(--vs-border);color:var(--vs-muted);cursor:not-allowed;">
                                Preview Full PDF
                            </button>
                        @endif
                    </div>
                </section>

                <section class="vs-card">
                    <div class="vs-card-header">
                        <div class="vs-eyebrow">Settings Summary</div>
                    </div>
                    <div class="vs-summary">
                        @foreach ($summaryRows as $label => $value)
                            <div class="vs-summary-row">
                                <span>{{ $label }}</span>
                                <strong>{{ $value }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>
            </aside>
        </section>

        @if ($this->showCreateTemplateDraftModal)
            <div class="vs-modal-backdrop" wire:key="clinic-create-template-draft-modal">
                <section class="vs-modal" role="dialog" aria-modal="true" aria-labelledby="clinic-create-template-draft-title">
                    <header class="vs-modal-header">
                        <span>
                            <h2 id="clinic-create-template-draft-title" class="vs-modal-title">Create Template Draft</h2>
                            <p class="vs-card-subtitle">Name the draft, choose the form type, and decide how it should start.</p>
                        </span>
                        <button type="button" wire:click.prevent="closeCreateTemplateDraftModal" class="vs-button">Close</button>
                    </header>

                    <div class="vs-modal-body">
                        <div class="vs-field">
                            <label>Template Name <span class="vs-required">*</span></label>
                            <input
                                type="text"
                                class="vs-input"
                                wire:model.defer="newClinicTemplateDraftData.template_name"
                                placeholder="Example: Demo Solo Dental Clinic Custom Template"
                            >
                            @error('newClinicTemplateDraftData.template_name')
                                <div class="vs-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="vs-grid">
                            <div class="vs-field">
                                <label>Form Type</label>
                                <select class="vs-select" wire:model.defer="newClinicTemplateDraftData.form_type">
                                    @foreach (\App\Models\VerificationTemplateVersion::FORM_TYPE_OPTIONS as $formType => $formTypeLabel)
                                        <option value="{{ $formType }}">{{ $formTypeLabel }}</option>
                                    @endforeach
                                </select>
                                @error('newClinicTemplateDraftData.form_type')
                                    <div class="vs-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="vs-field">
                                <label>Replicate Type</label>
                                <select class="vs-select" wire:model.live="newClinicTemplateDraftData.starting_point">
                                    <option value="active">Copy active clinic template</option>
                                    <option value="specific_version">Copy an existing template</option>
                                    <option value="fresh">Start fresh</option>
                                </select>
                                @error('newClinicTemplateDraftData.starting_point')
                                    <div class="vs-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if (($this->newClinicTemplateDraftData['starting_point'] ?? 'active') === 'specific_version')
                            <div class="vs-field">
                                <label>Template to Copy</label>
                                <select class="vs-select" wire:model.defer="newClinicTemplateDraftData.source_version_id">
                                    <option value="">Select a template</option>
                                    @foreach ($this->clinicTemplateDraftSourceOptions() as $versionId => $versionLabel)
                                        <option value="{{ $versionId }}">{{ $versionLabel }}</option>
                                    @endforeach
                                </select>
                                @error('newClinicTemplateDraftData.source_version_id')
                                    <div class="vs-error">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if (($this->newClinicTemplateDraftData['starting_point'] ?? 'active') === 'fresh')
                            <div class="vs-alert">
                                Fresh draft creates an empty clinic template structure. Use this only when the clinic needs a fully custom setup from the beginning.
                            </div>
                        @else
                            <div class="vs-alert" style="border-color:#bfdbfe;background:#eff6ff;color:#1e40af;">
                                Copying keeps the existing sections and questions, then lets you safely edit the new draft before publishing.
                            </div>
                        @endif
                    </div>

                    <footer class="vs-modal-footer">
                        <span class="vs-small">This creates a draft only. The active clinic form does not change until the draft is published.</span>
                        <span style="display:flex;align-items:center;justify-content:flex-end;gap:10px;">
                            <button type="button" wire:click.prevent="closeCreateTemplateDraftModal" class="vs-button">Cancel</button>
                            <button type="button" wire:click.prevent="submitCreateClinicTemplateDraft" wire:loading.attr="disabled" wire:target="submitCreateClinicTemplateDraft" class="vs-button vs-button--primary">
                                <span wire:loading.remove wire:target="submitCreateClinicTemplateDraft">Create Draft Template</span>
                                <span wire:loading wire:target="submitCreateClinicTemplateDraft">Creating...</span>
                            </button>
                        </span>
                    </footer>
                </section>
            </div>
        @endif
    </div>
    <script>
        (() => {
            const allowedSections = ['template-selection', 'template-management', 'pdf-settings'];

            const normalizeLegacyHash = () => {
                const hashSection = window.location.hash.replace('#', '');

                if (! allowedSections.includes(hashSection)) {
                    return;
                }

                const url = new URL(window.location.href);
                url.hash = '';
                url.searchParams.set('section', hashSection);
                window.location.replace(url.toString());
            };

            const clearHash = () => {
                if (! window.location.hash) {
                    return;
                }

                history.replaceState(null, '', window.location.pathname + window.location.search);
            };

            normalizeLegacyHash();

            document.addEventListener('livewire:init', () => {
                Livewire.on('verification-settings-section-changed', clearHash);
            });
        })();
    </script>
</x-filament-panels::page>
