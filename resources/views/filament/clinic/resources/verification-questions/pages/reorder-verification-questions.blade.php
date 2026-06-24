<x-filament-panels::page>
    @php
        $questionSections = $this->getVisibleQuestionSections();
        $sectionOptions = $this->getSectionFilterOptions();
        $selectedClinicName = $this->getSelectedClinicName();
        $showPortalCredentials = \App\Support\VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService();
        $verificationNavItems = [
            [
                'key' => 'settings',
                'label' => 'PDF Settings',
                'description' => 'Control PDF output and default verification template rules.',
                'url' => \App\Filament\Clinic\Pages\VerificationSettings::getUrl(),
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance Directory',
                'description' => 'Browse the shared carrier master and manage clinic-specific changes.',
                'url' => \App\Filament\Clinic\Resources\InsuranceCarriers\InsuranceCarrierResource::getUrl('index'),
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
            'label' => 'Template Management',
            'description' => 'Manage prompts and section-specific question content.',
            'url' => \App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource::getUrl('index'),
        ];
        $verificationNavItems[] = [
            'key' => 'arrangement',
            'label' => 'Question Arrangement',
            'description' => 'Reorder questions inside each verification section.',
            'url' => \App\Filament\Clinic\Pages\VerificationQuestionArrangement::getUrl(),
        ];
    @endphp

    <x-verification-management-shell
        :items="$verificationNavItems"
        active="arrangement"
        menu-title="Verification"
        menu-eyebrow="Clinic Settings"
        menu-description="Configure verification output, question content, and section ordering from one workspace."
    >
    <div style="display: flex; flex-direction: column; gap: 22px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
                <div>
                    <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #ecfeff; border: 1px solid #99f6e4; color: #0f766e; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase;">
                        Verification Workspace
                    </div>
                    <h2 style="margin: 14px 0 0; font-size: 30px; line-height: 1.08; font-weight: 800; color: #0f172a;">
                        Question Arrangement
                    </h2>
                    <p style="margin: 10px 0 0; max-width: 920px; font-size: 15px; line-height: 1.7; color: #64748b;">
                        Reorder section questions without opening create or edit. Use this space only for sequencing, so the team can keep every section clean and easy to maintain.
                    </p>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                    @if ($selectedClinicName)
                        <div style="min-width: 220px; padding: 14px 16px; border-radius: 18px; border: 1px solid #d1fae5; background: #f0fdf4;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #166534;">Clinic Scope</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $selectedClinicName }}</div>
                        </div>
                    @endif

                    <a
                        href="{{ $this->getListUrl() }}"
                        style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 16px; border-radius: 16px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 800; text-decoration: none;"
                    >
                        Back to question library
                    </a>
                </div>
            </div>

            @if ($selectedClinicName)
                <div style="padding: 22px 24px; display: grid; gap: 18px;">
                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05); overflow: hidden;">
                        <div style="padding: 16px 18px; border-bottom: 1px solid #edf2f7; background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);">
                            <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0f766e;">Verification Workspace</div>
                            <div style="margin-top: 8px; font-size: 15px; line-height: 1.6; color: #475569;">
                                Choose one verification section first. Only that section’s questions will load below so reordering stays quick and focused.
                            </div>
                        </div>
                        <div style="padding: 18px;">
                            <label for="selectedSectionKey" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 800; color: #0f172a;">
                                Active section
                            </label>
                            <select
                                id="selectedSectionKey"
                                wire:model.live="selectedSectionKey"
                                style="width: 100%; max-width: 460px; padding: 12px 14px; border-radius: 16px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 700;"
                            >
                                @foreach ($sectionOptions as $sectionKey => $sectionTitle)
                                    <option value="{{ $sectionKey }}">{{ $sectionTitle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </section>

                    @foreach ($questionSections as $section)
                        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05); overflow: hidden;">
                            <div style="padding: 16px 18px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);">
                                <div>
                                    <div style="font-size: 18px; font-weight: 800; color: #0f172a;">{{ $section['title'] }}</div>
                                    <div style="margin-top: 4px; font-size: 12px; color: #64748b;">
                                        {{ $section['count'] }} questions in this section
                                    </div>
                                </div>

                                <span style="display: inline-flex; align-items: center; padding: 8px 12px; border-radius: 999px; border: 1px solid #fed7aa; background: #fff7ed; color: #b45309; font-size: 12px; font-weight: 800;">
                                    Dedicated reorder space
                                </span>
                            </div>

                            <div style="padding: 18px; display: grid; gap: 10px;">
                                <div style="display: grid; gap: 10px;">
                                    @foreach ($section['questions'] as $question)
                                        <article style="border: 1px solid #e2e8f0; border-radius: 18px; background: {{ $question['is_active'] ? '#ffffff' : '#fff7f7' }}; box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04); overflow: hidden;">
                                            <div style="padding: 12px 16px; display: grid; grid-template-columns: minmax(0, 1.8fr) auto auto minmax(420px, 1fr); align-items: center; gap: 12px;">
                                                <div style="font-size: 14px; line-height: 1.5; font-weight: 800; color: #0f172a;">
                                                    {{ $question['prompt'] }}
                                                </div>
                                                @if ($question['is_active'])
                                                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 5px 9px; border-radius: 999px; border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; font-size: 11px; font-weight: 700; white-space: nowrap;">Active</span>
                                                @else
                                                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 5px 9px; border-radius: 999px; border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; font-size: 11px; font-weight: 700; white-space: nowrap;">Inactive</span>
                                                @endif
                                                <div style="font-size: 11px; font-weight: 800; color: #94a3b8; white-space: nowrap;">
                                                    #{{ $question['sort_order'] }}
                                                </div>
                                                <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px;">
                                                    <button type="button" wire:click="repositionQuestion({{ $question['id'] }}, 'top')" style="display: inline-flex; align-items: center; justify-content: center; padding: 9px 10px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 12px; font-weight: 800;">Top</button>
                                                    <button type="button" wire:click="repositionQuestion({{ $question['id'] }}, 'up')" style="display: inline-flex; align-items: center; justify-content: center; padding: 9px 10px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 12px; font-weight: 800;">Move Up</button>
                                                    <button type="button" wire:click="repositionQuestion({{ $question['id'] }}, 'down')" style="display: inline-flex; align-items: center; justify-content: center; padding: 9px 10px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 12px; font-weight: 800;">Move Down</button>
                                                    <button type="button" wire:click="repositionQuestion({{ $question['id'] }}, 'bottom')" style="display: inline-flex; align-items: center; justify-content: center; padding: 9px 10px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 12px; font-weight: 800;">Bottom</button>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            @else
                <div style="padding: 22px 24px;">
                    <div style="border: 1px dashed #cbd5e1; border-radius: 20px; background: #f8fafc; padding: 26px; text-align: center;">
                        <div style="margin-bottom: 8px; font-size: 16px; font-weight: 800; color: #0f172a;">Select a clinic to reorder its questions</div>
                        <div style="font-size: 14px; line-height: 1.7; color: #64748b;">
                            Choose a clinic from the Workspace menu first. This arrangement space follows the selected clinic scope.
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>
    </x-verification-management-shell>
</x-filament-panels::page>
