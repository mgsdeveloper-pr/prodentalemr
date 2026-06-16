<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $summaryCards = $this->getWorkbenchSummary();
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
        $feeScheduleReference = $this->getFeeScheduleReference();
        $canSubmitForm = method_exists($this, 'canSubmitForm') ? $this->canSubmitForm() : true;
        $canRequestClinicInfo = $this->canRequestClinicInfo();
        $showInfoRequestField = $canRequestClinicInfo
            || $record->normalized_status === \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE;
        $showReworkReasonField = $record->normalized_status === \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK;
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
                @if ($canSubmitForm)
                    @if ($this->auditReady)
                        <button type="button" wire:click="save" style="display: inline-flex; align-items: center; justify-content: center; min-width: 148px; padding: 11px 18px; border: 0; border-radius: 14px; background: linear-gradient(135deg, #0f766e 0%, #0ea5a4 100%); color: #ffffff; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 10px 22px rgba(15, 118, 110, 0.22);">
                            {{ $this->getSaveButtonLabel() }}
                        </button>
                    @else
                        <button type="button" wire:click="auditVerification" style="display: inline-flex; align-items: center; justify-content: center; min-width: 148px; padding: 11px 18px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 800; cursor: pointer;">
                            {{ $this->getSaveButtonLabel() }}
                        </button>
                    @endif
                    @if ($canRequestClinicInfo)
                        <button type="button" onclick="openWorkflowModal('info-request-modal')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 164px; padding: 11px 16px; border-radius: 14px; border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c; font-size: 13px; font-weight: 800; cursor: pointer;">
                            Request to Clinic
                        </button>
                    @endif
                    <button type="button" wire:click="saveAndBack" style="display: inline-flex; align-items: center; justify-content: center; min-width: 144px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; cursor: pointer;">
                        Back
                    </button>
                    <button type="button" onclick="if (! confirm('Clear the verification answers and reset this form?')) return false;" wire:click="clearVerificationForm" style="display: inline-flex; align-items: center; justify-content: center; min-width: 144px; padding: 11px 16px; border-radius: 14px; border: 1px solid #fecdd3; background: #fff1f2; color: #be123c; font-size: 13px; font-weight: 800; cursor: pointer;">
                        Clear Form
                    </button>
                @else
                    <a href="{{ $this->getIndexUrl() }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none;">
                        Back
                    </a>
                @endif
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
                                                @elseif ($field === 'vf_fee_schedule')
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <input type="text" wire:model.blur="data.{{ $field }}" style="{{ $inputStyle }}">
                                                        @if (filled($feeScheduleReference['url'] ?? null))
                                                            @php
                                                                $feeScheduleReferencePayload = json_encode([
                                                                    'url' => $feeScheduleReference['url'],
                                                                    'name' => $feeScheduleReference['name'],
                                                                    'label' => 'Fee Schedule Reference',
                                                                    'description' => 'Review the current fee schedule reference without leaving the verification workflow.',
                                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                                                            @endphp
                                                            <button
                                                                type="button"
                                                                onclick='openReferenceViewerModal({!! $feeScheduleReferencePayload !!})'
                                                                title="{{ $feeScheduleReference['name'] }}"
                                                                style="display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 14px; border: 1px solid #c7d2fe; background: #ffffff; color: #4338ca; font-size: 18px; cursor: pointer;"
                                                            >
                                                                &#9432;
                                                            </button>
                                                        @else
                                                            <button
                                                                type="button"
                                                                title="No fee schedule reference added"
                                                                disabled
                                                                style="display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 14px; border: 1px solid #dbe4ee; background: #f8fafc; color: #94a3b8; font-size: 18px; cursor: not-allowed; opacity: 0.9;"
                                                            >
                                                                &#9432;
                                                            </button>
                                                        @endif
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
                        @if ($canSubmitForm)
                            @if ($this->auditReady)
                                <button type="submit" style="display: inline-flex; align-items: center; justify-content: center; min-width: 160px; padding: 12px 18px; border: 0; border-radius: 14px; background: linear-gradient(135deg, #0f766e 0%, #0ea5a4 100%); color: #ffffff; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 10px 22px rgba(15, 118, 110, 0.22);">
                                    {{ $this->getSaveButtonLabel() }}
                                </button>
                            @else
                                <button type="button" wire:click="auditVerification" style="display: inline-flex; align-items: center; justify-content: center; min-width: 160px; padding: 12px 18px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 800; cursor: pointer;">
                                    {{ $this->getSaveButtonLabel() }}
                                </button>
                            @endif
                            @if ($canRequestClinicInfo)
                                <button type="button" onclick="openWorkflowModal('info-request-modal')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 172px; padding: 12px 18px; border-radius: 14px; border: 1px solid #fed7aa; background: #fff7ed; color: #c2410c; font-size: 13px; font-weight: 800; cursor: pointer;">
                                    Request to Clinic
                                </button>
                            @endif
                            <button type="button" wire:click="saveAndBack" style="display: inline-flex; align-items: center; justify-content: center; min-width: 148px; padding: 12px 18px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; cursor: pointer;">
                                Back
                            </button>
                            <button type="button" onclick="if (! confirm('Clear the verification answers and reset this form?')) return false;" wire:click="clearVerificationForm" style="display: inline-flex; align-items: center; justify-content: center; min-width: 148px; padding: 12px 18px; border-radius: 14px; border: 1px solid #fecdd3; background: #fff1f2; color: #be123c; font-size: 13px; font-weight: 800; cursor: pointer;">
                                Clear Form
                            </button>
                        @else
                            <a href="{{ $this->getIndexUrl() }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 18px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none;">
                                Back
                            </a>
                        @endif
                    </div>
                </section>
            </div>
        </form>
    </div>

    @if ($showInfoRequestField)
        <div id="info-request-modal" style="position: fixed; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center; padding: 28px; background: rgba(15, 23, 42, 0.62);">
            <div style="width: min(720px, 100%); border-radius: 28px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 28px 64px rgba(15, 23, 42, 0.28); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #10b981;">Workflow Notes</div>
                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Send Request to Clinic</h3>
                        <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Explain exactly what the clinic must provide before verification can continue.
                        </p>
                    </div>
                    <button type="button" onclick="closeWorkflowModal('info-request-modal')" style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                    <textarea
                        wire:model.live="data.info_request_reason"
                        placeholder="Example: Please upload the updated insurance card and confirm the subscriber date of birth before verification can continue."
                        style="{{ $textareaStyle }} min-height: 150px;"
                    ></textarea>
                    <div style="font-size: 12px; line-height: 1.6; color: #64748b;">
                        Use this when the clinic must provide missing information before verification can continue.
                    </div>
                    @error('data.info_request_reason')
                        <div style="font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                    @enderror
                </div>
                <div style="padding: 18px 22px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" onclick="closeWorkflowModal('info-request-modal')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 132px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 13px; font-weight: 800; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveAndTransition('{{ \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE }}')" onclick="closeWorkflowModal('info-request-modal')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 180px; padding: 11px 16px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 800; cursor: pointer;">
                        Send Request to Clinic
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showReworkReasonField)
        <div id="rework-reason-modal" style="position: fixed; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center; padding: 28px; background: rgba(15, 23, 42, 0.62);">
            <div style="width: min(720px, 100%); border-radius: 28px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 28px 64px rgba(15, 23, 42, 0.28); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #10b981;">Workflow Notes</div>
                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Return Request for Rework</h3>
                        <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Describe the correction or quality issue before returning this request for rework.
                        </p>
                    </div>
                    <button type="button" onclick="closeWorkflowModal('rework-reason-modal')" style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                    <textarea
                        wire:model.live="data.return_reason"
                        placeholder="Example: Coverage percentage was applied to the wrong service category and needs to be corrected before closure."
                        style="{{ $textareaStyle }} min-height: 150px;"
                    ></textarea>
                    <div style="font-size: 12px; line-height: 1.6; color: #64748b;">
                        Use this when the request is being returned for correction or quality rework.
                    </div>
                    @error('data.return_reason')
                        <div style="font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                    @enderror
                </div>
                <div style="padding: 18px 22px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" onclick="closeWorkflowModal('rework-reason-modal')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 132px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 13px; font-weight: 800; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveAndTransition('{{ \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK }}')" onclick="closeWorkflowModal('rework-reason-modal')" style="display: inline-flex; align-items: center; justify-content: center; min-width: 172px; padding: 11px 16px; border-radius: 14px; border: 1px solid #fecdd3; background: #fff1f2; color: #be123c; font-size: 13px; font-weight: 800; cursor: pointer;">
                        Return Request for Rework
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($this->openInfoRequestModalOnLoad && $showInfoRequestField)
        <script>
            (function () {
                const openRequestModal = function () {
                    openWorkflowModal('info-request-modal');

                    const url = new URL(window.location.href);
                    url.searchParams.delete('request_clinic');
                    window.history.replaceState({}, document.title, url.toString());
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', openRequestModal, { once: true });
                } else {
                    setTimeout(openRequestModal, 60);
                }

                document.addEventListener('livewire:navigated', function handleNavigation() {
                    openRequestModal();
                    document.removeEventListener('livewire:navigated', handleNavigation);
                });
            })();
        </script>
    @endif

    <div id="reference-viewer-modal" style="position: fixed; inset: 0; z-index: 85; display: none; align-items: center; justify-content: center; padding: 28px; background: rgba(15, 23, 42, 0.68);">
        <div style="position: relative; width: min(1080px, 100%); max-height: 88vh; border-radius: 24px; overflow: hidden; background: #ffffff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28); display: flex; flex-direction: column;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 22px; border-bottom: 1px solid #e2e8f0;">
                <div>
                    <div id="reference-viewer-label" style="font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #64748b;">Reference</div>
                    <div id="reference-viewer-name" style="margin-top: 6px; font-size: 18px; font-weight: 800; line-height: 1.4; color: #0f172a;">Document</div>
                </div>
                <button type="button" onclick="closeReferenceViewerModal()" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <div style="padding: 16px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: #f8fafc;">
                <div id="reference-viewer-description" style="font-size: 13px; line-height: 1.7; color: #64748b;">Review the saved document without leaving the verification workflow.</div>
                <a id="reference-viewer-link" href="#" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; border: 1px solid #c7d2fe; background: #ffffff; color: #4338ca; font-size: 12px; font-weight: 800; text-decoration: none;">
                    Open in new tab
                </a>
            </div>
            <div style="flex: 1 1 auto; min-height: 68vh; background: #0f172a;">
                <iframe id="reference-viewer-frame" src="about:blank" title="Reference Viewer" style="width: 100%; height: 68vh; border: 0; background: #ffffff;"></iframe>
            </div>
        </div>
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
        function openWorkflowModal(modalId) {
            const modal = document.getElementById(modalId);

            if (!modal) return;

            modal.style.display = 'flex';
        }

        function closeWorkflowModal(modalId) {
            const modal = document.getElementById(modalId);

            if (!modal) return;

            modal.style.display = 'none';
        }

        function openReferenceViewerModal(payload) {
            const modal = document.getElementById('reference-viewer-modal');
            const frame = document.getElementById('reference-viewer-frame');
            const link = document.getElementById('reference-viewer-link');
            const name = document.getElementById('reference-viewer-name');
            const label = document.getElementById('reference-viewer-label');
            const description = document.getElementById('reference-viewer-description');

            if (!modal || !frame || !link || !name || !label || !description || !payload || !payload.url) return;

            frame.src = payload.url;
            link.href = payload.url;
            name.textContent = payload.name || 'Document';
            label.textContent = payload.label || 'Reference';
            description.textContent = payload.description || 'Review the saved document without leaving the verification workflow.';
            modal.style.display = 'flex';
        }

        function closeReferenceViewerModal() {
            const modal = document.getElementById('reference-viewer-modal');
            const frame = document.getElementById('reference-viewer-frame');

            if (!modal || !frame) return;

            modal.style.display = 'none';
            frame.src = 'about:blank';
        }

        document.addEventListener('click', function (event) {
            const referenceModal = document.getElementById('reference-viewer-modal');

            if (referenceModal && event.target === referenceModal) {
                closeReferenceViewerModal();
            }
        });

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

        document.addEventListener('keydown', function (event) {
            const referenceModal = document.getElementById('reference-viewer-modal');

            if (event.key === 'Escape' && referenceModal && referenceModal.style.display === 'flex') {
                closeReferenceViewerModal();
            }
        });
    </script>
</x-filament-panels::page>
