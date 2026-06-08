<x-filament-panels::page>
    @php
        $clinic = $this->getSelectedClinic();
        $questionSections = $this->getQuestionSections();
        $selectedSectionLabels = $this->getSelectedSectionLabels();
        $selectedQuestionSections = $this->getSelectedQuestionSections();
        $availableQuestionSections = $this->getAvailableQuestionSectionsForSelection();
        $currentMode = $this->data['verification_pdf_output_mode'] ?? 'standard';
        $selectedSections = is_array($this->data['verification_pdf_output_sections'] ?? null) ? $this->data['verification_pdf_output_sections'] : [];
        $verificationNavItems = [
            [
                'key' => 'settings',
                'label' => 'PDF Settings',
                'description' => 'Control PDF output and default verification template rules.',
                'url' => \App\Filament\Admin\Pages\VerificationSettings::getUrl(),
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance Directory',
                'description' => 'Maintain the shared insurance carrier master and clinic-specific defaults.',
                'url' => \App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource::getUrl('index'),
            ],
            [
                'key' => 'participation',
                'label' => 'Provider Participation',
                'description' => 'Manage participating and non-participating payer guidance for verifiers.',
                'url' => \App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource::getUrl('index'),
            ],
            [
                'key' => 'credentials',
                'label' => 'Portal Credentials',
                'description' => 'Maintain the shared portal credential vault clinics can inherit from.',
                'url' => \App\Filament\Admin\Pages\PortalCredentialSettings::getUrl(),
            ],
            [
                'key' => 'questions',
                'label' => 'Verification Questions',
                'description' => 'Manage prompts and section-specific question content.',
                'url' => \App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource::getUrl('index'),
            ],
            [
                'key' => 'arrangement',
                'label' => 'Question Arrangement',
                'description' => 'Reorder questions inside each verification section.',
                'url' => \App\Filament\Admin\Pages\VerificationQuestionArrangement::getUrl(),
            ],
            [
                'key' => 'notifications',
                'label' => 'Notification Control',
                'description' => 'Manage verification events, recipients, and urgent alert behavior.',
                'url' => \App\Filament\Admin\Pages\VerificationNotificationControl::getUrl(),
            ],
            [
                'key' => 'readiness',
                'label' => 'Verification Readiness',
                'description' => 'Review launch blockers, polish items, and readiness gaps.',
                'url' => \App\Filament\Admin\Pages\VerificationReadiness::getUrl(),
            ],
        ];
    @endphp

    <x-verification-management-shell
        :items="$verificationNavItems"
        active="settings"
        menu-title="Verification"
        menu-eyebrow="Admin Settings"
        menu-description="Configure verification output, question content, and section ordering from one workspace."
    >
    <div class="verification-settings-page" style="display: flex; flex-direction: column; gap: 22px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div>
                    <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Verification Output</div>
                    <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Clinic PDF Template</h3>
                    <p style="margin: 10px 0 0; max-width: 760px; font-size: 14px; line-height: 1.7; color: #64748b;">
                        This clinic template is used by Clinic users and by your Admin service team when they generate verification PDFs for this clinic.
                    </p>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                    <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                        <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Clinic</div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $clinic?->clinic_name ?: 'Select clinic scope' }}</div>
                    </div>
                    <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                        <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Current Output</div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $this->getCurrentOutputLabel() }}</div>
                    </div>
                </div>
            </div>

            <div style="padding: 22px;">
                <form wire:submit="save">
                    {{ $this->form }}
                </form>
            </div>
        </section>

        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Sub-Question Selection</div>
                <h3 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">Choose Questions to Include</h3>
                <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                    Review the available questions by section and choose exactly which ones should appear in the clinic PDF output.
                </p>
            </div>
            <div style="padding: 18px 22px; display: flex; flex-direction: column; gap: 12px;">
                @if ($currentMode !== 'selected')
                    <div style="border: 1px dashed #cbd5e1; border-radius: 18px; padding: 22px; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                        Switch the default output to <strong>Current with Selected Output</strong> to configure question-level PDF selection.
                    </div>
                @elseif (empty($selectedSections))
                    <div style="border: 1px dashed #cbd5e1; border-radius: 18px; padding: 22px; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                        Select one or more items in <strong>Selected output sections</strong>. The matching question sections will appear here.
                    </div>
                @elseif ($availableQuestionSections->isEmpty())
                    <div style="border: 1px dashed #cbd5e1; border-radius: 18px; padding: 22px; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                        No active questions are available in the selected sections yet. Use <strong>Manage verification questions</strong> to add them first.
                    </div>
                @else
                    @foreach ($availableQuestionSections as $section)
                        <div style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; overflow: hidden; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);">
                            <div style="padding: 16px 18px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                                <div>
                                    <div style="font-size: 17px; font-weight: 800; color: #0f172a;">{{ $section['title'] }}</div>
                                    <div style="margin-top: 4px; font-size: 12px; color: #64748b;">
                                        {{ $section['selected_count'] }} of {{ $section['count'] }} questions selected
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; border: 1px solid #dbe4ee; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700;">
                                        {{ $section['count'] }} questions
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="selectAllQuestionsForSection('{{ $section['key'] }}')"
                                        style="display: inline-flex; align-items: center; justify-content: center; padding: 7px 12px; border-radius: 999px; border: 1px solid #bae6fd; background: #eff6ff; color: #0369a1; font-size: 12px; font-weight: 800;"
                                    >
                                        Select all
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="clearQuestionsForSection('{{ $section['key'] }}')"
                                        style="display: inline-flex; align-items: center; justify-content: center; padding: 7px 12px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #64748b; font-size: 12px; font-weight: 800;"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                            <div style="padding: 16px 18px; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                                @foreach ($section['questions'] as $question)
                                    <label style="display: flex; align-items: flex-start; gap: 12px; min-height: 72px; padding: 14px 16px; border: 1px solid {{ $question['selected'] ? '#f59e0b' : '#e2e8f0' }}; border-radius: 18px; background: {{ $question['selected'] ? 'linear-gradient(180deg, #fffdf7 0%, #fff7ed 100%)' : '#ffffff' }}; box-shadow: {{ $question['selected'] ? '0 8px 20px rgba(245, 158, 11, 0.12)' : '0 2px 8px rgba(15, 23, 42, 0.04)' }};">
                                        <input
                                            type="checkbox"
                                            value="{{ $question['id'] }}"
                                            wire:model.live="data.verification_pdf_output_question_ids_by_section.{{ $section['key'] }}"
                                            style="margin-top: 2px; width: 16px; height: 16px;"
                                        />
                                        <span style="display: flex; flex-direction: column; gap: 6px;">
                                            <span style="font-size: 13px; line-height: 1.55; font-weight: 700; color: #0f172a;">
                                                {{ $question['prompt'] }}
                                            </span>
                                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: {{ $question['selected'] ? '#b45309' : '#94a3b8' }};">
                                                {{ $question['selected'] ? 'Included in PDF' : 'Available to include' }}
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </section>

        <section style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 22px;">
            <div style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Selected Output</div>
                    <h3 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">PDF Section Template</h3>
                </div>
                <div style="padding: 18px 22px; display: flex; flex-direction: column; gap: 10px;">
                    @forelse ($selectedSectionLabels as $label)
                        <div style="display: inline-flex; align-items: center; padding: 9px 12px; border-radius: 14px; border: 1px solid #dbe4ee; background: #f8fafc; color: #334155; font-size: 13px; font-weight: 700;">
                            {{ $label }}
                        </div>
                    @empty
                        <div style="font-size: 14px; line-height: 1.7; color: #64748b;">
                            No section list is needed for this clinic when the output mode is <strong>{{ $this->getCurrentOutputLabel() }}</strong>.
                        </div>
                    @endforelse
                </div>
            </div>

            <div style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                    <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">How It Works</div>
                    <h3 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">PDF Actions</h3>
                </div>
                <div style="padding: 18px 22px; display: grid; gap: 12px;">
                    <div style="padding: 14px 16px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fafc;">
                        <div style="margin-bottom: 6px; font-size: 13px; font-weight: 800; color: #0f172a;">Download PDF</div>
                        <div style="font-size: 13px; line-height: 1.7; color: #64748b;">Downloads the clinic-approved output directly to the device.</div>
                    </div>
                    <div style="padding: 14px 16px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fafc;">
                        <div style="margin-bottom: 6px; font-size: 13px; font-weight: 800; color: #0f172a;">View PDF</div>
                        <div style="font-size: 13px; line-height: 1.7; color: #64748b;">Opens the same clinic-approved output inline in a new tab for quick review.</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    </x-verification-management-shell>
</x-filament-panels::page>
