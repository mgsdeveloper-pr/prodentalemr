<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $quickReference = $this->getQuickReferenceCard();
        $attachments = $this->getAttachmentCards();
        $notes = $this->getClientVisibleNotes();
        $activities = $this->getActivityTimeline();
        $communication = $this->getClinicCommunication();
        $snapshot = $this->getAuthoritativeSubmissionSnapshot();
        $sla = app(\App\Services\Verification\SLAService::class)->snapshot($record);
        $canViewSubmissionSnapshots = $this->canViewSubmissionSnapshots();
        $selectedSubmissionSnapshot = $this->selectedSubmissionSnapshot;
        $completedResultVersions = $record->formSubmissions()
            ->where('status', \App\Models\BillingWorkItem::STATUS_DONE)
            ->orderByDesc('version')
            ->get();
        $snapshotProfileFields = collect($snapshot['filled_verification_profile'] ?? [])->reject(
            fn (array $field): bool => in_array($field['label'] ?? null, ['Public Id', 'Is Pre Registered', 'Quick Reference'], true)
        );
        $statusLabel = \App\Models\BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? str($record->normalized_status)->headline()->toString();
        $priorityLabel = \App\Models\BillingWorkItem::PRIORITY_OPTIONS[$record->priority] ?? str($record->priority)->headline()->toString();
        $formType = \App\Models\VerificationProfile::FORM_TYPE_OPTIONS[$record->verificationProfile?->form_type ?? 'full_form'] ?? 'Full Form';
        $profile = $record->verificationProfile;
        $resultSummary = app(\App\Services\Verification\VerificationResultService::class)->summary($record);
        $eligibilityStatus = $resultSummary['eligibility_status'];
        $auditState = match ($record->normalized_status) {
            \App\Models\BillingWorkItem::STATUS_DONE => 'Audit Approved',
            \App\Models\BillingWorkItem::STATUS_REVIEW => 'In Audit Review',
            \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK => 'Returned for Correction',
            default => 'Not in Audit',
        };
        $quickReferenceCopyText = collect([
            'Patient: ' . ($quickReference['patient'] ?? '-'), 'DOB: ' . ($quickReference['dob'] ?? '-'),
            'Relationship: ' . ($quickReference['relationship'] ?? '-'), 'Subscriber: ' . ($quickReference['subscriber_name'] ?? '-'),
            'Member ID: ' . ($quickReference['member_id'] ?? '-'), 'Insurance: ' . ($quickReference['insurance_name'] ?? '-'),
            'Group Number: ' . ($quickReference['group_number'] ?? '-'), 'Appointment: ' . ($quickReference['appointment_date'] ?? '-'),
            'Provider: ' . ($quickReference['provider_name'] ?? '-'),
        ])->implode("\n");
    @endphp

    <style>
        .request-detail { display: flex; flex-direction: column; gap: 18px; color: #0f172a; }
        .request-detail__header, .request-card { border: 1px solid #dbe4ee; border-radius: 8px; background: #fff; }
        .request-detail__header { padding: 22px 24px; }
        .request-detail__eyebrow { margin: 0 0 7px; color: #0f766e; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .request-detail__title { margin: 0; font-size: 24px; line-height: 1.25; font-weight: 800; }
        .request-detail__subtitle { margin: 7px 0 0; color: #64748b; font-size: 13px; }
        .request-detail__chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
        .request-detail__notice { margin-top: 14px; padding: 10px 12px; border: 1px solid #cfe8e4; border-radius: 6px; background: #f5fbfa; color: #315b57; font-size: 12px; line-height: 1.55; }
        .request-chip { display: inline-flex; align-items: center; min-height: 28px; padding: 4px 10px; border: 1px solid #dbe4ee; border-radius: 999px; background: #f8fafc; color: #334155; font-size: 11px; font-weight: 700; }
        .request-chip--active { border-color: #99f6e4; background: #f0fdfa; color: #0f766e; }
        .request-detail__layout { display: grid; grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr); gap: 18px; align-items: start; }
        .request-detail__main, .request-detail__aside { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
        .request-detail__aside { position: sticky; top: 88px; }
        .request-card { overflow: hidden; }
        .request-card__header { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 15px 18px; border-bottom: 1px solid #e8eef4; background: #fbfcfd; }
        .request-card__title { margin: 0; font-size: 15px; font-weight: 800; }
        .request-card__hint { margin: 4px 0 0; color: #64748b; font-size: 12px; }
        .request-card__body { padding: 17px 18px; }
        .request-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .request-summary__group { padding: 0 18px; }
        .request-summary__group:first-child { padding-left: 0; border-right: 1px solid #e8eef4; }
        .request-summary__label { margin-bottom: 12px; color: #0f766e; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .request-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 13px 18px; }
        .request-field__label { margin-bottom: 3px; color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .request-field__value { color: #172033; font-size: 13px; font-weight: 700; overflow-wrap: anywhere; }
        .request-audit { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .request-audit__item { padding: 12px; border: 1px solid #e5eaf0; border-radius: 6px; background: #fff; }
        .request-audit__item strong { display: block; margin-top: 5px; font-size: 13px; }
        .request-communication { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .request-communication__item { min-width: 0; padding: 17px 18px; }
        .request-communication__item:first-child { border-right: 1px solid #e8eef4; }
        .request-communication__label { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; color: #0f766e; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .request-communication__message { color: #172033; font-size: 13px; font-weight: 700; line-height: 1.65; white-space: pre-line; overflow-wrap: anywhere; }
        .request-communication__meta { margin-top: 10px; color: #64748b; font-size: 11px; }
        .request-result-band { display: grid; border-bottom: 1px solid #e8eef4; }
        .request-result-band:last-child { border-bottom: 0; }
        .request-result-band--three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .request-result-band--four { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .request-result__item { min-width: 0; padding: 14px 16px; border-right: 1px solid #e8eef4; }
        .request-result__item:last-child { border-right: 0; }
        .request-result__value { margin-top: 5px; color: #172033; font-size: 14px; font-weight: 800; overflow-wrap: anywhere; }
        .request-snapshot { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); border-top: 1px solid #e8eef4; border-left: 1px solid #e8eef4; }
        .request-snapshot__row { padding: 11px 13px; border-right: 1px solid #e8eef4; border-bottom: 1px solid #e8eef4; }
        .request-snapshot__question { color: #475569; font-size: 12px; font-weight: 700; }
        .request-snapshot__answer { margin-top: 5px; color: #111827; font-size: 13px; line-height: 1.55; white-space: pre-line; }
        .request-link { display: inline-flex; align-items: center; justify-content: center; min-height: 31px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #334155; font-size: 11px; font-weight: 700; text-decoration: none; }
        .request-link:hover { border-color: #5eead4; color: #0f766e; }
        .request-copy { border: 0; background: transparent; color: #0f766e; font-size: 11px; font-weight: 800; cursor: pointer; }
        .request-reference { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 14px; }
        .request-reference__row { padding: 9px 0; border-bottom: 1px solid #edf2f7; }
        .request-pdf, .request-document, .request-note { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid #edf2f7; }
        .request-pdf:last-child, .request-document:last-child, .request-note:last-child { border-bottom: 0; }
        .request-pdf__actions { display: flex; gap: 6px; flex-shrink: 0; }
        .request-empty { margin: 0; color: #64748b; font-size: 12px; line-height: 1.6; }
        .request-timeline summary { cursor: pointer; list-style: none; }
        .request-timeline summary::-webkit-details-marker { display: none; }
        .request-timeline__items { max-height: 380px; overflow: auto; }
        .request-timeline__item { padding: 0 0 14px 14px; border-left: 2px solid #dbe4ee; }
        .request-timeline__item:last-child { padding-bottom: 0; }
        .request-modal { position: fixed; inset: 0; z-index: 90; display: flex; align-items: center; justify-content: center; padding: 24px; background: rgba(15, 23, 42, .56); }
        .request-modal__dialog { width: min(980px, 100%); max-height: 88vh; overflow: auto; border: 1px solid #dbe4ee; border-radius: 8px; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, .28); }
        .request-change-table { display: grid; grid-template-columns: minmax(190px, 1.25fr) repeat(2, minmax(160px, 1fr)); border-top: 1px solid #dbe4ee; border-left: 1px solid #dbe4ee; }
        .request-change-table__cell { min-width: 0; padding: 12px 14px; border-right: 1px solid #dbe4ee; border-bottom: 1px solid #dbe4ee; font-size: 12px; line-height: 1.55; white-space: pre-line; overflow-wrap: anywhere; }
        .request-change-table__cell--head { background: #f8fafc; color: #475569; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .request-change-table__question { color: #111827; font-weight: 800; }
        .request-change-table__previous { background: #fffafa; color: #9f1239; }
        .request-change-table__current { background: #f6fffc; color: #166534; font-weight: 700; }
        @media (max-width: 1100px) { .request-detail__layout { grid-template-columns: 1fr; } .request-detail__aside { position: static; } }
        @media (max-width: 720px) { .request-summary, .request-audit, .request-snapshot, .request-reference, .request-communication { grid-template-columns: 1fr; } .request-communication__item:first-child { border-right: 0; border-bottom: 1px solid #e8eef4; } .request-result-band--three, .request-result-band--four { grid-template-columns: repeat(2, minmax(0, 1fr)); } .request-result__item { border-bottom: 1px solid #e8eef4; } .request-result__item:nth-child(2n) { border-right: 0; } .request-result-band .request-result__item:nth-last-child(-n + 2) { border-bottom: 0; } .request-summary__group { padding: 0; } .request-summary__group:first-child { padding: 0 0 18px; margin-bottom: 18px; border-right: 0; border-bottom: 1px solid #e8eef4; } .request-change-table { grid-template-columns: 1fr; } .request-change-table__cell--head { display: none; } .request-change-table__question { border-top: 8px solid #f1f5f9; } }
    </style>

    <div class="request-detail">
        <header class="request-detail__header">
            <p class="request-detail__eyebrow">Verification Request</p>
            <h2 class="request-detail__title">{{ $quickReference['patient'] ?: $record->title }}</h2>
            <p class="request-detail__subtitle">{{ $record->clinic?->clinic_name ?? $record->organization?->name ?? 'Clinic not assigned' }} &middot; {{ $quickReference['insurance_name'] ?? 'Insurance not provided' }}</p>
            <div class="request-detail__chips">
                <span class="request-chip">{{ $record->reference_number }}</span><span class="request-chip request-chip--active">{{ $statusLabel }}</span>
                <span class="request-chip">{{ $formType }}</span><span class="request-chip">{{ $priorityLabel }} priority</span><span class="request-chip">{{ $sla['label'] ?? 'SLA not set' }}</span>
                <span class="request-chip">Handled by {{ $record->processingModeLabel() }}</span>
            </div>
            @if (filled($this->getResultAccessMessage()))
                <div class="request-detail__notice">{{ $this->getResultAccessMessage() }}</div>
            @endif
        </header>

        <div class="request-detail__layout">
            <main class="request-detail__main">
                <section class="request-card">
                    <div class="request-card__header"><div><h3 class="request-card__title">Patient &amp; Insurance Summary</h3><p class="request-card__hint">The essential request context, without repeated queue data.</p></div></div>
                    <div class="request-card__body request-summary">
                        <div class="request-summary__group">
                            <div class="request-summary__label">Patient</div>
                            <div class="request-fields">
                                @foreach (['Patient name' => $quickReference['patient'], 'Date of birth' => $quickReference['dob'], 'Relationship' => $quickReference['relationship'], 'Subscriber' => $quickReference['subscriber_name'], 'Subscriber DOB' => $quickReference['subscriber_dob'], 'Appointment' => $quickReference['appointment_date'], 'Provider' => $quickReference['provider_name'], 'Provider NPI' => $quickReference['provider_npi']] as $label => $value)
                                    <div><div class="request-field__label">{{ $label }}</div><div class="request-field__value">{{ $value ?: '-' }}</div></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="request-summary__group">
                            <div class="request-summary__label">Insurance</div>
                            <div class="request-fields">
                                @foreach (['Insurance / TPA' => $quickReference['insurance_name'], 'Member ID' => $quickReference['member_id'], 'Group number' => $quickReference['group_number'], 'Coverage role' => $quickReference['coverage_role'], 'Payer phone' => $quickReference['phone'], 'Assigned to' => $record->assignedTo?->name ?: 'Queue', 'Organization' => $record->organization?->name ?: '-', 'Clinic' => $record->clinic?->clinic_name ?: '-'] as $label => $value)
                                    <div><div class="request-field__label">{{ $label }}</div><div class="request-field__value">{{ $value ?: '-' }}</div></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section class="request-card">
                    <div class="request-card__header">
                        <div><h3 class="request-card__title">Verification Result Summary</h3><p class="request-card__hint">Key eligibility and benefit values recorded for this request.</p></div>
                        <span class="request-chip request-chip--active">{{ $eligibilityStatus }}</span>
                    </div>
                    <div>
                        <div class="request-result-band request-result-band--three">
                            @foreach (['Eligibility status' => $eligibilityStatus, 'Effective date' => $resultSummary['effective_date'], 'Network status' => $resultSummary['network_status']] as $label => $value)
                                <div class="request-result__item"><div class="request-field__label">{{ $label }}</div><div class="request-result__value">{{ $value }}</div></div>
                            @endforeach
                        </div>
                        <div class="request-result-band request-result-band--four">
                            @foreach (['Annual maximum' => $resultSummary['annual_maximum'], 'Maximum remaining' => $resultSummary['annual_maximum_remaining'], 'Individual deductible' => $resultSummary['individual_deductible'], 'Deductible remaining' => $resultSummary['individual_deductible_remaining']] as $label => $value)
                                <div class="request-result__item"><div class="request-field__label">{{ $label }}</div><div class="request-result__value">{{ $value }}</div></div>
                            @endforeach
                        </div>
                        <div class="request-result-band request-result-band--four">
                            @foreach (['Preventive' => $resultSummary['coverage_preventive'], 'Basic' => $resultSummary['coverage_basic'], 'Major' => $resultSummary['coverage_major'], 'Orthodontic' => $resultSummary['coverage_orthodontic']] as $label => $value)
                                <div class="request-result__item"><div class="request-field__label">{{ $label }} coverage</div><div class="request-result__value">{{ $value }}</div></div>
                            @endforeach
                        </div>
                    </div>
                </section>

                @if ($communication['has_activity'])
                    <section class="request-card">
                        <div class="request-card__header">
                            <div><h3 class="request-card__title">Clinic Communication</h3><p class="request-card__hint">The latest information request and clinic response for this verification.</p></div>
                            <a class="request-link" href="{{ $this->getRequestResponseUrl() }}" wire:navigate>Open conversation</a>
                        </div>
                        <div class="request-communication">
                            <div class="request-communication__item">
                                <div class="request-communication__label"><span>Information Requested</span><span>{{ $communication['request_count'] }}</span></div>
                                @if ($communication['request'])
                                    <div class="request-communication__message">{{ $communication['request']['message'] }}</div>
                                    <div class="request-communication__meta">{{ $communication['request']['actor'] }} &middot; {{ $communication['request']['date'] }}</div>
                                @else
                                    <p class="request-empty">No clinic information request has been recorded.</p>
                                @endif
                            </div>
                            <div class="request-communication__item">
                                <div class="request-communication__label"><span>Clinic Response</span><span>{{ $communication['response_count'] }}</span></div>
                                @if ($communication['response'])
                                    <div class="request-communication__message">{{ $communication['response']['message'] }}</div>
                                    <div class="request-communication__meta">{{ $communication['response']['actor'] }} &middot; {{ $communication['response']['date'] }}</div>
                                @elseif ($communication['waiting_for_clinic'])
                                    <p class="request-empty">Waiting for the clinic to respond.</p>
                                @else
                                    <p class="request-empty">No clinic response is recorded for the latest request.</p>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                <section class="request-card">
                    <div class="request-card__header"><div><h3 class="request-card__title">Audit Result</h3><p class="request-card__hint">Completion, reviewer, and SLA information used before approval.</p></div><span class="request-chip request-chip--active">{{ $auditState }}</span></div>
                    <div class="request-card__body request-audit">
                        @foreach (['Audit status' => $auditState, 'Reviewer' => $record->reviewedBy?->name ?: '-', 'Reviewed at' => optional($record->completed_at)->format('M d, Y h:i A') ?: '-', 'SLA' => ($sla['label'] ?? 'Not set') . ' · ' . ($sla['relative'] ?? '-')] as $label => $value)
                            <div class="request-audit__item"><div class="request-field__label">{{ $label }}</div><strong>{{ $value }}</strong></div>
                        @endforeach
                    </div>
                </section>

                <section class="request-card">
                    <div class="request-card__header"><div><h3 class="request-card__title">Completed Form Snapshot</h3><p class="request-card__hint">The saved form remains unchanged for audit after completion.</p></div>@if ($snapshot)<span class="request-chip">Saved {{ data_get($snapshot, 'headline.submitted_at', '-') }}</span>@endif</div>
                    <div class="request-card__body">
                        @if ($snapshot)
                            @if ($snapshotProfileFields->isNotEmpty())
                                <div class="request-snapshot">@foreach ($snapshotProfileFields as $field)<div class="request-snapshot__row"><div class="request-snapshot__question">{{ $field['label'] }}</div><div class="request-snapshot__answer">{{ $field['value'] }}</div></div>@endforeach</div>
                            @endif
                            @if (! empty($snapshot['answers']))
                                <div class="request-snapshot" style="margin-top:14px;">@foreach ($snapshot['answers'] as $answer)<div class="request-snapshot__row"><div class="request-snapshot__question">{{ $answer['prompt'] }}</div><div class="request-snapshot__answer">{{ $answer['value'] }}</div></div>@endforeach</div>
                            @endif
                            @if ($snapshotProfileFields->isEmpty() && empty($snapshot['answers']))<p class="request-empty">The completed request has no saved answers in its audit snapshot.</p>@endif
                        @else
                            <p class="request-empty">A locked audit snapshot will appear here after the verification is approved as Done.</p>
                        @endif
                    </div>
                </section>
            </main>

            <aside class="request-detail__aside">
                <section class="request-card">
                    <div class="request-card__header"><h3 class="request-card__title">Quick Reference</h3><button type="button" class="request-copy" onclick="copyVerificationQuickReference(@js($quickReferenceCopyText), this)">Copy all</button></div>
                    <div class="request-card__body request-reference">
                        @foreach (['Patient' => $quickReference['patient'], 'DOB' => $quickReference['dob'], 'Member ID' => $quickReference['member_id'], 'Relationship' => $quickReference['relationship'], 'Insurance' => $quickReference['insurance_name'], 'Group #' => $quickReference['group_number'], 'Appointment' => $quickReference['appointment_date'], 'Provider' => $quickReference['provider_name']] as $label => $value)
                            <div class="request-reference__row"><div class="request-field__label">{{ $label }}</div><div class="request-field__value">{{ $value ?: '-' }}</div></div>
                        @endforeach
                    </div>
                </section>

                <section class="request-card">
                    <div class="request-card__header"><div><h3 class="request-card__title">PDF Outputs</h3><p class="request-card__hint">Preview or download the saved request output.</p></div></div>
                    <div class="request-card__body">
                        @if ($this->canAccessSensitiveOutputs())
                            @foreach (['standard' => 'Standard', 'custom_portrait' => 'Custom Portrait', 'custom_landscape' => 'Custom Landscape'] as $mode => $label)
                                <div class="request-pdf"><strong style="font-size:12px;">{{ $label }}</strong><div class="request-pdf__actions"><a class="request-link" href="{{ $this->getPdfPreviewUrl($mode) }}" target="_blank">Preview</a><a class="request-link" href="{{ $this->getPdfDownloadUrl($mode) }}">Download</a></div></div>
                            @endforeach
                            @if ($completedResultVersions->count() > 1)
                                <details style="margin-top:12px;padding-top:12px;border-top:1px solid #e8eef4;">
                                    <summary style="cursor:pointer;color:#0f766e;font-size:12px;font-weight:800;">Previous completed results ({{ $completedResultVersions->count() - 1 }})</summary>
                                    <div style="display:grid;gap:8px;margin-top:10px;">
                                        @foreach ($completedResultVersions->skip(1) as $resultVersion)
                                            <div class="request-pdf">
                                                <div><strong style="font-size:12px;">Result v{{ $resultVersion->version }}</strong><div class="request-field__label" style="margin-top:3px;">{{ optional($resultVersion->created_at)->format('M d, Y h:i A') }}</div></div>
                                                <div class="request-pdf__actions"><a class="request-link" href="{{ $this->getPdfPreviewUrl('standard', $resultVersion->getKey()) }}" target="_blank">Preview</a><a class="request-link" href="{{ $this->getPdfDownloadUrl('standard', $resultVersion->getKey()) }}">Download</a></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        @else
                            <p class="request-empty">Select {{ $record->clinic?->clinic_name ?: 'this clinic' }} in Clinic Scope to view protected PDF and audit outputs.</p>
                        @endif
                    </div>
                </section>

                <section class="request-card">
                    <div class="request-card__header"><div><h3 class="request-card__title">Documents &amp; Notes</h3><p class="request-card__hint">{{ $attachments->count() }} documents &middot; {{ $notes->count() }} notes</p></div></div>
                    <div class="request-card__body">
                        @forelse ($attachments as $attachment)
                            <div class="request-document"><div><div class="request-field__value">{{ $attachment['title'] }}</div><div class="request-field__label" style="margin-top:4px;">{{ $attachment['uploaded_at'] }}</div></div><a class="request-link" href="{{ $attachment['download_url'] }}">Download</a></div>
                        @empty
                            <p class="request-empty">No documents are attached.</p>
                        @endforelse
                        @if ($notes->isNotEmpty())<div style="margin-top:12px; padding-top:4px; border-top:1px solid #edf2f7;">@foreach ($notes->take(3) as $note)<div class="request-note" style="display:block;"><div style="font-size:12px; line-height:1.55; color:#334155;">{{ $note->body }}</div><div class="request-field__label" style="margin-top:6px;">{{ $note->user?->name ?? 'System' }} &middot; {{ optional($note->created_at)->format('M d, Y') }}</div></div>@endforeach</div>@endif
                    </div>
                </section>

                <details class="request-card request-timeline">
                    <summary class="request-card__header"><div><h3 class="request-card__title">Timeline</h3><p class="request-card__hint">{{ $activities->count() }} recorded events</p></div><span class="request-chip">Open</span></summary>
                    <div class="request-card__body request-timeline__items">
                        @forelse ($activities as $activity)
                            <div class="request-timeline__item"><div class="request-field__value">{{ $activity['type'] }}</div><div style="margin-top:4px; font-size:12px; line-height:1.55; color:#475569;">{{ $activity['description'] }}</div>@if (filled($activity['details']))<div style="margin-top:5px; font-size:11px; line-height:1.5; color:#64748b; white-space:pre-line;">{{ $activity['details'] }}</div>@endif<div class="request-field__label" style="margin-top:6px;">{{ $activity['author'] }} &middot; {{ $activity['created_at'] }}</div>@if ($canViewSubmissionSnapshots && filled($activity['submission_id']))<button type="button" class="request-copy" style="margin-top:7px; padding:0;" wire:click="openSubmissionSnapshot({{ (int) $activity['submission_id'] }})">View snapshot</button>@endif</div>
                        @empty
                            <p class="request-empty">No activity has been recorded.</p>
                        @endforelse
                    </div>
                </details>
            </aside>
        </div>
    </div>

    @if ($showSubmissionSnapshotModal && filled($selectedSubmissionSnapshot))
        <div class="request-modal" wire:key="submission-snapshot-modal"><div class="request-modal__dialog">
            <div class="request-card__header"><div><h3 class="request-card__title">Form Answer Changes</h3><p class="request-card__hint">Submission v{{ data_get($selectedSubmissionSnapshot, 'headline.version', '-') }} &middot; {{ data_get($selectedSubmissionSnapshot, 'headline.submitted_at', '-') }} &middot; {{ count($selectedSubmissionSnapshot['form_changes'] ?? []) }} changes</p></div><button type="button" class="request-link" wire:click="closeSubmissionSnapshot">Close</button></div>
            <div class="request-card__body">
                @if (! empty($selectedSubmissionSnapshot['form_changes']))
                    <div class="request-change-table">
                        <div class="request-change-table__cell request-change-table__cell--head">Question / Field</div><div class="request-change-table__cell request-change-table__cell--head">Previous Answer</div><div class="request-change-table__cell request-change-table__cell--head">Current Answer</div>
                        @foreach ($selectedSubmissionSnapshot['form_changes'] as $change)
                            <div class="request-change-table__cell request-change-table__question"><span class="request-field__label" style="display:block; margin-bottom:4px;">{{ $change['group'] }}</span>{{ $change['label'] }}</div>
                            <div class="request-change-table__cell request-change-table__previous">{{ ($change['before'] ?? '-') === '-' ? 'Not previously recorded' : $change['before'] }}</div>
                            <div class="request-change-table__cell request-change-table__current">{{ ($change['after'] ?? '-') === '-' ? 'Cleared / no answer' : $change['after'] }}</div>
                        @endforeach
                    </div>
                @else
                    <p class="request-empty">No form answers changed from the previous saved submission.</p>
                @endif

                <details style="margin-top:16px; border-top:1px solid #e8eef4; padding-top:14px;">
                    <summary style="cursor:pointer; color:#0f766e; font-size:12px; font-weight:800;">View all answers recorded in this submission</summary>
                    <div class="request-snapshot" style="margin-top:12px;">@foreach ($selectedSubmissionSnapshot['answers'] ?? [] as $answer)<div class="request-snapshot__row"><div class="request-snapshot__question">{{ $answer['prompt'] }}</div><div class="request-snapshot__answer">{{ $answer['value'] }}</div></div>@endforeach</div>
                    @if (empty($selectedSubmissionSnapshot['answers']))<p class="request-empty" style="margin-top:10px;">No dynamic question answers were stored in this submission.</p>@endif
                </details>
            </div>
        </div></div>
    @endif

    <script>
        async function copyVerificationQuickReference(text, button) {
            if (!text) return;
            await navigator.clipboard.writeText(text);
            const original = button.textContent;
            button.textContent = 'Copied';
            setTimeout(() => button.textContent = original, 1400);
        }
    </script>
</x-filament-panels::page>
