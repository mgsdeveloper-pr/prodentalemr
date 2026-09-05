<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $isTemplateThreeVerificationForm = $this->formTemplate === 'template_3';
        $summaryCards = $this->getWorkbenchSummary();
        $quickReference = $this->getQuickReferenceCard();
        $templateThreeContextRows = $this->getContextRows();
        $templateThreePracticeContext = collect($templateThreeContextRows['practice'] ?? [])
            ->mapWithKeys(fn (array $row): array => [(string) ($row['label'] ?? '') => $row['value'] ?? '-']);
        $templateThreeQuickReferenceRows = [
            'Patient & Policy' => [
                ['Patient Name', $quickReference['patient'] ?? '-'],
                ['DOB', $quickReference['dob'] ?? '-'],
                ['Relationship', $quickReference['relationship'] ?? '-'],
                ['Member ID', $quickReference['member_id'] ?? '-'],
                ['Group ID', $quickReference['group_number'] ?? '-'],
                ['Insurance Name', $quickReference['insurance_name'] ?? '-'],
            ],
            'Verification Context' => [
                ['Insurance Phone', $quickReference['phone'] ?? '-'],
                ['Subscriber', $quickReference['subscriber_name'] ?? '-'],
                ['Subscriber DOB', $quickReference['subscriber_dob'] ?? '-'],
                ['Subscriber ID', $quickReference['subscriber_id'] ?? '-'],
                ['Location', $templateThreePracticeContext->get('Location', '-')],
                ['Appointment Date', $quickReference['appointment_date'] ?? '-'],
            ],
            'Provider Information' => [
                ['Provider Name', $quickReference['provider_name'] ?? '-'],
                ['NPI', $quickReference['provider_npi'] ?? '-'],
                ['Tax ID / EIN', $quickReference['provider_tax_id'] ?? '-'],
                ['Clinic Name', $quickReference['clinic_name'] ?? '-'],
            ],
        ];
        $coreDetails = $this->getCoreDetailRows();
        $coverageMatrix = $this->getCoverageMatrix();
        $planProvisionRows = $this->getPlanProvisionRows();
        $historySection = $this->getHistorySection();
        $frequencyGroups = $this->getFrequencyGroups();
        $serviceHistoryRows = $this->getServiceHistoryRows();
        $codeCoverageSection = $this->getCodeCoverageSection();
        $smartVerificationForm = $this->getSmartVerificationForm();
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
        $workflowLifecycle = $this->getWorkflowLifecycle();
        $slaSnapshot = app(\App\Services\Verification\SLAService::class)->snapshot($record);
        $activityTimeline = $this->getActivityTimeline(6);
        $attachments = $this->getAttachmentCards();
        $feeScheduleReference = $this->getFeeScheduleReference();
        $canSubmitForm = method_exists($this, 'canSubmitForm') ? $this->canSubmitForm() : true;
        $callingWorkspace = $this->getCallingWorkspace();
        $clinicResponseUrl = $this->getClinicResponseUrl();
        $canRequestClinicInfo = $this->canRequestClinicInfo();
        $canRefreshVerificationTemplate = $this->canRefreshVerificationTemplate();
        $statusRules = app(\App\Services\Verification\StatusService::class);
        $canSubmitToQa = $statusRules->canShowSendToReview($record, auth()->user());
        $canApproveQa = $statusRules->canShowMarkDone($record, auth()->user());
        $canReturnForRework = $statusRules->canShowReturnForRework($record, auth()->user());
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
        $correctionFocus = $this->getCorrectionFocus();
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
            'Relationship: ' . ($quickReference['relationship'] ?? ''),
            'Subscriber: ' . ($quickReference['subscriber_name'] ?? ''),
            'Subscriber DOB: ' . ($quickReference['subscriber_dob'] ?? ''),
            'Insurance / TPA: ' . ($quickReference['insurance_name'] ?? ''),
            'Insurance / TPA Phone: ' . ($quickReference['phone'] ?? ''),
            'Coverage Status: ' . ($quickReference['coverage_role'] ?? ''),
            'Group Number: ' . ($quickReference['group_number'] ?? ''),
            'Appointment Date: ' . ($quickReference['appointment_date'] ?? ''),
            'Doctor: ' . ($quickReference['provider_name'] ?? ''),
            'Provider NPI: ' . ($quickReference['provider_npi'] ?? ''),
        ]));
        $requestStatusLabel = \App\Models\BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? str($record->normalized_status)->headline()->toString();
        $requestNextAction = match (true) {
            $record->normalized_status === \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => 'Waiting for clinic response',
            $canSubmitToQa => 'Send to Audit when ready',
            $canApproveQa => 'Review and complete',
            $this->auditReady => 'Audit saved form',
            default => 'Save progress',
        };
        $latestActivity = $activityTimeline->first();
        $templateVersionRecord = $record->verificationTemplateVersion;
        $templateSnapshotVersion = data_get($record->verification_template_snapshot, 'version', []);
        $templateVersionNumber = data_get($templateSnapshotVersion, 'version_number') ?? $templateVersionRecord?->version_number;
        $templateScope = data_get($templateSnapshotVersion, 'scope') ?? $templateVersionRecord?->scope;
        $templateName = data_get($templateSnapshotVersion, 'name') ?? $templateVersionRecord?->name;
        $templateFormType = data_get($templateSnapshotVersion, 'form_type') ?? $templateVersionRecord?->form_type;
        $templateFormTypeLabel = \App\Models\VerificationTemplateVersion::FORM_TYPE_OPTIONS[$templateFormType] ?? 'Full + Short';
        $templateChipLabel = trim(collect([
            $templateScope ? str($templateScope)->headline()->toString() : null,
            $templateVersionNumber ? 'v' . $templateVersionNumber : null,
            $templateFormTypeLabel,
        ])->filter()->implode(' / '));
    @endphp

    <script>
        (() => {
            const collapseVerificationSidebar = () => {
                const root = document.documentElement;

                root.classList.add('app-sidebar-collapsed');
                root.classList.add('verification-sidebar-collapsed');

                localStorage.setItem('app-sidebar-collapsed', '1');
                localStorage.setItem('verification-sidebar-collapsed', '1');

                window.dispatchEvent(new Event('resize'));
            };

            collapseVerificationSidebar();
            document.addEventListener('livewire:navigated', collapseVerificationSidebar);
        })();
    </script>

    <script>
        (() => {
            window.triggerTemplateThreeDraftSave = (button) => {
                if (!button) {
                    return false;
                }

                const active = document.activeElement;

                if (active && active !== button && typeof active.blur === 'function') {
                    active.blur();
                }

                window.clearTimeout(window.__templateThreeDraftSaveTimer);

                window.__templateThreeDraftSaveTimer = window.setTimeout(() => {
                    const livewireRoot = button.closest('[wire\\:id]') ?? document;
                    const proxyButton = livewireRoot.querySelector('[data-template-three-draft-proxy]');

                    if (proxyButton) {
                        proxyButton.click();
                    }
                }, 220);

                return false;
            };
        })();
    </script>

    @php
        ob_start();
    @endphp
        <div class="vt3-top-actions" style="display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;align-items:center;">
            @if ((! $isTemplateThreeVerificationForm || $this->focusMode) && ($callingWorkspace['visible'] ?? false) && filled($quickReference['phone'] ?? null))
                @include('filament.saas.resources.verifications.pages.partials.telephony-call-control', [
                    'callingWorkspace' => $callingWorkspace,
                    'destinationNumber' => $quickReference['phone'],
                    'insuranceName' => $quickReference['insurance_name'] ?? 'Insurance',
                ])
            @endif
            @if ($clinicResponseUrl)
                <a href="{{ $clinicResponseUrl }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:188px;padding:10px 15px;border-radius:12px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:900;text-decoration:none;">
                    Respond in Request &amp; Response
                </a>
            @elseif ($canSubmitForm)
                @if (($this->formTemplate ?? null) === 'template_3')
                    <button
                        type="button"
                        data-template-three-draft-proxy
                        wire:click="saveAsDraft"
                        wire:loading.attr="disabled"
                        wire:target="saveAsDraft"
                        style="display:none"
                        tabindex="-1"
                        aria-hidden="true"
                    ></button>
                @endif
                @if ($this->auditReady)
                    <button type="button" wire:click="auditVerification" style="display:inline-flex;align-items:center;justify-content:center;min-width:112px;padding:10px 15px;border-radius:12px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:900;cursor:pointer;">
                        {{ $this->getSaveButtonLabel() }}
                    </button>
                @else
                    <button
                        type="button"
                        @if (($this->formTemplate ?? null) === 'template_3')
                            onclick="return window.triggerTemplateThreeDraftSave(this);"
                        @else
                            wire:click="saveAsDraft"
                        @endif
                        style="display:inline-flex;align-items:center;justify-content:center;min-width:112px;padding:10px 15px;border:0;border-radius:12px;background:#0f766e;color:#ffffff;font-size:12px;font-weight:900;cursor:pointer;box-shadow:0 8px 18px rgba(15,118,110,0.18);"
                    >
                        {{ $this->getSaveButtonLabel() }}
                    </button>
                        @endif
                <div x-data="{ open: false }" style="position:relative;">
                    <button type="button" x-on:click="open = ! open" style="display:inline-flex;align-items:center;justify-content:center;min-width:74px;height:40px;border-radius:12px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:12px;font-weight:900;cursor:pointer;">More</button>
                    <div x-show="open" x-transition x-on:click.outside="open = false" style="position:absolute;right:0;top:46px;z-index:40;display:grid;gap:6px;min-width:190px;padding:8px;border:1px solid #dbe4ee;border-radius:14px;background:#ffffff;box-shadow:0 16px 34px rgba(15,23,42,0.14);">
                        @if ($canRequestClinicInfo)
                            <button type="button" x-on:click="open = false" wire:click="openInfoRequestModal" style="display:flex;align-items:center;width:100%;padding:10px 12px;border-radius:10px;border:1px solid #fed7aa;background:#fff7ed;color:#c2410c;font-size:12px;font-weight:850;cursor:pointer;text-align:left;">
                                Request Clinic Info
                            </button>
                        @endif
                        @if ($canRefreshVerificationTemplate)
                            <button type="button" wire:click="refreshVerificationTemplate" wire:confirm="Refresh this request to the latest clinic template? Existing workflow status will remain unchanged." style="display:flex;align-items:center;width:100%;padding:10px 12px;border-radius:10px;border:1px solid #c7d2fe;background:#eef2ff;color:#4338ca;font-size:12px;font-weight:850;cursor:pointer;text-align:left;">
                                Refresh Template
                            </button>
                        @endif
                        @unless ($this->focusMode)
                            <button type="button" wire:click="enterFocusMode" style="display:flex;align-items:center;width:100%;padding:10px 12px;border-radius:10px;border:1px solid #99f6e4;background:#f0fdfa;color:#0f766e;font-size:12px;font-weight:850;cursor:pointer;text-align:left;">
                                Focus Mode
                            </button>
                        @endunless
                        <a href="{{ $this->getIndexUrl() }}" style="display:flex;align-items:center;width:100%;padding:10px 12px;border-radius:10px;border:1px solid #dbe4ee;background:#f8fafc;color:#334155;font-size:12px;font-weight:850;text-align:left;text-decoration:none;">
                            Back to Queue
                        </a>
                        <button type="button" onclick="if (! confirm('Clear the verification answers and reset this form?')) return false;" wire:click="clearVerificationForm" style="display:flex;align-items:center;width:100%;padding:10px 12px;border-radius:10px;border:1px solid #fecdd3;background:#fff1f2;color:#be123c;font-size:12px;font-weight:850;cursor:pointer;text-align:left;">
                            Clear Form
                        </button>
                    </div>
                </div>
            @else
                <a href="{{ $this->getIndexUrl() }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 15px;border-radius:12px;border:1px solid #dbe4ee;background:#ffffff;color:#334155;font-size:12px;font-weight:850;text-decoration:none;">
                    Back
                </a>
            @endif
        </div>
    @php
        $verificationFormHeroActions = trim(ob_get_clean());
        $focusModeSaveState = $this->getFocusModeSaveState();
        $focusModePatient = $record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?? 'Verification Request');
    @endphp

    <script>
        (() => {
            const root = document.documentElement;
            const focusModeActive = @js($this->focusMode);

            root.classList.toggle('pd-focus-mode-active', focusModeActive);

            if (focusModeActive) {
                document.addEventListener('livewire:navigating', () => {
                    root.classList.remove('pd-focus-mode-active');
                }, { once: true });
            }
        })();
    </script>

    <style>
        @keyframes pd-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .vt3-compact-workbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px 24px;
            align-items: center;
            padding: 14px 24px 12px;
            border: 0;
            border-bottom: 1px solid #dbe4ee;
            border-radius: 0;
            background: #ffffff;
            box-shadow: none;
        }

        .vt3-compact-workbar__identity {
            min-width: 0;
            display: grid;
            gap: 7px;
        }

        .vt3-compact-workbar__title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 14px;
        }

        .vt3-compact-workbar__title-row h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.15;
            font-weight: 900;
            color: #0f172a;
        }

        .vt3-compact-workbar__context {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 10px;
            margin-top: 0;
        }

        .vt3-compact-workbar__context-divider {
            color: #cbd5e1;
            font-size: 12px;
        }

        .vt3-compact-workbar__token {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 10px;
            border: 1px solid #dbe4ee;
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .vt3-compact-workbar__patient {
            font-size: 13px;
            font-weight: 800;
            color: #334155;
        }

        .vt3-compact-workbar__breadcrumbs {
            margin-top: 0;
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 650;
            color: #64748b;
        }

        .vt3-compact-workbar__breadcrumbs span:last-child {
            color: #0f172a;
        }

        .vt3-compact-workbar__actions {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            align-self: center;
            max-width: none;
            white-space: nowrap;
        }

        .vt3-compact-workbar__actions .vt3-top-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .vt3-compact-workbar__actions .vt3-top-actions > button,
        .vt3-compact-workbar__actions .vt3-top-actions > a {
            min-width: 0 !important;
            padding: 10px 16px !important;
            border-radius: 12px !important;
            font-size: 12px !important;
            font-weight: 800 !important;
            line-height: 1 !important;
            box-shadow: none !important;
            transition: background-color 140ms ease, border-color 140ms ease, color 140ms ease, transform 140ms ease !important;
        }

        .vt3-compact-workbar__actions .vt3-top-actions > button:hover,
        .vt3-compact-workbar__actions .vt3-top-actions > a:hover {
            transform: translateY(-1px);
        }

        .vt3-form-stage {
            gap: 16px !important;
        }

        .vt3-integrated-header {
            position: sticky;
            top: calc(var(--pwdl-shell-topbar, 72px) - 8px);
            z-index: 25;
            overflow: visible;
            border: 0;
            border-bottom: 1px solid #dbe4ee;
            border-radius: 0;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        }

        .vt3-integrated-header .vt3-compact-workbar {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            min-height: 56px;
            padding: 8px 16px;
            border-bottom: 0;
        }

        .vt3-integrated-header .vt3-compact-workbar__title-row {
            flex-wrap: nowrap;
            gap: 0;
            min-width: 0;
            align-items: center;
        }

        .vt3-integrated-header .vt3-compact-workbar__title-row h1 {
            flex: 0 0 auto;
            padding-right: 14px;
            font-size: 15px;
            line-height: 1.2;
        }

        .vt3-integrated-header .vt3-compact-workbar__context {
            flex-wrap: nowrap;
            gap: 0;
            min-width: 0;
            margin: 0;
        }

        .vt3-header-context-item {
            display: inline-flex;
            align-items: center;
            min-width: 0;
            padding: 0 12px;
            border-left: 1px solid #dbe4ee;
            color: #475569;
            font-size: 12px;
            line-height: 1.35;
            white-space: nowrap;
        }

        .vt3-header-context-item strong {
            margin-left: 4px;
            color: #0f172a;
            font-weight: 850;
        }

        .vt3-header-context-item:last-child {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .vt3-integrated-header .vt3-compact-workbar__actions,
        .vt3-integrated-header .vt3-compact-workbar__actions .vt3-top-actions {
            justify-content: flex-end;
        }

        .vt3-reference-drawer {
            position: fixed;
            top: calc(var(--pwdl-shell-topbar, 72px) + 49px);
            right: 0;
            bottom: 0;
            z-index: 45;
            display: flex;
            width: min(390px, calc(100vw - 48px));
            flex-direction: column;
            border-left: 1px solid #dbe4ee;
            background: #ffffff;
            box-shadow: -12px 0 28px rgba(15, 23, 42, 0.14);
            transform: translateX(100%);
            transition: transform 180ms ease;
        }

        .vt3-reference-drawer.is-open {
            transform: translateX(0);
        }

        .vt3-call-tool {
            position: absolute;
            top: calc(34% - 50px);
            left: -42px;
            z-index: 2;
            width: 42px;
        }

        .vt3-call-tool__trigger {
            display: inline-grid;
            width: 42px;
            height: 42px;
            padding: 0;
            place-items: center;
            border: 0;
            border-radius: 6px 0 0 6px;
            background: #0f766e;
            color: #ffffff;
            cursor: pointer;
            box-shadow: -4px 4px 12px rgba(15, 23, 42, 0.14);
        }

        .vt3-call-tool__trigger:hover {
            background: #115e59;
        }

        .vt3-call-tool__trigger.is-active {
            background: #15803d;
        }

        .vt3-call-tool__trigger.has-error {
            background: #b91c1c;
        }

        .vt3-call-tool__trigger.is-unavailable {
            background: #64748b;
        }

        .vt3-call-tool__trigger:focus-visible {
            outline: 3px solid rgba(45, 212, 191, 0.42);
            outline-offset: 2px;
        }

        .vt3-call-tool__trigger svg {
            width: 19px;
            height: 19px;
        }

        .vt3-reference-drawer__tab {
            position: absolute;
            top: 34%;
            left: -42px;
            display: flex;
            width: 42px;
            min-height: 184px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 7px;
            border: 0;
            border-radius: 6px 0 0 6px;
            background: #0f766e;
            color: #ffffff;
            cursor: pointer;
            box-shadow: -4px 4px 12px rgba(15, 23, 42, 0.14);
        }

        .vt3-reference-drawer__tab svg {
            position: absolute;
            top: 10px;
            width: 16px;
            height: 16px;
        }

        .vt3-reference-drawer__tab span {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 11px;
            font-weight: 850;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .vt3-reference-drawer__header {
            display: flex;
            min-height: 64px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid #dbe4ee;
        }

        .vt3-reference-drawer__header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
        }

        .vt3-reference-drawer__close {
            display: inline-grid;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            place-items: center;
            border: 0;
            border-radius: 5px;
            background: transparent;
            color: #475569;
            cursor: pointer;
        }

        .vt3-reference-drawer__close:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .vt3-reference-drawer__close svg {
            width: 20px;
            height: 20px;
        }

        .vt3-reference-drawer__body {
            min-height: 0;
            flex: 1;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 8px 20px 24px;
            scrollbar-width: thin;
        }

        .vt3-reference-drawer__group {
            padding: 18px 0;
            border-bottom: 1px solid #dbe4ee;
        }

        .vt3-reference-drawer__group:last-child {
            border-bottom: 0;
        }

        .vt3-reference-drawer__group h3 {
            margin: 0 0 14px;
            color: #0f766e;
            font-size: 14px;
            font-weight: 900;
        }

        .vt3-reference-drawer__group dl {
            display: grid;
            gap: 0;
            margin: 0;
        }

        .vt3-reference-drawer__row {
            display: grid;
            grid-template-columns: minmax(112px, .8fr) minmax(0, 1.2fr);
            gap: 14px;
            padding: 9px 0;
        }

        .vt3-reference-drawer__row dt,
        .vt3-reference-drawer__row dd {
            margin: 0;
            font-size: 12px;
            line-height: 1.45;
        }

        .vt3-reference-drawer__row dt {
            color: #64748b;
            font-weight: 650;
        }

        .vt3-reference-drawer__row dd {
            color: #0f172a;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .vt3-reference-drawer__row a,
        .vt3-reference-drawer__row button {
            color: #0f766e;
            text-decoration: none;
        }

        .vt3-reference-drawer__row a:hover,
        .vt3-reference-drawer__row button:hover {
            text-decoration: underline;
        }

        .vt3-status-rail {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
        }

        .vt3-compact-workbar > .vt3-status-rail {
            grid-column: 1 / -1;
            padding-top: 12px;
            border-top: 1px solid #eef2f7;
        }

        .vt3-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            padding: 5px 9px;
            border: 1px solid #dbe4ee;
            border-radius: 9px;
            background: #ffffff;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.2;
            white-space: nowrap;
        }

        .vt3-status-chip strong {
            color: #0f172a;
            font-weight: 900;
        }

        .vt3-status-chip--primary {
            border-color: #bde8dc;
            background: #eef8f4;
            color: #0f766e;
        }

        .vt3-operations-panel {
            border: 1px solid #dbe4ee;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .vt3-operations-panel summary {
            list-style: none;
        }

        .vt3-operations-panel summary::-webkit-details-marker {
            display: none;
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

        .verification-smart-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .verification-smart-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .verification-smart-field--wide {
            grid-column: 1 / -1;
        }

        .verification-template-switcher {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px;
            border: 1px solid #dbe4ee;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }

        .verification-template-switcher button {
            min-width: 118px;
            padding: 9px 14px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #64748b;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
        }

        .verification-template-switcher button.is-active {
            background: #0f766e;
            color: #ffffff;
            box-shadow: 0 6px 14px rgba(15, 118, 110, 0.22);
        }

        @media (max-width: 1280px) {
            .verification-workbench-layout {
                grid-template-columns: minmax(280px, 320px) minmax(0, 1fr);
            }
        }

        @media (max-width: 1120px) {
            .vt3-compact-workbar {
                grid-template-columns: minmax(0, 1fr);
                align-items: start;
                padding-inline: 18px;
            }

            .vt3-integrated-header {
                position: static;
                box-shadow: none;
            }

            .vt3-integrated-header .vt3-compact-workbar__title-row,
            .vt3-integrated-header .vt3-compact-workbar__context {
                flex-wrap: wrap;
            }

            .vt3-header-context-item {
                margin-top: 5px;
            }

            .vt3-compact-workbar__actions {
                justify-content: flex-start;
                max-width: none;
            }

            .vt3-integrated-header .vt3-compact-workbar__actions,
            .vt3-integrated-header .vt3-compact-workbar__actions .vt3-top-actions {
                justify-content: flex-start;
            }

            .verification-workbench-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .verification-workbench-sidebar {
                position: static;
            }

            .verification-smart-form-grid,
            .verification-smart-field-grid {
                grid-template-columns: minmax(0, 1fr);
            }

        }

        @media (max-width: 640px) {
            .vt3-integrated-header .vt3-compact-workbar__title-row h1 {
                width: 100%;
                padding: 0 0 4px;
            }

            .vt3-header-context-item {
                padding: 0 8px;
                border-left: 0;
            }

            .vt3-header-context-item:first-child {
                padding-left: 0;
            }

            .vt3-reference-drawer {
                top: calc(var(--pwdl-shell-topbar, 72px) - 8px);
                width: calc(100vw - 42px);
            }

            .vt3-reference-drawer__tab {
                left: -38px;
                width: 38px;
            }

            .vt3-call-tool {
                left: -38px;
                width: 38px;
            }

            .vt3-call-tool__trigger {
                width: 38px;
                height: 38px;
            }
        }
    </style>

    <div class="verification-reference-workspace verification-reference-workspace--edit {{ $this->focusMode ? 'verification-focus-mode' : '' }}" style="display: flex; flex-direction: column; gap: {{ $isTemplateThreeVerificationForm && ! $this->focusMode ? '12px' : '22px' }};">
        @if ($this->focusMode)
            <x-pds.focus-mode-topbar
                :title="$this->getTitle()"
                :reference="$record->reference_number"
                :patient="$focusModePatient"
                :save-status="$focusModeSaveState['status']"
                :save-label="$focusModeSaveState['label']"
            >
                <x-pds.button type="button" variant="secondary" size="sm" wire:click="exitFocusMode">
                    Exit Focus Mode
                </x-pds.button>
            </x-pds.focus-mode-topbar>
        @elseif ($isTemplateThreeVerificationForm)
            <section
                class="vt3-integrated-header"
                x-data="{ quickReferenceDrawerOpen: true }"
                x-on:keydown.escape.window="quickReferenceDrawerOpen = false"
            >
                <div class="vt3-compact-workbar">
                    <div class="vt3-compact-workbar__identity">
                        <div class="vt3-compact-workbar__title-row">
                            <h1>{{ $this->getTitle() }}</h1>
                            <div class="vt3-compact-workbar__context">
                                <span class="vt3-header-context-item">Patient: <strong>{{ $quickReference['patient'] ?? '-' }}</strong></span>
                                <span class="vt3-header-context-item">DOB: <strong>{{ $quickReference['dob'] ?? '-' }}</strong></span>
                                <span class="vt3-header-context-item">Member ID: <strong>{{ $quickReference['member_id'] ?? '-' }}</strong></span>
                                <span class="vt3-header-context-item">Insurance: <strong>{{ $quickReference['insurance_name'] ?? '-' }}</strong></span>
                                <span class="vt3-header-context-item">Subscriber: <strong>{{ $quickReference['subscriber_name'] ?? '-' }}</strong></span>
                                <span class="vt3-header-context-item">Subscriber DOB: <strong>{{ $quickReference['subscriber_dob'] ?? '-' }}</strong></span>
                                <span class="vt3-header-context-item">Subscriber ID: <strong>{{ $quickReference['subscriber_id'] ?? '-' }}</strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="vt3-compact-workbar__actions">{!! $verificationFormHeroActions !!}</div>
                </div>
                @include('filament.saas.resources.verifications.pages.partials.template-3-quick-reference-drawer')
            </section>
        @else
            @include('filament.shared.partials.page-hero', [
                'eyebrow' => 'Verification Worksheet',
                'title' => $this->getTitle(),
                'description' => $this->getFormDescription(),
                'extraContent' => '
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <div style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;padding:6px 10px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#64748b;font-size:12px;font-weight:700;width:fit-content;">
                            <span>Verification Requests</span>
                            <span style="color:#94a3b8;">&rsaquo;</span>
                            <span>'.e($record->reference_number).'</span>
                            <span style="color:#94a3b8;">&rsaquo;</span>
                            <span>Edit</span>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                            <span style="display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;background:#ffffff;border:1px solid #dbe4ee;color:#334155;font-size:12px;font-weight:700;">'.e($record->reference_number).'</span>
                            <span style="font-size:13px;font-weight:700;color:#0f172a;">Patient: '.e($record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?? 'Verification Request')).'</span>
                        </div>
                    </div>',
                'rightContent' => $verificationFormHeroActions,
            ])
        @endif

        <x-pds.validation-summary :errors="$errors" />

        @if ($correctionFocus)
            <section style="border:1px solid #fecdd3;border-left:4px solid #e11d48;border-radius:10px;background:#fff7f8;padding:16px 18px;display:grid;gap:12px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#be123c;">Correction Requested</div>
                        <div style="margin-top:5px;font-size:14px;font-weight:800;color:#0f172a;">{{ $correctionFocus['reason'] }}</div>
                        <div style="margin-top:4px;font-size:12px;color:#64748b;">Requested by {{ $correctionFocus['requested_by'] ?: 'Clinic' }}{{ $correctionFocus['requested_at'] ? ' on '.$correctionFocus['requested_at'] : '' }}. The original completed result remains locked.</div>
                    </div>
                    @if ($correctionFocus['baseline_submission_version'])
                        <span style="padding:6px 10px;border-radius:999px;border:1px solid #fecdd3;background:#fff;color:#9f1239;font-size:11px;font-weight:800;">Baseline v{{ $correctionFocus['baseline_submission_version'] }}</span>
                    @endif
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:7px;">
                    @foreach ($correctionFocus['requested_fields'] as $field)
                        <span style="padding:6px 9px;border-radius:7px;border:1px solid #fecdd3;background:#fff;color:#881337;font-size:11px;font-weight:700;">{{ $field }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        @if (! $this->focusMode && ! $isTemplateThreeVerificationForm)
            <section class="vt3-status-rail" aria-label="Verification request summary">
                <span class="vt3-status-chip vt3-status-chip--primary">Status <strong>{{ $requestStatusLabel }}</strong></span>
                <span class="vt3-status-chip">Next <strong>{{ $requestNextAction }}</strong></span>
                <span class="vt3-status-chip" title="{{ $templateName ?: 'Template snapshot' }}">Template <strong>{{ $templateChipLabel ?: 'Attached' }}</strong></span>
                <span class="vt3-status-chip">SLA <strong>{{ $slaSnapshot['label'] ?? 'Not Set' }}</strong></span>
                <span class="vt3-status-chip">Activity <strong>{{ $activityTimeline->count() }}</strong></span>
            </section>
        @endif

        @if ($this->focusMode)
            <x-pds.sticky-action-bar>
                {!! $verificationFormHeroActions !!}
                <x-pds.button type="button" variant="secondary" size="sm" wire:click="exitFocusMode">
                    Exit Focus Mode
                </x-pds.button>
            </x-pds.sticky-action-bar>
        @endif

        <form wire:submit="save">
            <section class="vt3-form-stage" style="display: flex; flex-direction: column; gap: 18px;">
                @include('filament.saas.resources.verifications.pages.partials.verification-form-template-3')
            </section>
        </form>

        @unless ($this->focusMode)
            <details class="vt3-operations-panel">
                <summary style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; cursor: pointer; list-style: none;">
                    <span>
                        <span style="display: block; font-size: 11px; font-weight: 900; letter-spacing: 0.16em; text-transform: uppercase; color: #0f766e;">Request Activity</span>
                        <span style="display: block; margin-top: 4px; font-size: 13px; color: #64748b;">Open when you need workflow details, audit review, or timeline history.</span>
                    </span>
                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 7px 12px; border-radius: 999px; border: 1px solid #dbe4ee; background: #f8fafc; color: #334155; font-size: 12px; font-weight: 900;">
                        {{ $requestStatusLabel }}
                    </span>
                </summary>
                <div style="display: flex; flex-direction: column; gap: 16px; padding: 0 18px 18px;">
                    @include('filament.saas.resources.verifications.pages.partials.workflow-lifecycle', [
                        'lifecycle' => $workflowLifecycle,
                    ])

                    @include('filament.saas.resources.verifications.pages.partials.sla-summary', [
                        'record' => $record,
                        'sla' => $slaSnapshot,
                    ])

                    @include('filament.saas.resources.verifications.pages.partials.qa-summary', [
                        'record' => $record,
                        'showQaActions' => true,
                        'canSubmitToQa' => false,
                        'canApproveQa' => $canApproveQa,
                        'canReturnForRework' => $canReturnForRework,
                    ])

                </div>
            </details>
        @endunless
    </div>

    @if ($showInfoRequestField && $this->showInfoRequestModal)
        <div id="info-request-modal" style="position: fixed; inset: 0; z-index: 80; display: flex; align-items: center; justify-content: center; padding: 28px; background: rgba(15, 23, 42, 0.62);">
            <div style="width: min(720px, 100%); border-radius: 28px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 28px 64px rgba(15, 23, 42, 0.28); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #10b981;">Clinic Information Needed</div>
                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Request Clinic Information</h3>
                        <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Explain exactly what the clinic must provide. This moves the request to Waiting on Clinic and pauses SLA timing until they respond.
                        </p>
                    </div>
                    <button type="button" wire:click="closeInfoRequestModal" style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px; cursor: pointer;">&times;</button>
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
                    <button type="button" wire:click="closeInfoRequestModal" style="display: inline-flex; align-items: center; justify-content: center; min-width: 132px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 13px; font-weight: 800; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveAndTransition('{{ \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE }}')" wire:loading.attr="disabled" wire:target="saveAndTransition" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-width: 180px; padding: 11px 16px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 800; cursor: pointer;">
                        <span wire:loading.remove wire:target="saveAndTransition">Send to Clinic</span>
                        <span wire:loading.inline-flex wire:target="saveAndTransition" style="display: none; align-items: center; gap: 8px;">
                            <span style="width: 14px; height: 14px; border-radius: 999px; border: 2px solid #bfdbfe; border-top-color: #1d4ed8; animation: pd-spin 0.8s linear infinite;"></span>
                            Sending...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showReworkReasonField || $canReturnForRework)
        <div id="rework-reason-modal" style="position: fixed; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center; padding: 28px; background: rgba(15, 23, 42, 0.62);">
            <div style="width: min(720px, 100%); border-radius: 28px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 28px 64px rgba(15, 23, 42, 0.28); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #10b981;">Audit Review</div>
                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Return for Correction</h3>
                        <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Describe the correction or quality issue before sending this request back to the specialist.
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
                        Return for Correction
                    </button>
                </div>
            </div>
        </div>
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
                            Review the exact form data that was saved at this point in the workflow, including the request status, verification profile, and captured answers.
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

        document.addEventListener('close-workflow-modal', function (event) {
            const modalId = event.detail?.modalId;

            if (!modalId) return;

            closeWorkflowModal(modalId);
        });

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
