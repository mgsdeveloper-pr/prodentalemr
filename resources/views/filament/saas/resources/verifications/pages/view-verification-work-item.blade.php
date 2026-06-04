<x-filament-panels::page>
    @php
        $record = $this->getRecord();
        $summaryCards = $this->getWorkbenchSummary();
        $verificationPanels = $this->getVerificationPanels();
        $planSnapshots = $this->getPlanSnapshots();
        $notes = $this->getClientVisibleNotes();
        $attachments = $this->getAttachmentCards();
        $activities = $this->getActivityTimeline();
        $quickReference = $this->getQuickReferenceCard();
        $canViewSubmissionSnapshots = $this->canViewSubmissionSnapshots();
        $selectedSubmissionSnapshot = $this->selectedSubmissionSnapshot;
        $quickReferenceCopyText = implode("\n", array_filter([
            'Patient: ' . ($quickReference['patient'] ?? ''),
            'DOB: ' . ($quickReference['dob'] ?? ''),
            'Member ID: ' . ($quickReference['member_id'] ?? ''),
            'Provider NPI: ' . ($quickReference['provider_npi'] ?? ''),
            'Practice NPI: ' . ($quickReference['practice_npi'] ?? ''),
            'Phone: ' . ($quickReference['phone'] ?? ''),
        ]));

        $toneStyles = [
            'slate' => 'border: 1px solid #cbd5e1; background: #f8fafc; color: #334155;',
            'sky' => 'border: 1px solid #bae6fd; background: #eff6ff; color: #0369a1;',
            'amber' => 'border: 1px solid #fed7aa; background: #fff7ed; color: #b45309;',
            'rose' => 'border: 1px solid #fecdd3; background: #fff1f2; color: #be123c;',
            'emerald' => 'border: 1px solid #bbf7d0; background: #ecfdf5; color: #15803d;',
            'indigo' => 'border: 1px solid #c7d2fe; background: #eef2ff; color: #4338ca;',
            'cyan' => 'border: 1px solid #a5f3fc; background: #ecfeff; color: #0f766e;',
            'violet' => 'border: 1px solid #ddd6fe; background: #f5f3ff; color: #7c3aed;',
        ];
    @endphp

    <style>
        .verification-view-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(320px, 0.85fr);
            gap: 22px;
            padding: 28px;
        }

        .verification-view-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .verification-view-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(360px, 0.9fr);
            gap: 24px;
            align-items: start;
        }

        .verification-view-copy {
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

        .verification-view-sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .verification-view-sidebar-card {
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .verification-view-sidebar-card__header {
            padding: 16px 18px;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .verification-view-sidebar-card__body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .verification-view-sidebar-card__title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: #111827;
        }

        .verification-view-sidebar-card__title--eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #10b981;
        }

        .verification-view-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid #dbe4ee;
            background: #ffffff;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .verification-view-button:hover {
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .verification-view-button--soft {
            border-color: #c7d2fe;
            background: #eef2ff;
            color: #4338ca;
        }

        .verification-view-button--soft:hover {
            border-color: #a5b4fc;
            color: #3730a3;
        }

        .verification-view-quick-reference {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 12px;
        }

        .verification-view-quick-reference__item {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
            padding: 12px;
        }

        .verification-view-context-group {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
            padding: 12px 14px;
        }

        .verification-view-context-rows {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .verification-view-context-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
        }

        .verification-view-context-row:first-child {
            padding-top: 0;
            border-top: none;
        }

        .verification-view-timeline {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .verification-view-timeline__item {
            position: relative;
            padding-left: 18px;
        }

        .verification-view-timeline__dot {
            position: absolute;
            left: 0;
            top: 8px;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #06b6d4;
        }

        @media (max-width: 1280px) {
            .verification-view-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 1120px) {
            .verification-view-hero,
            .verification-view-layout {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 768px) {
            .verification-view-summary-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .verification-view-quick-reference {
                grid-template-columns: minmax(0, 1fr);
            }

            .verification-view-context-row {
                flex-direction: column;
            }
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 26px; overflow: hidden; background: linear-gradient(135deg, #0f172a 0%, #12263a 56%, #0f3a4a 100%); color: #ffffff; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);">
            <div class="verification-view-hero">
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <span style="display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; border: 1px solid rgba(34,211,238,0.28); background: rgba(34,211,238,0.12); color: #a5f3fc; font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase;">
                            Verification Operations
                        </span>
                        <span style="display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.08); color: #e2e8f0; font-size: 12px; font-weight: 600;">
                            {{ $record->reference_number }}
                        </span>
                    </div>

                    <div>
                        <h2 style="margin: 0; font-size: 34px; line-height: 1.1; font-weight: 700; color: #ffffff;">
                            {{ $record->title }}
                        </h2>
                        <p style="margin: 12px 0 0; max-width: 880px; font-size: 15px; line-height: 1.7; color: #cbd5e1;">
                            Review the incoming verification request, supporting context, and payer evidence from one operational workspace before the team completes or writes back the result.
                        </p>
                    </div>

                    <div class="verification-view-summary-grid">
                        @foreach ($summaryCards as $card)
                            <div style="border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; background: rgba(255,255,255,0.06); padding: 16px;">
                                <div style="margin-bottom: 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #cbd5e1;">
                                    {{ $card['label'] }}
                                </div>
                                <span style="display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; {{ $toneStyles[$card['tone']] ?? $toneStyles['slate'] }}">
                                    {{ $card['value'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; background: rgba(255,255,255,0.06); padding: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px;">
                        <h3 style="margin: 0; font-size: 12px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #cbd5e1;">
                            Queue Snapshot
                        </h3>
                        <span style="display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.08); color: #e2e8f0; font-size: 12px; font-weight: 600;">
                            {{ $record->managedBillingService?->name ?? 'Verification' }}
                        </span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; gap: 18px;">
                            <div style="font-size: 13px; color: #94a3b8;">Client</div>
                            <div style="font-size: 13px; font-weight: 600; color: #ffffff; text-align: right;">{{ $record->organization?->name ?? '-' }}</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 18px;">
                            <div style="font-size: 13px; color: #94a3b8;">Appointment</div>
                            <div style="font-size: 13px; font-weight: 600; color: #ffffff; text-align: right;">{{ optional($record->verificationProfile?->appointment_date)->format('M d, Y') ?: '-' }}</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 18px;">
                            <div style="font-size: 13px; color: #94a3b8;">Assigned to</div>
                            <div style="font-size: 13px; font-weight: 600; color: #ffffff; text-align: right;">{{ $record->assignedTo?->name ?? 'Queue' }}</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 18px;">
                            <div style="font-size: 13px; color: #94a3b8;">Reviewer</div>
                            <div style="font-size: 13px; font-weight: 600; color: #ffffff; text-align: right;">{{ $record->reviewedBy?->name ?? '-' }}</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 18px;">
                            <div style="font-size: 13px; color: #94a3b8;">Due at</div>
                            <div style="font-size: 13px; font-weight: 600; color: #ffffff; text-align: right;">{{ optional($record->due_at)->format('M d, Y h:i A') ?: '-' }}</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 18px;">
                            <div style="font-size: 13px; color: #94a3b8;">Writeback</div>
                            <div style="font-size: 13px; font-weight: 600; color: #ffffff; text-align: right;">{{ \App\Models\BillingWorkItem::WRITEBACK_STATUS_OPTIONS[$record->writeback_status] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="verification-view-layout">
            <div style="display: flex; flex-direction: column; gap: 20px;">
                @foreach ($verificationPanels as $panel)
                    <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);">
                        <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #111827;">{{ $panel['title'] }}</h3>
                        </div>

                        <div style="padding: 18px; display: flex; flex-direction: column; gap: 16px;">
                            @if (!empty($panel['items']))
                                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;">
                                    @foreach ($panel['items'] as $item)
                                        <div style="border: 1px solid #e5e7eb; border-radius: 18px; background: #f8fafc; padding: 14px 16px;">
                                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">{{ $item['label'] }}</div>
                                            <div style="font-size: 14px; font-weight: 600; color: #111827; line-height: 1.5;">{{ $item['value'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty($panel['notes']))
                                <div style="border: 1px solid #e5e7eb; border-radius: 18px; background: #f8fafc; padding: 16px;">
                                    <div style="margin-bottom: 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">{{ $panel['notes']['label'] }}</div>
                                    <div style="font-size: 14px; line-height: 1.7; color: #334155; white-space: pre-line;">{{ $panel['notes']['value'] }}</div>
                                </div>
                            @endif

                            @if (!empty($panel['rich']))
                                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;">
                                    @foreach ($panel['rich'] as $block)
                                        <div style="border: 1px solid #e5e7eb; border-radius: 18px; background: #f8fafc; padding: 16px;">
                                            <div style="margin-bottom: 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;">{{ $block['label'] }}</div>
                                            <div style="font-size: 14px; line-height: 1.7; color: #334155; white-space: pre-line;">{{ $block['value'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>
                @endforeach

                <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);">
                    <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                        <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #111827;">Insurance Plans Shared at Intake</h3>
                    </div>

                    <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px;">
                        @forelse ($planSnapshots as $plan)
                            <article style="border: 1px solid #e5e7eb; border-radius: 20px; background: #f8fafc; padding: 16px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 14px;">
                                    <div style="font-size: 16px; font-weight: 700; color: #111827;">{{ $plan['priority'] }} Plan</div>
                                    <span style="display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; border: 1px solid #d1d5db; background: #ffffff; color: #374151; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                                        {{ $plan['payer_name'] }}
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;">
                                    <div>
                                        <div style="margin-bottom: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Member ID</div>
                                        <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ $plan['member_id'] }}</div>
                                    </div>
                                    <div>
                                        <div style="margin-bottom: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Group Number</div>
                                        <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ $plan['group_number'] }}</div>
                                    </div>
                                    <div>
                                        <div style="margin-bottom: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Subscriber DOB</div>
                                        <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ $plan['subscriber_dob'] }}</div>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <div style="margin-bottom: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Subscriber Name</div>
                                        <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ $plan['subscriber_name'] }}</div>
                                    </div>
                                </div>

                                @if (filled($plan['notes']))
                                    <div style="margin-top: 14px; border: 1px solid #e5e7eb; border-radius: 16px; background: #ffffff; padding: 14px; font-size: 14px; line-height: 1.6; color: #334155;">
                                        {{ $plan['notes'] }}
                                    </div>
                                @endif
                            </article>
                        @empty
                            <p style="margin: 0; font-size: 14px; color: #64748b;">No insurance plan details were attached at intake.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="verification-view-sidebar">
                <section class="verification-view-sidebar-card">
                    <div class="verification-view-sidebar-card__header">
                        <h3 class="verification-view-sidebar-card__title verification-view-sidebar-card__title--eyebrow">Quick Reference</h3>
                        <button type="button" class="verification-view-copy" onclick="copyVerificationQuickReference(@js($quickReferenceCopyText), this)">Copy all</button>
                    </div>
                    <div class="verification-view-sidebar-card__body verification-view-quick-reference">
                        <div class="verification-view-quick-reference__item">
                            <div style="margin-bottom: 5px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Patient</div>
                            <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $quickReference['patient'] }}</div>
                        </div>
                        <div class="verification-view-quick-reference__item">
                            <div style="margin-bottom: 5px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">DOB</div>
                            <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $quickReference['dob'] }}</div>
                        </div>
                        <div class="verification-view-quick-reference__item">
                            <div style="margin-bottom: 5px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Member ID</div>
                            <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $quickReference['member_id'] }}</div>
                        </div>
                        <div class="verification-view-quick-reference__item">
                            <div style="margin-bottom: 5px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Provider NPI</div>
                            <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $quickReference['provider_npi'] }}</div>
                        </div>
                        <div class="verification-view-quick-reference__item">
                            <div style="margin-bottom: 5px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Practice NPI</div>
                            <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $quickReference['practice_npi'] }}</div>
                        </div>
                        <div class="verification-view-quick-reference__item">
                            <div style="margin-bottom: 5px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;">Phone</div>
                            <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $quickReference['phone'] }}</div>
                        </div>
                    </div>
                </section>

                <section style="border: 1px solid #e5e7eb; border-radius: 24px; background: #ffffff; overflow: hidden; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);">
                    <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7;">
                        <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #111827;">Notes</h3>
                    </div>
                    <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px;">
                        @forelse ($notes as $note)
                            <article style="border: 1px solid #e5e7eb; border-radius: 18px; background: #f8fafc; padding: 14px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
                                    <span style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; {{ $note->visibility === 'client_visible' ? 'border: 1px solid #bbf7d0; background: #ecfdf5; color: #15803d;' : 'border: 1px solid #cbd5e1; background: #ffffff; color: #475569;' }}">
                                        {{ str($note->visibility)->replace('_', ' ')->title() }}
                                    </span>
                                    <span style="font-size: 12px; color: #94a3b8;">{{ optional($note->created_at)->format('M d, Y h:i A') }}</span>
                                </div>
                                <div style="font-size: 14px; line-height: 1.7; color: #334155; white-space: pre-line;">{{ $note->body }}</div>
                                <div style="margin-top: 10px; font-size: 12px; font-weight: 600; color: #64748b;">{{ $note->user?->name ?? 'System' }}</div>
                            </article>
                        @empty
                            <p style="margin: 0; font-size: 14px; color: #64748b;">No queue notes have been added yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="verification-view-sidebar-card">
                    <div class="verification-view-sidebar-card__header">
                        <h3 class="verification-view-sidebar-card__title">Attachments</h3>
                    </div>
                    <div class="verification-view-sidebar-card__body">
                        @forelse ($attachments as $attachment)
                            <article style="border: 1px solid #e5e7eb; border-radius: 16px; background: #f8fafc; padding: 12px 14px;">
                                <div style="margin-bottom: 4px; font-size: 14px; font-weight: 700; color: #111827;">{{ $attachment['title'] }}</div>
                                <div style="margin-bottom: 12px; font-size: 12px; color: #64748b;">{{ $attachment['subtitle'] }}</div>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                                    <span style="font-size: 12px; color: #94a3b8;">{{ $attachment['uploaded_at'] }}</span>
                                    <a href="{{ $attachment['download_url'] }}" class="verification-view-button">
                                        Download
                                    </a>
                                </div>
                            </article>
                        @empty
                            <p style="margin: 0; font-size: 14px; color: #64748b;">No attachments have been uploaded for this verification yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="verification-view-sidebar-card">
                    <div class="verification-view-sidebar-card__header">
                        <h3 class="verification-view-sidebar-card__title">Full Workflow Timeline</h3>
                    </div>
                    <div class="verification-view-sidebar-card__body verification-view-timeline">
                        @forelse ($activities as $activity)
                            <article class="verification-view-timeline__item">
                                <span class="verification-view-timeline__dot"></span>
                                <div style="border: 1px solid #e5e7eb; border-radius: 16px; background: #f8fafc; padding: 12px 14px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
                                        <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $activity['type'] }}</div>
                                        <div style="font-size: 12px; color: #94a3b8;">{{ $activity['created_at'] }}</div>
                                    </div>
                                    <div style="font-size: 14px; line-height: 1.7; color: #334155;">{{ $activity['description'] }}</div>
                                    @if (filled($activity['details']))
                                        <div style="margin-top: 10px; border: 1px solid #e5e7eb; border-radius: 14px; background: #ffffff; padding: 12px; font-size: 13px; line-height: 1.7; color: #475569; white-space: pre-line;">
                                            {{ $activity['details'] }}
                                        </div>
                                    @endif
                                    @if ($canViewSubmissionSnapshots && filled($activity['submission_id']))
                                        <div style="margin-top: 10px;">
                                            <button
                                                type="button"
                                                wire:click="openSubmissionSnapshot({{ (int) $activity['submission_id'] }})"
                                                class="verification-view-button verification-view-button--soft"
                                            >
                                                View Snapshot
                                            </button>
                                        </div>
                                    @endif
                                    <div style="margin-top: 8px; font-size: 12px; font-weight: 600; color: #64748b;">{{ $activity['author'] }}</div>
                                </div>
                            </article>
                        @empty
                            <p style="margin: 0; font-size: 14px; color: #64748b;">No activity has been logged yet.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
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
