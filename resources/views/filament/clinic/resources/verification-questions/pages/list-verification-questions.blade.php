<x-filament-panels::page>
    @php
        $questionSections = $this->getQuestionSections();
        $selectedClinicName = $this->getSelectedClinicName();
        $templateOptions = $this->getTemplateOptions();
        $selectedTemplateLabel = $this->getSelectedTemplateLabel();
        $versionSummary = $this->getVersionSummary();
        $templateVersionHistory = $this->getTemplateVersionHistory();
        $showPortalCredentials = \App\Support\VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService();
        $verificationNavItems = [
            [
                'key' => 'settings',
                'label' => 'PDF Settings',
                'description' => 'Control PDF output and default verification template rules.',
                'url' => \App\Filament\Clinic\Pages\VerificationSettings::getUrl(),
            ],
        ];
        if ($showPortalCredentials) {
            $verificationNavItems[] = [
                'key' => 'credentials',
                'label' => 'Portal Credentials',
                'description' => 'Keep clinic-specific website and payer portal credentials without using spreadsheets.',
                'url' => \App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource::getUrl('index'),
            ];
        }
        $verificationNavItems[] = [
            'key' => 'questions',
            'label' => 'Clinic Template',
            'description' => 'Manage this clinic-specific template without changing the platform template.',
            'url' => \App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource::getUrl('index'),
        ];
        if ($versionSummary['showing_draft']) {
            $verificationNavItems[] = [
                'key' => 'arrangement',
                'label' => 'Question Arrangement',
                'description' => 'Reorder questions inside each verification section.',
                'url' => \App\Filament\Clinic\Pages\VerificationQuestionArrangement::getUrl(),
            ];
        }
    @endphp

    <style>
        .ct-page {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .ct-card {
            border: 1px solid #dbe4ee;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .ct-hero {
            border: 1px solid #dbe4ee;
            border-radius: 24px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .ct-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            padding: 9px 13px;
            border: 1px solid #dbe4ee;
            border-radius: 12px;
            background: #ffffff;
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .ct-button--primary {
            border-color: #0f766e;
            background: #0f766e;
            color: #ffffff;
        }

        .ct-button--success {
            border-color: #10b981;
            background: #10b981;
            color: #052e16;
        }

        .ct-button--muted {
            background: #f8fafc;
            color: #334155;
        }

        .ct-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
        }

        .ct-label {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
        }

        .ct-chip {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            border: 1px solid #dbe4ee;
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 900;
        }

        .ct-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.42);
        }

        .ct-modal {
            width: min(640px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            border: 1px solid #dbe4ee;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.24);
        }

        .ct-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .ct-input,
        .ct-select {
            width: 100%;
            min-height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            padding: 9px 12px;
            color: #0f172a;
            font-size: 14px;
        }

        @media (max-width: 760px) {
            .ct-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="ct-page">
        @if ($selectedClinicName)
            <section class="ct-card" style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0;">
                <div style="padding: 16px 18px; border-right: 1px solid #e2e8f0;">
                    <div class="ct-label">Clinic</div>
                    <div style="margin-top: 7px; font-size: 15px; font-weight: 900; color: #0f172a;">{{ $selectedClinicName }}</div>
                </div>
                <div style="padding: 16px 18px; border-right: 1px solid #e2e8f0;">
                    <div class="ct-label">Current Template</div>
                    <div style="margin-top: 7px; font-size: 15px; font-weight: 900; color: #0f172a;">{{ $selectedTemplateLabel }}</div>
                </div>
                <div style="padding: 16px 18px; border-right: 1px solid #e2e8f0;">
                    <div class="ct-label">Status</div>
                    <div style="margin-top: 7px;">
                        <span class="ct-chip" style="border-color: {{ $versionSummary['showing_draft'] ? '#fbbf24' : '#99f6e4' }}; background: {{ $versionSummary['showing_draft'] ? '#fffbeb' : '#f0fdfa' }}; color: {{ $versionSummary['showing_draft'] ? '#92400e' : '#0f766e' }};">
                            {{ $versionSummary['showing_draft'] ? 'Draft Open' : 'Published' }}
                        </span>
                    </div>
                </div>
                <div style="padding: 16px 18px; border-right: 1px solid #e2e8f0;">
                    <div class="ct-label">Structure</div>
                    <div style="margin-top: 7px; font-size: 15px; font-weight: 900; color: #0f172a;">{{ $questionSections->count() }} sections / {{ $questionSections->sum('count') }} questions</div>
                </div>
                <div style="padding: 16px 18px;">
                    <div class="ct-label">Last Published</div>
                    <div style="margin-top: 7px; font-size: 13px; font-weight: 800; color: #475569;">{{ $versionSummary['active_published_at'] }}</div>
                </div>
            </section>
        @endif

        <section class="ct-hero">
            <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
                <div>
                    <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #ecfeff; border: 1px solid #99f6e4; color: #0f766e; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase;">
                        Clinic Workspace
                    </div>
                    <h2 style="margin: 10px 0 0; font-size: 28px; line-height: 1.08; font-weight: 800; color: #0f172a;">
                        Clinic Template
                    </h2>
                    <p style="margin: 8px 0 0; max-width: 900px; font-size: 14px; line-height: 1.6; color: #64748b;">
                        Manage the clinic copy, add questions, adjust ordering, and publish changes from one place.
                    </p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
                    @if ($selectedClinicName)
                        @if ($versionSummary['can_manage'])
                            <div class="ct-actions">
                                @if (! $versionSummary['has_draft'])
                                    <button type="button" class="ct-button ct-button--primary" wire:click="openCreateDraftModal" wire:loading.attr="disabled" wire:target="openCreateDraftModal">
                                        Create Draft
                                    </button>
                                @elseif (! $versionSummary['showing_draft'])
                                    <button type="button" class="ct-button" wire:click="openDraftVersion" wire:loading.attr="disabled" wire:target="openDraftVersion">
                                        Open Draft
                                    </button>
                                @else
                                    <a href="{{ $this->getCreateUrl() }}" class="ct-button ct-button--primary">
                                        New Question
                                    </a>
                                    <button type="button" class="ct-button ct-button--muted" wire:click="openTemplateSectionModal('section')" wire:loading.attr="disabled" wire:target="openTemplateSectionModal">
                                        Add Section
                                    </button>
                                    <button type="button" class="ct-button ct-button--muted" wire:click="openTemplateSectionModal('sub_section')" wire:loading.attr="disabled" wire:target="openTemplateSectionModal">
                                        Add Sub-section
                                    </button>
                                    <a href="{{ \App\Filament\Clinic\Pages\VerificationQuestionArrangement::getUrl() }}" class="ct-button ct-button--muted">
                                        Rearrange
                                    </a>
                                    <button type="button" class="ct-button ct-button--success" wire:click="publishDraftVersion" wire:confirm="Publish this clinic template draft?" wire:loading.attr="disabled" wire:target="publishDraftVersion">
                                        Publish Draft
                                    </button>
                                    <button type="button" class="ct-button" wire:click="closeDraftVersion" wire:loading.attr="disabled" wire:target="closeDraftVersion">
                                        View Published
                                    </button>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            @if ($selectedClinicName)
                <div style="padding: 14px 24px; border-bottom: 1px solid #edf2f7; background: {{ $versionSummary['showing_draft'] ? '#fffbeb' : '#f8fafc' }};">
                    <div style="display: flex; align-items: flex-start; gap: 12px; max-width: 960px;">
                        <div style="width: 10px; height: 10px; margin-top: 7px; border-radius: 999px; background: {{ $versionSummary['showing_draft'] ? '#f59e0b' : '#64748b' }}; flex: 0 0 auto;"></div>
                        <div>
                            <div style="font-size: 13px; font-weight: 900; color: #0f172a;">
                                {{ $versionSummary['showing_draft'] ? 'You are editing a clinic draft. Publish when ready.' : ($versionSummary['has_draft'] ? 'You are viewing the published clinic template. Open Draft to edit unpublished changes.' : 'Published clinic template is locked. Create Draft Version to edit.') }}
                            </div>
                            <p style="margin: 4px 0 0; color: #475569; font-size: 13px; line-height: 1.6;">
                                {{ $versionSummary['showing_draft'] ? 'New questions, sections, and ordering changes stay isolated to this clinic until the draft is published.' : 'Existing verification requests keep their current template snapshot until the user refreshes them.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div style="padding: 18px 22px; display: grid; gap: 16px;">
                    <section style="border: 1px solid #dbe4ee; border-radius: 20px; background: #ffffff; overflow: hidden;">
                        <div style="padding: 15px 18px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                            <div>
                                <h3 style="margin: 0; font-size: 18px; font-weight: 900; color: #0f172a;">Template Versions</h3>
                                <p style="margin: 5px 0 0; color: #64748b; font-size: 13px; line-height: 1.55;">One active clinic template is used for new verification requests. Older templates stay available for existing snapshots.</p>
                            </div>
                            @if ($versionSummary['can_manage'] && ! $versionSummary['has_draft'])
                                <button type="button" class="ct-button ct-button--primary" wire:click="openCreateDraftModal" wire:loading.attr="disabled" wire:target="openCreateDraftModal">
                                    Create Draft
                                </button>
                            @endif
                        </div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; min-width: 920px; border-collapse: collapse; table-layout: fixed;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <th style="width: 28%; padding: 12px 16px; text-align: left;" class="ct-label">Template</th>
                                        <th style="width: 14%; padding: 12px 16px; text-align: left;" class="ct-label">Status</th>
                                        <th style="width: 14%; padding: 12px 16px; text-align: left;" class="ct-label">Form Type</th>
                                        <th style="width: 20%; padding: 12px 16px; text-align: left;" class="ct-label">Structure</th>
                                        <th style="width: 14%; padding: 12px 16px; text-align: left;" class="ct-label">Updated</th>
                                        <th style="width: 10%; padding: 12px 16px; text-align: right;" class="ct-label">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom: 1px solid #edf2f7;">
                                        <td style="padding: 14px 16px;">
                                            <div style="font-size: 14px; font-weight: 900; color: #0f172a;">{{ $versionSummary['active_name'] }}</div>
                                            <div style="margin-top: 4px; font-size: 12px; color: #64748b;">Active clinic template</div>
                                        </td>
                                        <td style="padding: 14px 16px;"><span class="ct-chip" style="border-color: #99f6e4; background: #f0fdfa; color: #0f766e;">Published</span></td>
                                        <td style="padding: 14px 16px; font-size: 13px; color: #334155;">{{ $versionSummary['active_form_type'] }}</td>
                                        <td style="padding: 14px 16px; font-size: 13px; color: #334155;">{{ $questionSections->count() }} sections / {{ $questionSections->sum('active_count') }}/{{ $questionSections->sum('count') }} active questions</td>
                                        <td style="padding: 14px 16px; font-size: 13px; color: #334155;">{{ $versionSummary['active_published_at'] }}</td>
                                        <td style="padding: 14px 16px; text-align: right;">
                                            @if ($versionSummary['has_draft'] && ! $versionSummary['showing_draft'])
                                                <button type="button" class="ct-button" wire:click="openDraftVersion" wire:loading.attr="disabled" wire:target="openDraftVersion">Open Draft</button>
                                            @elseif ($versionSummary['showing_draft'])
                                                <button type="button" class="ct-button" wire:click="closeDraftVersion" wire:loading.attr="disabled" wire:target="closeDraftVersion">View Published</button>
                                            @else
                                                <span style="font-size: 12px; color: #94a3b8; font-weight: 800;">Current</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($versionSummary['has_draft'])
                                        <tr style="border-bottom: 1px solid #edf2f7; background: #fffbeb;">
                                            <td style="padding: 14px 16px;">
                                                <div style="font-size: 14px; font-weight: 900; color: #0f172a;">{{ $versionSummary['working_name'] ?? 'Clinic Template Draft' }}</div>
                                                <div style="margin-top: 4px; font-size: 12px; color: #64748b;">Working draft</div>
                                            </td>
                                            <td style="padding: 14px 16px;"><span class="ct-chip" style="border-color: #fbbf24; background: #fff7ed; color: #92400e;">Draft</span></td>
                                            <td style="padding: 14px 16px; font-size: 13px; color: #334155;">{{ $versionSummary['working_form_type'] }}</td>
                                            <td style="padding: 14px 16px; font-size: 13px; color: #334155;">{{ $questionSections->count() }} sections / {{ $questionSections->sum('active_count') }}/{{ $questionSections->sum('count') }} active questions</td>
                                            <td style="padding: 14px 16px; font-size: 13px; color: #334155;">{{ $versionSummary['working_status'] }}</td>
                                            <td style="padding: 14px 16px; text-align: right;">
                                                @if (! $versionSummary['showing_draft'])
                                                    <button type="button" class="ct-button" wire:click="openDraftVersion" wire:loading.attr="disabled" wire:target="openDraftVersion">Open Draft</button>
                                                @else
                                                    <button type="button" class="ct-button ct-button--success" wire:click="publishDraftVersion" wire:confirm="Publish this clinic template draft?" wire:loading.attr="disabled" wire:target="publishDraftVersion">Publish</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section style="border: 1px solid #bfdbfe; border-radius: 20px; background: linear-gradient(135deg, #eff6ff 0%, #ffffff 78%); padding: 16px 18px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12px; font-weight: 900; letter-spacing: 0.14em; text-transform: uppercase; color: #1d4ed8;">Active Template</div>
                            <div style="margin-top: 6px; font-size: 20px; font-weight: 900; color: #0f172a;">{{ $selectedTemplateLabel }}</div>
                            <div style="margin-top: 4px; font-size: 13px; color: #64748b;">Clinic-specific sections and questions are versioned separately from the platform template.</div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; padding: 9px 13px; border-radius: 999px; border: 1px solid #bfdbfe; background: #ffffff; color: #1d4ed8; font-size: 12px; font-weight: 900;">
                                {{ $questionSections->count() }} sections
                            </span>
                            <span style="display: inline-flex; align-items: center; padding: 9px 13px; border-radius: 999px; border: 1px solid #99f6e4; background: #f0fdfa; color: #0f766e; font-size: 12px; font-weight: 900;">
                                {{ $questionSections->sum('active_count') }} active
                            </span>
                            <span style="display: inline-flex; align-items: center; padding: 9px 13px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 12px; font-weight: 900;">
                                {{ $questionSections->sum('count') }} questions
                            </span>
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05); overflow: hidden;">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                            <div>
                                <h3 style="margin: 0; font-size: 20px; font-weight: 900; color: #0f172a;">Template Structure</h3>
                                <p style="margin: 6px 0 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                                    {{ $versionSummary['showing_draft'] ? 'Review and edit this clinic draft by section.' : 'Review the published clinic template. Open Draft before making changes.' }}
                                </p>
                            </div>
                            <span style="display: inline-flex; align-items: center; padding: 7px 11px; border-radius: 999px; border: 1px solid {{ $versionSummary['showing_draft'] ? '#fbbf24' : '#99f6e4' }}; background: {{ $versionSummary['showing_draft'] ? '#fffbeb' : '#f0fdfa' }}; color: {{ $versionSummary['showing_draft'] ? '#92400e' : '#0f766e' }}; font-size: 12px; font-weight: 900;">
                                {{ $versionSummary['showing_draft'] ? 'Draft Open' : 'Published View' }}
                            </span>
                        </div>

                        <div style="overflow-x: auto;">
                            <table style="width: 100%; min-width: 980px; border-collapse: collapse; table-layout: fixed;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <th style="width: 22%; padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 900; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Section</th>
                                        <th style="width: 34%; padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 900; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Question</th>
                                        <th style="width: 12%; padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 900; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Form</th>
                                        <th style="width: 12%; padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 900; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Answer</th>
                                        <th style="width: 10%; padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 900; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Status</th>
                                        <th style="width: 10%; padding: 13px 16px; text-align: right; font-size: 11px; font-weight: 900; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($questionSections as $section)
                                        @forelse ($section['questions'] as $question)
                                            <tr style="border-bottom: 1px solid #edf2f7; background: {{ $question['is_active'] ? '#ffffff' : '#fff7f7' }};">
                                                <td style="padding: 14px 16px; vertical-align: top;">
                                                    <div style="font-size: 13px; font-weight: 900; color: #0f172a; line-height: 1.45;">{{ $section['title'] }}</div>
                                                    <div style="margin-top: 4px; font-size: 12px; color: #64748b;">{{ $section['active_count'] }}/{{ $section['count'] }} active</div>
                                                </td>
                                                <td style="padding: 14px 16px; vertical-align: top;">
                                                    <div style="font-size: 14px; line-height: 1.55; font-weight: 800; color: #0f172a;">{{ $question['prompt'] }}</div>
                                                    <div style="margin-top: 7px; display: flex; gap: 6px; flex-wrap: wrap;">
                                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 800;">Order #{{ $question['sort_order'] }}</span>
                                                        @if ($question['is_builtin'])
                                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; border: 1px solid #dbeafe; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 800;">System</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td style="padding: 14px 16px; vertical-align: top; font-size: 13px; color: #334155;">{{ $question['form_type'] }}</td>
                                                <td style="padding: 14px 16px; vertical-align: top; font-size: 13px; color: #334155;">{{ $question['input_type'] }}</td>
                                                <td style="padding: 14px 16px; vertical-align: top;">
                                                    @if ($question['is_active'])
                                                        <span style="display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; font-size: 11px; font-weight: 900;">Active</span>
                                                    @else
                                                        <span style="display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; font-size: 11px; font-weight: 900;">Inactive</span>
                                                    @endif
                                                </td>
                                                <td style="padding: 14px 16px; vertical-align: top; text-align: right;">
                                                    @if ($versionSummary['showing_draft'])
                                                        <div style="display: inline-flex; gap: 10px; align-items: center;">
                                                            <a
                                                                href="{{ $this->getEditUrl($question['id']) }}"
                                                                style="display: inline-flex; align-items: center; color: #c2410c; font-size: 13px; font-weight: 900; text-decoration: none;"
                                                            >
                                                                Edit
                                                            </a>
                                                            <button
                                                                type="button"
                                                                wire:click="deleteQuestion({{ $question['id'] }})"
                                                                wire:confirm="Delete this verification question?"
                                                                style="display: inline-flex; align-items: center; color: #dc2626; font-size: 13px; font-weight: 900; background: transparent; border: none; padding: 0; cursor: pointer;"
                                                            >
                                                                Delete
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span style="font-size: 12px; font-weight: 800; color: #94a3b8;">Read-only</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr style="border-bottom: 1px solid #edf2f7;">
                                                <td style="padding: 14px 16px; vertical-align: top;">
                                                    <div style="font-size: 13px; font-weight: 900; color: #0f172a;">{{ $section['title'] }}</div>
                                                    <div style="margin-top: 4px; font-size: 12px; color: #64748b;">0 active</div>
                                                </td>
                                                <td colspan="5" style="padding: 14px 16px; color: #64748b; font-size: 13px; line-height: 1.6;">
                                                    No questions are configured in this section yet. {{ $versionSummary['showing_draft'] ? 'Use New Question and choose this section to add one.' : ($versionSummary['has_draft'] ? 'Open the draft before adding questions.' : 'Create a draft before adding questions.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            @else
                <div style="padding: 22px 24px;">
                    <div style="border: 1px dashed #cbd5e1; border-radius: 20px; background: #f8fafc; padding: 26px; text-align: center;">
                        <div style="margin-bottom: 8px; font-size: 16px; font-weight: 800; color: #0f172a;">Select a clinic to manage its question set</div>
                        <div style="font-size: 14px; line-height: 1.7; color: #64748b;">
                            Choose a clinic from the Workspace menu first. The question library and PDF output settings both follow the selected clinic scope.
                        </div>
                    </div>
                </div>
            @endif
        </section>

        @if ($selectedClinicName)
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <h3 style="margin: 0; font-size: 20px; font-weight: 900; color: #0f172a;">Clinic Version History</h3>
                        <p style="margin: 8px 0 0; max-width: 760px; font-size: 13px; line-height: 1.65; color: #64748b;">
                            Published versions remain available for existing request snapshots. Draft versions are working copies until published.
                        </p>
                    </div>
                    <span style="display: inline-flex; align-items: center; padding: 7px 11px; border-radius: 999px; border: 1px solid #dbe4ee; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 800;">
                        {{ count($templateVersionHistory) }} versions
                    </span>
                </div>

                <div style="padding: 14px 24px 20px; display: flex; flex-direction: column; gap: 10px;">
                    @forelse ($templateVersionHistory as $version)
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; padding: 13px 14px; border: 1px solid #e5e7eb; border-radius: 14px; background: #ffffff;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <strong style="font-size: 14px; color: #0f172a;">{{ $version['name'] }}</strong>
                                    <span style="display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; border: 1px solid #dbe4ee; background: #f8fafc; color: #475569; font-size: 11px; font-weight: 800;">{{ $version['version'] }}</span>
                                    <span style="display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; border: 1px solid {{ $version['status'] === 'Draft' ? '#fbbf24' : ($version['is_active'] ? '#99f6e4' : '#e2e8f0') }}; background: {{ $version['status'] === 'Draft' ? '#fffbeb' : ($version['is_active'] ? '#f0fdfa' : '#f8fafc') }}; color: {{ $version['status'] === 'Draft' ? '#92400e' : ($version['is_active'] ? '#0f766e' : '#64748b') }}; font-size: 11px; font-weight: 800;">{{ $version['status'] }}</span>
                                    @if ($version['is_working_draft'])
                                        <span style="display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c; font-size: 11px; font-weight: 800;">Working Draft</span>
                                    @endif
                                    <span style="display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; border: 1px solid #dbe4ee; background: #f8fafc; color: #475569; font-size: 11px; font-weight: 800;">{{ $version['form_type'] }}</span>
                                    <span style="display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 800;">{{ $version['clinic_visibility'] }}</span>
                                </div>
                                <div style="margin-top: 6px; font-size: 12px; line-height: 1.55; color: #64748b;">
                                    {{ $version['published_at'] ? 'Published ' . $version['published_at'] : 'Not published yet' }}
                                </div>
                                @if (filled($version['notes']))
                                    <div style="margin-top: 6px; max-width: 760px; font-size: 12px; line-height: 1.55; color: #475569;">
                                        {{ $version['notes'] }}
                                    </div>
                                @endif
                            </div>

                            @if ($version['is_active'])
                                <span style="font-size: 12px; font-weight: 800; color: #0f766e;">Active</span>
                            @endif
                        </div>
                    @empty
                        <div style="border: 1px dashed #cbd5e1; border-radius: 16px; background: #f8fafc; padding: 18px; text-align: center; color: #64748b; font-size: 14px;">
                            No clinic template versions have been created yet.
                        </div>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($showCreateDraftModal)
            <div class="ct-modal-backdrop" role="dialog" aria-modal="true">
                <form wire:submit.prevent="submitCreateDraftVersion" class="ct-modal">
                    <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                        <div>
                            <h3 style="margin: 0; font-size: 20px; font-weight: 900; color: #0f172a;">Create Clinic Draft</h3>
                            <p style="margin: 6px 0 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                                Name the draft and decide whether clinic users can see this template after it is published.
                            </p>
                        </div>
                        <button type="button" class="ct-button" wire:click="closeCreateDraftModal">Close</button>
                    </div>

                    <div style="padding: 18px 20px; display: grid; gap: 14px;">
                        <div class="ct-field">
                            <label class="ct-label" for="clinic-template-draft-name">Template name</label>
                            <input id="clinic-template-draft-name" class="ct-input" type="text" wire:model.defer="newDraftData.template_name">
                            @error('newDraftData.template_name')
                                <div style="font-size: 12px; color: #dc2626;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                            <div class="ct-field">
                                <label class="ct-label" for="clinic-template-form-type">Type of form</label>
                                <select id="clinic-template-form-type" class="ct-select" wire:model.defer="newDraftData.form_type">
                                    @foreach (\App\Models\VerificationTemplateVersion::FORM_TYPE_OPTIONS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('newDraftData.form_type')
                                    <div style="font-size: 12px; color: #dc2626;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="ct-field">
                                <label class="ct-label" for="clinic-template-visibility">Clinic visibility</label>
                                <select id="clinic-template-visibility" class="ct-select" wire:model.defer="newDraftData.clinic_visibility">
                                    @foreach (\App\Models\VerificationTemplateVersion::CLINIC_VISIBILITY_OPTIONS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('newDraftData.clinic_visibility')
                                    <div style="font-size: 12px; color: #dc2626;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div style="border: 1px solid #dbe4ee; border-radius: 14px; background: #f8fafc; padding: 13px 14px; color: #475569; font-size: 13px; line-height: 1.6;">
                            This draft starts from the active published clinic template. Existing verification requests stay on their current snapshot until refreshed.
                        </div>
                    </div>

                    <div style="padding: 16px 20px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="ct-button" wire:click="closeCreateDraftModal">Cancel</button>
                        <button type="submit" class="ct-button ct-button--primary" wire:loading.attr="disabled" wire:target="submitCreateDraftVersion">
                            Create Draft
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @if ($showTemplateSectionModal)
            <div class="ct-modal-backdrop" role="dialog" aria-modal="true">
                <form wire:submit.prevent="submitTemplateSection" class="ct-modal">
                    <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                        <div>
                            <h3 style="margin: 0; font-size: 20px; font-weight: 900; color: #0f172a;">
                                {{ $templateSectionMode === 'sub_section' ? 'Add Sub-section' : 'Add Section' }}
                            </h3>
                            <p style="margin: 6px 0 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                                This will be added only to the open clinic draft.
                            </p>
                        </div>
                        <button type="button" class="ct-button" wire:click="closeTemplateSectionModal">Close</button>
                    </div>

                    <div style="padding: 18px 20px; display: grid; gap: 14px;">
                        @if ($templateSectionMode === 'sub_section')
                            <div class="ct-field">
                                <label class="ct-label" for="clinic-template-parent-section">Parent section</label>
                                <select id="clinic-template-parent-section" class="ct-select" wire:model.defer="newTemplateSectionData.parent_section_key">
                                    <option value="">Select a section</option>
                                    @foreach ($questionSections as $section)
                                        <option value="{{ $section['key'] }}">{{ $section['title'] }}</option>
                                    @endforeach
                                </select>
                                @error('newTemplateSectionData.parent_section_key')
                                    <div style="font-size: 12px; color: #dc2626;">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="ct-field">
                            <label class="ct-label" for="clinic-template-section-name">
                                {{ $templateSectionMode === 'sub_section' ? 'Sub-section name' : 'Section name' }}
                            </label>
                            <input id="clinic-template-section-name" class="ct-input" type="text" wire:model.defer="newTemplateSectionData.label" placeholder="Example: Implant Coverage">
                            @error('newTemplateSectionData.label')
                                <div style="font-size: 12px; color: #dc2626;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div style="padding: 16px 20px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="ct-button" wire:click="closeTemplateSectionModal">Cancel</button>
                        <button type="submit" class="ct-button ct-button--primary" wire:loading.attr="disabled" wire:target="submitTemplateSection">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-filament-panels::page>
