<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $summaryCards = $this->getWorkbenchSummary();
        $contextRows = $this->getContextRows();
        $sectionProgress = $this->getVerificationSectionProgress();
        $quickReference = $this->getQuickReferenceCard();
        $coreDetails = $this->getCoreDetailRows();
        $coverageMatrix = $this->getCoverageMatrix();
        $planProvisionRows = $this->getPlanProvisionRows();
        $historySection = $this->getHistorySection();
        $frequencyGroups = $this->getFrequencyGroups();
        $serviceHistoryRows = $this->getServiceHistoryRows();
        $closingSection = $this->getClosingSection();
        $controlOptions = $this->getTopControlOptions();
        $queueControlSnapshot = $this->getQueueControlSnapshot();
        $canManageQueueControl = $this->canManageQueueControl();
        $coreDynamicRows = $this->getDynamicQuestionsForSection('core_details');
        $coverageDynamicRows = $this->getDynamicQuestionsForSection('coverage_matrix');
        $planDynamicRows = $this->getDynamicQuestionsForSection('plan_provisions');
        $historyDynamicRows = $this->getDynamicQuestionsForSection('history');
        $serviceHistoryDynamicRows = $this->getDynamicQuestionsForSection('service_history');
        $verificationDynamicRows = $this->getDynamicQuestionsForSection('verification_information');
        $activityTimeline = $this->getActivityTimeline(6);
        $attachments = $this->getAttachmentCards();
        $statusButtons = collect($this->getStatusActionButtons())->filter(fn (array $button): bool => $button['visible'])->values()->all();
        $canSubmitForm = method_exists($this, 'canSubmitForm') ? $this->canSubmitForm() : true;
        $showStatusButtons = count($statusButtons) > 0;
        $showInfoRequestField = collect($statusButtons)->contains(fn (array $button): bool => ($button['target'] ?? null) === \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
            || $record->normalized_status === \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE;
        $showReworkReasonField = collect($statusButtons)->contains(fn (array $button): bool => ($button['target'] ?? null) === \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK)
            || $record->normalized_status === \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK;
        $showClinicResponseCard = ! $canManageQueueControl
            && $canSubmitForm
            && $record->normalized_status === \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE;
        $frequencyDynamicGroups = [
            'frequency_diagnostic_preventative' => $this->getDynamicQuestionsForSection('frequency_diagnostic_preventative'),
            'frequency_basic' => $this->getDynamicQuestionsForSection('frequency_basic'),
            'frequency_major' => $this->getDynamicQuestionsForSection('frequency_major'),
            'frequency_orthodontics_benefit' => $this->getDynamicQuestionsForSection('frequency_orthodontics_benefit'),
        ];
        $canViewSubmissionSnapshots = $this->canViewSubmissionSnapshots();
        $selectedSubmissionSnapshot = $this->selectedSubmissionSnapshot;
        $actionToneStyles = [
            'primary' => 'border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8;',
            'warning' => 'border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c;',
            'info' => 'border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8;',
            'danger' => 'border: 1px solid #fecdd3; background: #fff1f2; color: #be123c;',
            'purple' => 'border: 1px solid #ddd6fe; background: #f5f3ff; color: #7c3aed;',
            'success' => 'border: 1px solid #bbf7d0; background: #ecfdf5; color: #15803d;',
        ];

        $toneStyles = [
            'slate' => 'border: 1px solid #d8dee8; background: #f8fafc; color: #334155;',
            'sky' => 'border: 1px solid #bae6fd; background: #eff6ff; color: #0369a1;',
            'amber' => 'border: 1px solid #fed7aa; background: #fff7ed; color: #b45309;',
            'rose' => 'border: 1px solid #fecdd3; background: #fff1f2; color: #be123c;',
            'emerald' => 'border: 1px solid #bbf7d0; background: #ecfdf5; color: #15803d;',
            'indigo' => 'border: 1px solid #c7d2fe; background: #eef2ff; color: #4338ca;',
            'cyan' => 'border: 1px solid #a5f3fc; background: #ecfeff; color: #0f766e;',
            'violet' => 'border: 1px solid #ddd6fe; background: #f5f3ff; color: #7c3aed;',
        ];
        $timelineDotColors = [
            'slate' => '#94a3b8',
            'sky' => '#0ea5e9',
            'amber' => '#f59e0b',
            'rose' => '#e11d48',
            'emerald' => '#10b981',
            'indigo' => '#6366f1',
            'cyan' => '#06b6d4',
            'violet' => '#8b5cf6',
        ];

        $inputStyle = 'width: 100%; min-height: 42px; padding: 10px 12px; border: 1px solid #d6dde8; border-radius: 10px; background: #ffffff; color: #0f172a; font-size: 13px; line-height: 1.4;';
        $textareaStyle = 'width: 100%; min-height: 78px; padding: 10px 12px; border: 1px solid #d6dde8; border-radius: 10px; background: #ffffff; color: #0f172a; font-size: 13px; line-height: 1.5; resize: vertical;';
        $selectStyle = 'width: 100%; min-height: 42px; padding: 10px 12px; border: 1px solid #d6dde8; border-radius: 10px; background: #ffffff; color: #0f172a; font-size: 13px; line-height: 1.4;';
        $sectionBarStyle = 'background: linear-gradient(90deg, #eff6ff 0%, #f8fafc 100%); color: #0f172a; border-bottom: 1px solid #dbeafe;';
        $sectionBarTitleStyle = 'padding: 10px 18px; font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; text-align: center;';
        $sectionHeaderCellStyle = 'padding: 12px 16px; border-bottom: 1px solid #dbeafe; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #475569;';
        $quickReferenceCopyText = implode("\n", array_filter([
            'Patient: ' . ($quickReference['patient'] ?? ''),
            'DOB: ' . ($quickReference['dob'] ?? ''),
            'Member ID: ' . ($quickReference['member_id'] ?? ''),
            'Insurance: ' . ($quickReference['insurance_name'] ?? ''),
            'Coverage Status: ' . ($quickReference['coverage_role'] ?? ''),
            'Insurance Phone: ' . ($quickReference['phone'] ?? ''),
            'Provider NPI: ' . ($quickReference['provider_npi'] ?? ''),
            'Practice NPI: ' . ($quickReference['practice_npi'] ?? ''),
        ]));
    @endphp

    <style>
        .verification-workbench-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 8px 0 2px;
        }

        .verification-workbench-header__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .verification-workbench-layout {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }

        .verification-workbench-sidebar {
            display: flex;
            flex-direction: column;
            gap: 18px;
            position: sticky;
            top: 24px;
        }

        .verification-workbench-copy {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 12px;
            border-radius: 999px;
            border: 1px solid #dbe4ee;
            background: #ffffff;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }

        @media (max-width: 1280px) {
            .verification-workbench-layout {
                grid-template-columns: minmax(280px, 320px) minmax(0, 1fr);
            }
        }

        @media (max-width: 1120px) {
            .verification-workbench-header {
                flex-direction: column;
            }

            .verification-workbench-header__actions {
                justify-content: flex-start;
            }

            .verification-workbench-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .verification-workbench-sidebar {
                position: static;
            }
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 22px;">
        <section class="verification-workbench-header">
            <div>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px;">
                    <span style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #ffffff; border: 1px solid #dbe4ee; color: #334155; font-size: 12px; font-weight: 700;">
                        {{ $record->reference_number }}
                    </span>
                    <span style="font-size: 13px; font-weight: 600; color: #64748b;">
                        {{ $record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?? 'Verification Request') }}
                    </span>
                </div>
                <p style="margin: 0; max-width: 880px; font-size: 15px; line-height: 1.7; color: #64748b;">
                    {{ $this->getFormDescription() }}
                </p>
            </div>

            <div class="verification-workbench-header__actions">
                @if ($showStatusButtons)
                    @foreach ($statusButtons as $button)
                        <button
                            type="button"
                            wire:click="{{ $button['action'] ?? (filled($button['target'] ?? null) ? "saveAndTransition('{$button['target']}')" : '') }}"
                            style="display: inline-flex; align-items: center; justify-content: center; min-width: 144px; padding: 11px 16px; border-radius: 14px; font-size: 13px; font-weight: 800; cursor: pointer; {{ $actionToneStyles[$button['tone']] ?? $actionToneStyles['info'] }}"
                        >
                            {{ $button['label'] }}
                        </button>
                    @endforeach
                @endif
                @if ($canSubmitForm)
                    <button type="submit" style="display: inline-flex; align-items: center; justify-content: center; min-width: 148px; padding: 11px 18px; border: 0; border-radius: 14px; background: linear-gradient(135deg, #0f766e 0%, #0ea5a4 100%); color: #ffffff; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 10px 22px rgba(15, 118, 110, 0.22);">
                        {{ $this->getSaveButtonLabel() }}
                    </button>
                @endif
                <a href="{{ $this->getPdfDownloadUrl() }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none;">
                    Download PDF
                </a>
                <a href="{{ $this->getPdfPreviewUrl() }}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none;">
                    View PDF
                </a>
                <a href="{{ $this->getViewUrl() }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none;">
                    {{ $this->getViewButtonLabel() }}
                </a>
                <a href="{{ $this->getIndexUrl() }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none;">
                    {{ $this->getIndexButtonLabel() }}
                </a>
            </div>
        </section>

        @if ($errors->any())
            <section style="border: 1px solid #fecdd3; border-radius: 20px; background: #fff1f2; padding: 16px 18px;">
                <div style="margin-bottom: 10px; font-size: 13px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #be123c;">
                    Please review these items
                </div>
                <ul style="margin: 0; padding-left: 18px; color: #9f1239; font-size: 13px; line-height: 1.7;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <form wire:submit="save">
            <div class="verification-workbench-layout">
                <aside class="verification-workbench-sidebar">
                    <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <h3 style="margin: 0; font-size: 13px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #10b981;">
                                Quick Reference
                            </h3>
                            <button type="button" class="verification-workbench-copy" onclick="copyVerificationQuickReference(@js($quickReferenceCopyText), this)">Copy all</button>
                        </div>
                        <div style="padding: 16px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 14px;">
                            <div style="grid-column: 1 / -1;">
                                <div style="margin-bottom: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">Patient</div>
                                <div style="font-size: 14px; font-weight: 800; color: #111827;">{{ $quickReference['patient'] }}</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">DOB</div>
                                <div style="font-size: 13px; font-weight: 700; color: #111827;">{{ $quickReference['dob'] }}</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">Member ID</div>
                                <div style="font-size: 13px; font-weight: 700; color: #111827;">{{ $quickReference['member_id'] }}</div>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <div style="margin-bottom: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">Insurance</div>
                                <div style="font-size: 13px; font-weight: 700; color: #111827;">{{ $quickReference['insurance_name'] }}</div>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <div style="margin-bottom: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">Coverage Status</div>
                                <div style="font-size: 13px; font-weight: 700; color: #111827;">{{ $quickReference['coverage_role'] }}</div>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <div style="margin-bottom: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">Insurance Phone</div>
                                <div style="font-size: 13px; font-weight: 700; color: #111827;">{{ $quickReference['phone'] }}</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">Provider NPI</div>
                                <div style="font-size: 13px; font-weight: 700; color: #111827;">{{ $quickReference['provider_npi'] }}</div>
                            </div>
                            <div>
                                <div style="margin-bottom: 4px; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">Practice NPI</div>
                                <div style="font-size: 13px; font-weight: 700; color: #111827;">{{ $quickReference['practice_npi'] }}</div>
                            </div>
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #0f172a 0%, #13293d 100%); overflow: hidden; box-shadow: 0 16px 32px rgba(15, 23, 42, 0.16);">
                        <div style="padding: 18px 18px 0;">
                            <h3 style="margin: 0 0 12px; font-size: 12px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #a5f3fc;">
                                Queue Snapshot
                            </h3>
                        </div>
                        <div style="padding: 0 18px 18px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                            @foreach ($summaryCards as $card)
                                <div style="border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; background: rgba(255,255,255,0.06); padding: 12px;">
                                    <div style="margin-bottom: 8px; font-size: 10px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #cbd5e1;">
                                        {{ $card['label'] }}
                                    </div>
                                    <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; {{ $toneStyles[$card['tone']] ?? $toneStyles['slate'] }}">
                                        {{ $card['value'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h3 style="margin: 0; font-size: 13px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #10b981;">
                                Verification Progress
                            </h3>
                        </div>
                        <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                            @foreach ($sectionProgress as $section)
                                @php
                                    $percent = $section['total'] > 0 ? min(100, (int) round(($section['completed'] / $section['total']) * 100)) : 0;
                                @endphp
                                <div>
                                    <div style="display: flex; justify-content: space-between; gap: 12px; margin-bottom: 7px;">
                                        <div style="font-size: 13px; font-weight: 600; color: #0f172a;">{{ $section['label'] }}</div>
                                        <div style="font-size: 12px; font-weight: 700; color: #64748b;">{{ $section['completed'] }}/{{ $section['total'] }}</div>
                                    </div>
                                    <div style="height: 8px; border-radius: 999px; overflow: hidden; background: #e2e8f0;">
                                        <div style="height: 100%; width: {{ $percent }}%; background: linear-gradient(90deg, #14b8a6 0%, #22c55e 100%); border-radius: 999px;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #111827;">Context</h3>
                        </div>
                        <div style="padding: 16px; display: flex; flex-direction: column; gap: 14px;">
                            @foreach ($contextRows as $groupLabel => $rows)
                                <div style="border: 1px solid #e5e7eb; border-radius: 16px; background: #f8fafc; padding: 14px;">
                                    <div style="margin-bottom: 10px; font-size: 10px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #6b7280;">{{ str($groupLabel)->replace('_', ' ')->title() }}</div>
                                    <div style="display: flex; flex-direction: column; gap: 9px;">
                                        @foreach ($rows as $row)
                                            <div style="display: flex; justify-content: space-between; gap: 12px;">
                                                <div style="font-size: 12px; color: #64748b;">{{ $row['label'] }}</div>
                                                <div style="max-width: 58%; text-align: right; font-size: 12px; font-weight: 700; color: #111827;">{{ $row['value'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    @if ($showInfoRequestField || $showReworkReasonField)
                        <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);">
                            <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                                <h3 style="margin: 0; font-size: 13px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #10b981;">
                                    Workflow Notes
                                </h3>
                            </div>
                            <div style="padding: 16px; display: flex; flex-direction: column; gap: 16px;">
                                @if ($showInfoRequestField)
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #64748b;">
                                            Information Request
                                        </label>
                                        <textarea
                                            wire:model.blur="data.info_request_reason"
                                            placeholder="Example: Please upload the updated insurance card and confirm the subscriber date of birth before verification can continue."
                                            style="{{ $textareaStyle }}"
                                        ></textarea>
                                        <div style="margin-top: 8px; font-size: 12px; line-height: 1.6; color: #64748b;">
                                            Use this when the clinic must provide missing information before verification can continue.
                                        </div>
                                        @error('data.info_request_reason')
                                            <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif

                                @if ($showReworkReasonField)
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #64748b;">
                                            Rework Reason
                                        </label>
                                        <textarea
                                            wire:model.blur="data.return_reason"
                                            placeholder="Example: Coverage percentage was applied to the wrong service category and needs to be corrected before closure."
                                            style="{{ $textareaStyle }}"
                                        ></textarea>
                                        <div style="margin-top: 8px; font-size: 12px; line-height: 1.6; color: #64748b;">
                                            Use this when the request is being returned for correction or quality rework.
                                        </div>
                                        @error('data.return_reason')
                                            <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif

                    @if ($showClinicResponseCard)
                        <section style="border: 1px solid #fde68a; border-radius: 24px; background: linear-gradient(180deg, #fffef7 0%, #fffbeb 100%); overflow: hidden; box-shadow: 0 8px 24px rgba(180, 83, 9, 0.08);">
                            <div style="padding: 18px 20px; border-bottom: 1px solid #fde68a;">
                                <h3 style="margin: 0; font-size: 13px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #b45309;">
                                    Clinic Response Needed
                                </h3>
                            </div>
                            <div style="padding: 16px; display: flex; flex-direction: column; gap: 14px;">
                                <div style="border: 1px solid #fed7aa; border-radius: 16px; background: #ffffff; padding: 14px;">
                                    <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #92400e;">
                                        Verification Team Request
                                    </div>
                                    <div style="font-size: 13px; line-height: 1.7; color: #7c2d12;">
                                        {{ $record->info_request_reason ?: 'The verification team requested additional clinic information before they can continue.' }}
                                    </div>
                                    <div style="margin-top: 10px; font-size: 12px; color: #9a3412;">
                                        Requested by {{ $record->infoRequestedBy?->name ?: 'Verification team' }}
                                        @if ($record->updated_at)
                                            on {{ $record->updated_at->format('M d, Y h:i A') }}
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #92400e;">
                                        Clinic Response Note
                                    </label>
                                    <textarea
                                        wire:model.blur="data.notes"
                                        placeholder="Explain what you updated or clarified for the verification team. Example: Uploaded the current insurance card and corrected the subscriber DOB."
                                        style="{{ $textareaStyle }}"
                                    ></textarea>
                                    <div style="margin-top: 8px; font-size: 12px; line-height: 1.6; color: #92400e;">
                                        This note will go back with the request when you click <strong>Respond to Request</strong>.
                                    </div>
                                    @error('data.notes')
                                        <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #92400e;">
                                        Supporting Attachments
                                    </label>
                                    <input
                                        type="file"
                                        wire:model="clinicResponseAttachments"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                        style="display: block; width: 100%; padding: 10px 12px; border: 1px dashed #f59e0b; border-radius: 14px; background: #ffffff; color: #7c2d12; font-size: 13px;"
                                    />
                                    <div style="margin-top: 8px; font-size: 12px; line-height: 1.6; color: #92400e;">
                                        Upload the insurance card, screenshot, or any supporting document that helps the verification team continue.
                                    </div>
                                    @error('clinicResponseAttachments.*')
                                        <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </section>
                    @endif

                    @if ($attachments->isNotEmpty() || $showClinicResponseCard)
                        <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);">
                            <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                                <h3 style="margin: 0; font-size: 13px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #7c3aed;">
                                    Supporting Attachments
                                </h3>
                            </div>
                            <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                                @forelse ($attachments as $attachment)
                                    <article style="border: 1px solid #e5e7eb; border-radius: 16px; background: #f8fafc; padding: 14px;">
                                        <div style="margin-bottom: 4px; font-size: 13px; font-weight: 800; color: #111827;">{{ $attachment['title'] }}</div>
                                        <div style="margin-bottom: 10px; font-size: 12px; line-height: 1.6; color: #64748b;">{{ $attachment['subtitle'] }}</div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                            <span style="font-size: 12px; color: #94a3b8;">{{ $attachment['uploaded_at'] }}</span>
                                            <a href="{{ $attachment['download_url'] }}" style="display: inline-flex; align-items: center; padding: 8px 12px; border-radius: 999px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none;">
                                                Download
                                            </a>
                                        </div>
                                    </article>
                                @empty
                                    <div style="font-size: 13px; line-height: 1.7; color: #64748b;">
                                        No supporting attachments have been added yet. Uploading files here will attach them to the verification timeline for the service team.
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    @endif

                    <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h3 style="margin: 0; font-size: 13px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #10b981;">
                                Workflow Timeline
                            </h3>
                        </div>
                        <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                            @forelse ($activityTimeline as $activity)
                                <article style="position: relative; padding-left: 18px;">
                                    <span style="position: absolute; left: 0; top: 8px; width: 9px; height: 9px; border-radius: 999px; background: {{ $timelineDotColors[$activity['tone']] ?? $timelineDotColors['cyan'] }};"></span>
                                    <div style="border: 1px solid #e5e7eb; border-radius: 18px; background: #f8fafc; padding: 14px;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
                                            <div style="font-size: 13px; font-weight: 800; color: #0f172a;">{{ $activity['type'] }}</div>
                                            <div style="font-size: 11px; color: #94a3b8;">{{ $activity['created_at'] }}</div>
                                        </div>
                                        <div style="font-size: 13px; line-height: 1.7; color: #334155;">{{ $activity['description'] }}</div>
                                        @if (filled($activity['details']))
                                            <div style="margin-top: 10px; border: 1px solid #e5e7eb; border-radius: 14px; background: #ffffff; padding: 12px; font-size: 12px; line-height: 1.7; color: #475569; white-space: pre-line;">
                                                {{ $activity['details'] }}
                                            </div>
                                        @endif
                                        @if ($canViewSubmissionSnapshots && filled($activity['submission_id']))
                                            <div style="margin-top: 10px;">
                                                <button
                                                    type="button"
                                                    wire:click="openSubmissionSnapshot({{ (int) $activity['submission_id'] }})"
                                                    style="display: inline-flex; align-items: center; padding: 8px 12px; border-radius: 999px; border: 1px solid #c7d2fe; background: #eef2ff; color: #4338ca; font-size: 12px; font-weight: 700; cursor: pointer;"
                                                >
                                                    View Snapshot
                                                </button>
                                            </div>
                                        @endif
                                        <div style="margin-top: 8px; font-size: 11px; font-weight: 700; color: #64748b;">{{ $activity['author'] }}</div>
                                    </div>
                                </article>
                            @empty
                                <div style="font-size: 13px; color: #64748b;">No workflow history has been logged yet.</div>
                            @endforelse
                        </div>
                    </section>
                </aside>

                <section style="display: flex; flex-direction: column; gap: 18px;">
                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                        <div style="padding: 0; {{ $sectionBarStyle }}">
                            <div style="{{ $sectionBarTitleStyle }}">{{ $coreDetails['title'] }}</div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tbody>
                                    @foreach ($coreDetails['rows'] as $row)
                                        @php $field = $row['field']; $type = $row['type']; @endphp
                                        <tr>
                                            <td style="width: 44%; padding: 12px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                @if ($type === 'date')
                                                    <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                @elseif ($type === 'currency')
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 42px; border: 1px solid #d6dde8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700;">$</span>
                                                        <input type="number" step="0.01" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                    </div>
                                                @else
                                                    <input type="text" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($coreDynamicRows as $row)
                                        @php $field = $row['field']; $type = $row['type']; @endphp
                                        <tr>
                                            <td style="width: 44%; padding: 12px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                                @if (filled($row['help_text']))
                                                    <div style="margin-top: 4px; font-size: 12px; line-height: 1.5; font-weight: 500; color: #94a3b8;">
                                                        {{ $row['help_text'] }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                @if ($type === 'date')
                                                    <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                @elseif ($type === 'currency')
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 42px; border: 1px solid #d6dde8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700;">$</span>
                                                        <input type="number" step="0.01" wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                    </div>
                                                @elseif ($type === 'textarea')
                                                    <textarea wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $textareaStyle }}"></textarea>
                                                @elseif ($type === 'yes_no')
                                                    <select wire:model.blur="data.{{ $field }}" style="{{ $selectStyle }}">
                                                        <option value="">Select</option>
                                                        <option value="Yes">Yes</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                @else
                                                    <input type="{{ in_array($type, ['number', 'percent'], true) ? 'number' : 'text' }}" @if ($type === 'percent') step="0.01" @endif wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                        <div style="padding: 0; {{ $sectionBarStyle }}">
                            <div style="{{ $sectionBarTitleStyle }}">{{ $coverageMatrix['title'] }}</div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8fafc;">
                                        <th style="{{ $sectionHeaderCellStyle }}">Category</th>
                                        <th style="width: 220px; {{ $sectionHeaderCellStyle }}">DED Applied? Yes/No</th>
                                        <th style="width: 220px; {{ $sectionHeaderCellStyle }}">Category %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($coverageMatrix['rows'] as $row)
                                        <tr>
                                            <td style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7;">
                                                <select wire:model.blur="data.{{ $row['deductible_field'] }}" style="{{ $selectStyle }}">
                                                    <option value="">Select</option>
                                                    <option value="Yes">Yes</option>
                                                    <option value="No">No</option>
                                                </select>
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7;">
                                                <input type="text" wire:model.blur="data.{{ $row['percent_field'] }}" placeholder="%" style="{{ $inputStyle }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($coverageDynamicRows as $row)
                                        @php $field = $row['field']; $type = $row['type']; @endphp
                                        <tr>
                                            <td style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                                @if (filled($row['help_text']))
                                                    <div style="margin-top: 4px; font-size: 12px; line-height: 1.5; font-weight: 500; color: #94a3b8;">
                                                        {{ $row['help_text'] }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td colspan="2" style="padding: 10px 16px; border-bottom: 1px solid #eef2f7;">
                                                @if ($type === 'textarea')
                                                    <textarea wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $textareaStyle }}"></textarea>
                                                @elseif ($type === 'date')
                                                    <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                @elseif ($type === 'yes_no')
                                                    <select wire:model.blur="data.{{ $field }}" style="{{ $selectStyle }}">
                                                        <option value="">Select</option>
                                                        <option value="Yes">Yes</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                @elseif ($type === 'currency')
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 42px; border: 1px solid #d6dde8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700;">$</span>
                                                        <input type="number" step="0.01" wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                    </div>
                                                @else
                                                    <input type="{{ in_array($type, ['number', 'percent'], true) ? 'number' : 'text' }}" @if ($type === 'percent') step="0.01" @endif wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                        <div style="padding: 0; {{ $sectionBarStyle }}">
                            <div style="{{ $sectionBarTitleStyle }}">{{ $planProvisionRows['title'] }}</div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tbody>
                                    @foreach ($planProvisionRows['rows'] as $row)
                                        @php $field = $row['field']; $type = $row['type']; @endphp
                                        <tr>
                                            <td style="width: 44%; padding: 12px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                @if ($type === 'textarea')
                                                    <textarea wire:model.blur="data.{{ $field }}" style="{{ $textareaStyle }}"></textarea>
                                                @else
                                                    <input type="text" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($planDynamicRows as $row)
                                        @php $field = $row['field']; $type = $row['type']; @endphp
                                        <tr>
                                            <td style="width: 44%; padding: 12px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                                @if (filled($row['help_text']))
                                                    <div style="margin-top: 4px; font-size: 12px; line-height: 1.5; font-weight: 500; color: #94a3b8;">
                                                        {{ $row['help_text'] }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                @if ($type === 'textarea')
                                                    <textarea wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $textareaStyle }}"></textarea>
                                                @elseif ($type === 'date')
                                                    <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                @elseif ($type === 'yes_no')
                                                    <select wire:model.blur="data.{{ $field }}" style="{{ $selectStyle }}">
                                                        <option value="">Select</option>
                                                        <option value="Yes">Yes</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                @elseif ($type === 'currency')
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 42px; border: 1px solid #d6dde8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700;">$</span>
                                                        <input type="number" step="0.01" wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                    </div>
                                                @else
                                                    <input type="{{ in_array($type, ['number', 'percent'], true) ? 'number' : 'text' }}" @if ($type === 'percent') step="0.01" @endif wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                        <div style="padding: 0; {{ $sectionBarStyle }}">
                            <div style="{{ $sectionBarTitleStyle }}">{{ $historySection['title'] }}</div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tbody>
                                    @foreach ($historySection['rows'] as $row)
                                        @php $field = $row['field']; $type = $row['type']; @endphp
                                        <tr>
                                            <td style="width: 44%; padding: 12px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                @if ($type === 'textarea')
                                                    <textarea wire:model.blur="data.{{ $field }}" style="{{ $textareaStyle }}"></textarea>
                                                @else
                                                    <input type="text" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($historyDynamicRows as $row)
                                        @php $field = $row['field']; $type = $row['type']; @endphp
                                        <tr>
                                            <td style="width: 44%; padding: 12px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                                @if (filled($row['help_text']))
                                                    <div style="margin-top: 4px; font-size: 12px; line-height: 1.5; font-weight: 500; color: #94a3b8;">
                                                        {{ $row['help_text'] }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                @if ($type === 'textarea')
                                                    <textarea wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $textareaStyle }}"></textarea>
                                                @elseif ($type === 'date')
                                                    <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                @elseif ($type === 'yes_no')
                                                    <select wire:model.blur="data.{{ $field }}" style="{{ $selectStyle }}">
                                                        <option value="">Select</option>
                                                        <option value="Yes">Yes</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                @elseif ($type === 'currency')
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 42px; border: 1px solid #d6dde8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700;">$</span>
                                                        <input type="number" step="0.01" wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                    </div>
                                                @else
                                                    <input type="{{ in_array($type, ['number', 'percent'], true) ? 'number' : 'text' }}" @if ($type === 'percent') step="0.01" @endif wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                            <div style="padding: 0; {{ $sectionBarStyle }}">
                                <div style="{{ $sectionBarTitleStyle }}">Frequency and Percentage</div>
                            </div>
                        </div>
                        @foreach ($frequencyGroups as $group)
                            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                                <div style="padding: 0; {{ $sectionBarStyle }}">
                                    <div style="{{ $sectionBarTitleStyle }}">{{ $group['title'] }}</div>
                                </div>
                                <div style="overflow-x: auto;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tbody>
                                            @foreach ($group['rows'] as $row)
                                                <tr>
                                                    <td style="width: 44%; padding: 12px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                        {{ $row['label'] }}
                                                    </td>
                                                    <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                        <input type="text" wire:model.blur="data.{{ $row['field'] }}" style="{{ $inputStyle }}">
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @foreach ($frequencyDynamicGroups[match ($group['title']) {
                                                'Diagnostic & Preventative' => 'frequency_diagnostic_preventative',
                                                'Basic' => 'frequency_basic',
                                                'Major' => 'frequency_major',
                                                'Orthodontics Benefit' => 'frequency_orthodontics_benefit',
                                            }] as $row)
                                                @php $field = $row['field']; $type = $row['type']; @endphp
                                                <tr>
                                                    <td style="width: 44%; padding: 12px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                        {{ $row['label'] }}
                                                        @if (filled($row['help_text']))
                                                            <div style="margin-top: 4px; font-size: 12px; line-height: 1.5; font-weight: 500; color: #94a3b8;">
                                                                {{ $row['help_text'] }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                        @if ($type === 'textarea')
                                                            <textarea wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $textareaStyle }}"></textarea>
                                                        @elseif ($type === 'date')
                                                            <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                        @elseif ($type === 'yes_no')
                                                            <select wire:model.blur="data.{{ $field }}" style="{{ $selectStyle }}">
                                                                <option value="">Select</option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        @elseif ($type === 'currency')
                                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 42px; border: 1px solid #d6dde8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700;">$</span>
                                                                <input type="number" step="0.01" wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                            </div>
                                                        @else
                                                            <input type="{{ in_array($type, ['number', 'percent'], true) ? 'number' : 'text' }}" @if ($type === 'percent') step="0.01" @endif wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endforeach
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                        <div style="padding: 16px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 10px; height: 10px; border-radius: 999px; background: #10b981;"></span>
                                <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Service History</h3>
                            </div>
                            <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #dbe4ee; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700;">
                                {{ collect($serviceHistoryRows)->filter(fn ($row) => filled(data_get($this->data, $row['field'])))->count() }}/{{ count($serviceHistoryRows) }} services filled
                            </span>
                        </div>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8fafc;">
                                        <th style="width: 140px; padding: 12px 16px; border-bottom: 1px solid #edf2f7; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Code</th>
                                        <th style="padding: 12px 16px; border-bottom: 1px solid #edf2f7; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Service</th>
                                        <th style="width: 260px; padding: 12px 16px; border-bottom: 1px solid #edf2f7; text-align: left; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Service Dates / Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($serviceHistoryRows as $row)
                                        <tr>
                                            <td style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 13px; font-weight: 800; color: #0f766e;">
                                                {{ $row['code'] }}
                                            </td>
                                            <td style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                <input type="text" wire:model.blur="data.{{ $row['field'] }}" placeholder="MM/DD/YYYY or note" style="{{ $inputStyle }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($serviceHistoryDynamicRows as $row)
                                        <tr>
                                            <td style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 13px; font-weight: 800; color: #0f766e;">Custom</td>
                                            <td style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top; font-size: 14px; font-weight: 600; color: #0f172a;">
                                                {{ $row['label'] }}
                                            </td>
                                            <td style="padding: 10px 16px; border-bottom: 1px solid #eef2f7; vertical-align: top;">
                                                @if ($row['type'] === 'textarea')
                                                    <textarea wire:model.blur="data.{{ $row['field'] }}" placeholder="{{ $row['placeholder'] }}" style="{{ $textareaStyle }}"></textarea>
                                                @else
                                                    <input type="text" wire:model.blur="data.{{ $row['field'] }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                        <div style="padding: 16px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 10px; height: 10px; border-radius: 999px; background: #10b981;"></span>
                                <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">{{ $closingSection['title'] }}</h3>
                            </div>
                            <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #dbe4ee; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700;">
                                {{ $closingSection['completed'] }}/{{ $closingSection['total'] }} answered
                            </span>
                        </div>
                        <div style="padding: 18px 20px 20px;">
                            <p style="margin: 0 0 18px; font-size: 14px; line-height: 1.7; color: #64748b;">
                                {{ $closingSection['description'] }}
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 20px;">
                                @foreach ($closingSection['rows'] as $row)
                                    @php
                                        $field = $row['field'];
                                        $type = $row['type'];
                                        $isFullWidth = $type === 'textarea';
                                    @endphp
                                    <div style="{{ $isFullWidth ? 'grid-column: 1 / -1;' : '' }}">
                                        <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #64748b;">
                                            {{ $row['label'] }}
                                        </label>
                                        @if ($type === 'textarea')
                                            <textarea wire:model.blur="data.{{ $field }}" style="{{ $textareaStyle }}"></textarea>
                                        @elseif ($type === 'date')
                                            <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                        @else
                                            <input type="text" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                        @endif
                                    </div>
                                @endforeach
                                @foreach ($verificationDynamicRows as $row)
                                    @php
                                        $field = $row['field'];
                                        $type = $row['type'];
                                        $isFullWidth = $type === 'textarea';
                                    @endphp
                                    <div style="{{ $isFullWidth ? 'grid-column: 1 / -1;' : '' }}">
                                        <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #64748b;">
                                            {{ $row['label'] }}
                                        </label>
                                        @if (filled($row['help_text']))
                                            <div style="margin: -2px 0 8px; font-size: 12px; line-height: 1.5; color: #94a3b8;">
                                                {{ $row['help_text'] }}
                                            </div>
                                        @endif
                                        @if ($type === 'textarea')
                                            <textarea wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $textareaStyle }}"></textarea>
                                        @elseif ($type === 'date')
                                            <input type="date" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                        @elseif ($type === 'yes_no')
                                            <select wire:model.blur="data.{{ $field }}" style="{{ $selectStyle }}">
                                                <option value="">Select</option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        @elseif ($type === 'currency')
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; min-height: 42px; border: 1px solid #d6dde8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700;">$</span>
                                                <input type="number" step="0.01" wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                            </div>
                                        @else
                                            <input type="{{ in_array($type, ['number', 'percent'], true) ? 'number' : 'text' }}" @if ($type === 'percent') step="0.01" @endif wire:model.blur="data.{{ $field }}" placeholder="{{ $row['placeholder'] }}" style="{{ $inputStyle }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <div style="display: flex; justify-content: flex-end; gap: 12px; padding-bottom: 6px;">
                        <a href="{{ $this->getViewUrl() }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 128px; padding: 12px 18px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none;">
                            {{ $this->getCancelButtonLabel() }}
                        </a>
                        @if ($showStatusButtons)
                        @foreach ($statusButtons as $button)
                            <button
                                type="button"
                                wire:click="{{ $button['action'] ?? (filled($button['target'] ?? null) ? "saveAndTransition('{$button['target']}')" : '') }}"
                                style="display: inline-flex; align-items: center; justify-content: center; min-width: 148px; padding: 12px 18px; border-radius: 14px; font-size: 13px; font-weight: 800; cursor: pointer; {{ $actionToneStyles[$button['tone']] ?? $actionToneStyles['info'] }}"
                            >
                                {{ $button['label'] }}
                            </button>
                        @endforeach
                        @endif
                        @if ($canSubmitForm)
                            <button type="submit" style="display: inline-flex; align-items: center; justify-content: center; min-width: 160px; padding: 12px 18px; border: 0; border-radius: 14px; background: linear-gradient(135deg, #0f766e 0%, #0ea5a4 100%); color: #ffffff; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 10px 22px rgba(15, 118, 110, 0.22);">
                                {{ $this->getSaveButtonLabel() }}
                            </button>
                        @endif
                    </div>
                </section>
            </div>
        </form>
    </div>

    @if ($showSubmissionSnapshotModal && filled($selectedSubmissionSnapshot))
        <div style="position: fixed; inset: 0; z-index: 90; background: rgba(15, 23, 42, 0.56); display: flex; align-items: center; justify-content: center; padding: 28px;">
            <div style="width: min(1080px, 100%); max-height: 88vh; overflow: auto; border-radius: 28px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 28px 64px rgba(15, 23, 42, 0.28);">
                <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #4f46e5;">Form Submission Snapshot</div>
                        <h3 style="margin: 0; font-size: 28px; line-height: 1.15; font-weight: 700; color: #0f172a;">Saved Verification Payload</h3>
                        <p style="margin: 12px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Review the exact form data that was saved at this point in the workflow, including the work item state, verification profile, and captured answers.
                        </p>
                    </div>
                    <button type="button" wire:click="closeSubmissionSnapshot" style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                    </button>
                </div>

                <div style="padding: 22px 24px; display: flex; flex-direction: column; gap: 20px;">
                    <section style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;">
                        @foreach ([
                            'Version' => filled($selectedSubmissionSnapshot['headline']['version'] ?? null) ? 'v' . $selectedSubmissionSnapshot['headline']['version'] : '-',
                            'Submitted At' => $selectedSubmissionSnapshot['headline']['submitted_at'] ?? '-',
                            'Submitted By' => $selectedSubmissionSnapshot['headline']['submitted_by'] ?? '-',
                            'Source Panel' => $selectedSubmissionSnapshot['headline']['panel'] ?? '-',
                            'Status' => $selectedSubmissionSnapshot['headline']['status'] ?? '-',
                            'Outcome' => $selectedSubmissionSnapshot['headline']['outcome'] ?? '-',
                            'Priority' => $selectedSubmissionSnapshot['headline']['priority'] ?? '-',
                        ] as $label => $value)
                            <div style="border: 1px solid #e5e7eb; border-radius: 18px; background: #f8fafc; padding: 14px 16px;">
                                <div style="margin-bottom: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">{{ $label }}</div>
                                <div style="font-size: 14px; font-weight: 700; color: #111827; line-height: 1.6;">{{ $value }}</div>
                            </div>
                        @endforeach
                    </section>

                    <section style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; overflow: hidden;">
                        <div style="padding: 16px 18px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <h4 style="margin: 0; font-size: 17px; font-weight: 700; color: #111827;">Changes From Previous Submission</h4>
                            <span style="font-size: 12px; font-weight: 700; color: #64748b;">{{ count($selectedSubmissionSnapshot['changes'] ?? []) }} differences</span>
                        </div>
                        <div style="padding: 16px 18px; display: flex; flex-direction: column; gap: 12px;">
                            @forelse ($selectedSubmissionSnapshot['changes'] ?? [] as $change)
                                <div style="border: 1px solid #e5e7eb; border-radius: 16px; background: #f8fafc; padding: 14px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px;">
                                        <div style="font-size: 13px; font-weight: 700; color: #111827;">{{ $change['label'] }}</div>
                                        <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                                            {{ $change['group'] ?? 'Verification Audit' }}
                                        </span>
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                                        <div style="border: 1px solid #fecdd3; border-radius: 14px; background: #fff1f2; padding: 12px;">
                                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #be123c;">Previous</div>
                                            <div style="font-size: 13px; line-height: 1.65; color: #334155; white-space: pre-line;">{{ $change['before'] }}</div>
                                        </div>
                                        <div style="border: 1px solid #bbf7d0; border-radius: 14px; background: #ecfdf5; padding: 12px;">
                                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #15803d;">Current</div>
                                            <div style="font-size: 13px; line-height: 1.65; color: #334155; white-space: pre-line;">{{ $change['after'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div style="font-size: 13px; color: #64748b;">This is the first saved submission for this request, so there is no previous version to compare.</div>
                            @endforelse
                        </div>
                    </section>

                    @foreach ([
                        'Submission Summary' => $selectedSubmissionSnapshot['summary'] ?? [],
                        'Queue Snapshot' => $selectedSubmissionSnapshot['work_item'] ?? [],
                        'Verification Profile' => $selectedSubmissionSnapshot['verification_profile'] ?? [],
                    ] as $sectionTitle => $rows)
                        <section style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; overflow: hidden;">
                            <div style="padding: 16px 18px; border-bottom: 1px solid #edf2f7;">
                                <h4 style="margin: 0; font-size: 17px; font-weight: 700; color: #111827;">{{ $sectionTitle }}</h4>
                            </div>
                            <div style="padding: 16px 18px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 14px;">
                                @forelse ($rows as $row)
                                    <div style="border: 1px solid #e5e7eb; border-radius: 16px; background: #f8fafc; padding: 12px 14px;">
                                        <div style="margin-bottom: 5px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">{{ $row['label'] }}</div>
                                        <div style="font-size: 13px; line-height: 1.65; color: #334155; white-space: pre-line;">{{ $row['value'] }}</div>
                                    </div>
                                @empty
                                    <div style="grid-column: 1 / -1; font-size: 13px; color: #64748b;">No saved values were captured for this section.</div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach

                    <section style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; overflow: hidden;">
                        <div style="padding: 16px 18px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <h4 style="margin: 0; font-size: 17px; font-weight: 700; color: #111827;">Captured Answers</h4>
                            <span style="font-size: 12px; font-weight: 700; color: #64748b;">{{ count($selectedSubmissionSnapshot['answers'] ?? []) }} saved</span>
                        </div>
                        <div style="padding: 16px 18px; display: flex; flex-direction: column; gap: 12px;">
                            @forelse ($selectedSubmissionSnapshot['answers'] ?? [] as $answer)
                                <div style="border: 1px solid #e5e7eb; border-radius: 16px; background: #f8fafc; padding: 14px;">
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
                                        <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $answer['prompt'] }}</div>
                                        <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                                            {{ $answer['code'] }}
                                        </span>
                                    </div>
                                    <div style="font-size: 13px; line-height: 1.7; color: #334155; white-space: pre-line;">{{ $answer['value'] }}</div>
                                </div>
                            @empty
                                <div style="font-size: 13px; color: #64748b;">No dynamic answers were stored for this submission.</div>
                            @endforelse
                        </div>
                    </section>

                    <section style="border: 1px solid #e5e7eb; border-radius: 22px; background: #ffffff; overflow: hidden;">
                        <div style="padding: 16px 18px; border-bottom: 1px solid #edf2f7;">
                            <h4 style="margin: 0; font-size: 17px; font-weight: 700; color: #111827;">Exact Payload</h4>
                        </div>
                        <div style="padding: 16px 18px;">
                            <pre style="margin: 0; padding: 16px; border-radius: 18px; background: #0f172a; color: #e2e8f0; font-size: 12px; line-height: 1.7; overflow: auto; white-space: pre-wrap;">{{ $selectedSubmissionSnapshot['raw_payload'] ?? '{}' }}</pre>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    @endif

    <script>
        async function copyVerificationQuickReference(text, button) {
            if (!text) return;

            await navigator.clipboard.writeText(text);

            if (!button) return;

            const original = button.textContent;
            button.textContent = 'Copied';

            setTimeout(() => {
                button.textContent = original;
            }, 1200);
        }
    </script>
</x-filament-panels::page>
