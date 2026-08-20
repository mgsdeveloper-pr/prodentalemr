<x-filament-panels::page>
    @php($rows = $this->getRows())
    @php($summary = $this->getSummary())
    @php($selectedWorkItem = $this->getSelectedWorkItem())

    <div class="pd-response-workspace">
        <section class="pd-response-summary">
            @foreach ($summary as $card)
                <button
                    type="button"
                    wire:click="selectStatusFilter('{{ $card['filter'] }}')"
                    class="pd-response-summary-item {{ $card['is_active'] ? 'is-active' : '' }}"
                >
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ number_format($card['count']) }}</strong>
                </button>
            @endforeach
        </section>

        <section class="pd-response-table-card">
            <div class="pd-response-toolbar">
                <div class="pd-response-search">
                    <x-heroicon-o-magnifying-glass />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search patient, request, or response"
                    >
                </div>

                <div class="pd-response-filter">
                    <label for="response-status-filter">Status</label>
                    <select id="response-status-filter" wire:model.live="statusFilter">
                        <option value="all">All items</option>
                        <option value="open">Open requests</option>
                        <option value="responded">Responses received</option>
                        <option value="closed">Closed requests</option>
                    </select>
                </div>
            </div>

            <div class="pd-response-table-wrap">
                <table class="pd-response-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Information Requested</th>
                            <th>Clinic Response</th>
                            <th>Last Activity</th>
                            <th>Status</th>
                            <th aria-label="Actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $index => $workItem)
                            @php($row = $this->presentRow($workItem))
                            <tr>
                                <td>
                                    <div class="pd-response-primary-cell">
                                        <strong>{{ $row['patient_name'] }}</strong>
                                        <span>{{ $workItem->reference_number }} · {{ $row['clinic_name'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="pd-response-copy">{{ $row['request_raised'] }}</div>
                                    <div class="pd-response-count">{{ $row['request_count'] }} request{{ $row['request_count'] === 1 ? '' : 's' }}</div>
                                </td>
                                <td>
                                    <div class="pd-response-copy">{{ $row['response_received'] }}</div>
                                    <div class="pd-response-count">{{ $row['response_count'] }} response{{ $row['response_count'] === 1 ? '' : 's' }}</div>
                                </td>
                                <td>{{ $row['date_time'] }}</td>
                                <td>
                                    <span class="pd-response-status" style="--status-border: {{ $row['status_styles']['border'] }}; --status-bg: {{ $row['status_styles']['bg'] }}; --status-text: {{ $row['status_styles']['text'] }};">
                                        {{ $row['status']['label'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="pd-response-actions">
                                        <button
                                            type="button"
                                            wire:click="openDetails({{ $workItem->getKey() }})"
                                            class="pd-response-action pd-response-action-neutral"
                                        >
                                            <x-heroicon-o-eye style="width: 16px; height: 16px;" />
                                            <span>View</span>
                                        </button>

                                        @if ($this->canShowResponseShortcut($workItem))
                                            <button
                                                type="button"
                                                wire:click="openResponseComposer({{ $workItem->getKey() }})"
                                                class="pd-response-action pd-response-action-primary"
                                            >
                                                <x-heroicon-o-pencil-square style="width: 16px; height: 16px;" />
                                                <span>Respond</span>
                                            </button>
                                        @else
                                            <a
                                                href="{{ $this->openWorkItemUrl($workItem) }}"
                                                wire:navigate
                                                class="pd-response-action pd-response-action-primary"
                                            >
                                                <x-heroicon-o-arrow-top-right-on-square style="width: 16px; height: 16px;" />
                                                <span>Open Form</span>
                                            </a>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 56px 24px;">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 14px; text-align: center;">
                                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 68px; height: 68px; border-radius: 22px; background: #f8fbff; border: 1px solid #dbe4ee; color: #64748b;">
                                            <x-heroicon-o-chat-bubble-left-right style="width: 28px; height: 28px;" />
                                        </div>
                                        <div style="font-size: 20px; font-weight: 800; color: #0f172a;">No request activity found</div>
                                        <div style="max-width: 560px; color: #64748b; font-size: 14px; line-height: 1.8;">
                                            No request and response activity matches the current clinic scope and filters. Open a verification and raise a request to start tracking it here.
                                        </div>
                                        <a
                                            href="{{ $this->verificationRequestIndexUrl() }}"
                                            wire:navigate
                                            class="pd-response-action pd-response-action-primary"
                                        >
                                            <x-heroicon-o-plus style="width: 16px; height: 16px;" />
                                            <span>Raise Request</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pd-response-pagination">
                {{ $rows->links() }}
            </div>
        </section>
    </div>

    @if ($showDetailsModal && $selectedWorkItem)
        @php($requestHistory = $this->getRequestHistory($selectedWorkItem))
        @php($responseHistory = $this->getResponseHistory($selectedWorkItem))
        @php($responseAttachments = $this->getResponseAttachments($selectedWorkItem))
        @php($workflowStatus = $this->getWorkflowStatus($selectedWorkItem))
        @php($workflowStatusStyles = $this->workflowStatusStyles($workflowStatus['tone']))
        @php($closureSummary = $this->getClosureSummary($selectedWorkItem))

        <div style="position: fixed; inset: 0; z-index: 80; background: rgba(15, 23, 42, 0.42); display: flex; align-items: center; justify-content: center; padding: 24px;">
            <div style="display: flex; flex-direction: column; width: min(1200px, 100%); max-height: calc(100vh - 48px); border-radius: 28px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 30px 80px rgba(15, 23, 42, 0.26); overflow: hidden;">
                <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 18px;">
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                            {{ $selectedWorkItem->reference_number }}
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 28px; font-weight: 800; color: #0f172a;">{{ $selectedWorkItem->verificationProfile?->patient_full_name ?: ($selectedWorkItem->patient?->full_name ?: 'Unknown patient') }}</h3>
                            <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <span style="font-size: 14px; line-height: 1.7; color: #64748b;">{{ $selectedWorkItem->clinic?->clinic_name ?: '-' }}</span>
                                <span style="display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border-radius: 999px; border: 1px solid {{ $workflowStatusStyles['border'] }}; background: {{ $workflowStatusStyles['bg'] }}; color: {{ $workflowStatusStyles['text'] }}; font-size: 12px; font-weight: 800;">
                                    <span style="width: 7px; height: 7px; border-radius: 999px; background: currentColor;"></span>
                                    {{ $workflowStatus['label'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="closeDetails"
                        style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155;"
                    >
                        <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                    </button>
                </div>

                @if ($closureSummary)
                    <div style="padding: 14px 24px; border-bottom: 1px solid #edf2f7; background: #f0fdf4;">
                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                            <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #bbf7d0; background: #ffffff;">
                                <div style="margin-bottom: 5px; font-size: 11px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; color: #166534;">Closed By</div>
                                <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $closureSummary['closed_by'] }}</div>
                            </div>
                            <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #bbf7d0; background: #ffffff;">
                                <div style="margin-bottom: 5px; font-size: 11px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; color: #166534;">Closed At</div>
                                <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $closureSummary['closed_at'] }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div style="flex: 1 1 auto; overflow: auto; padding: 24px;">
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px;">
                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; overflow: hidden;">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h4 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Request History</h4>
                        </div>
                        <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px; height: 430px; overflow: auto;">
                            @forelse ($requestHistory as $item)
                                @php($messageText = filled($item['message']) ? $item['message'] : $item['message_fallback'])
                                @php($showTitle = filled($item['title']) && trim(mb_strtolower($item['title'])) !== trim(mb_strtolower($messageText)))
                                <div style="height: 190px; min-height: 190px; flex: 0 0 190px; padding: 16px; border-radius: 18px; border: 1px solid #fde68a; background: #fffbeb; display: flex; flex-direction: column; overflow: hidden;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #ffffff; color: #92400e; font-size: 10px; font-weight: 800; border: 1px solid #fcd34d;">{{ $item['source_label'] }}</span>
                                        <span style="font-size: 12px; color: #a16207;">to</span>
                                        <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #ffffff; color: #92400e; font-size: 10px; font-weight: 800; border: 1px solid #fcd34d;">{{ $item['target_label'] }}</span>
                                    </div>
                                    @if ($showTitle)
                                        <div style="font-size: 15px; line-height: 1.5; color: #0f172a; font-weight: 700; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $item['title'] }}</div>
                                    @endif
                                    <div style="margin-top: {{ $showTitle ? '12px' : '4px' }}; display: flex; flex: 1 1 auto; flex-direction: column; gap: 6px; min-height: 0;">
                                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #92400e;">{{ $item['message_label'] }}</div>
                                        <div style="font-size: 14px; line-height: 1.7; color: #475569; display: -webkit-box; -webkit-line-clamp: {{ $showTitle ? '3' : '4' }}; -webkit-box-orient: vertical; overflow: hidden;">{{ $messageText }}</div>
                                    </div>
                                    <div style="margin-top: 10px; font-size: 12px; color: #64748b;">{{ $item['actor'] }} · {{ $item['role'] }} · {{ $item['date'] }}</div>
                                </div>
                            @empty
                                <div style="padding: 10px 0; font-size: 14px; line-height: 1.7; color: #64748b;">No request history has been logged for this verification yet.</div>
                            @endforelse
                        </div>
                    </section>

                    <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; overflow: hidden;">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h4 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Response History</h4>
                        </div>
                        <div style="padding: 18px; display: flex; flex-direction: column; gap: 14px; height: 430px; overflow: auto;">
                            @forelse ($responseHistory as $item)
                                @php($messageText = filled($item['message']) ? $item['message'] : $item['message_fallback'])
                                @php($showTitle = filled($item['title']) && trim(mb_strtolower($item['title'])) !== trim(mb_strtolower($messageText)))
                                <div style="height: 190px; min-height: 190px; flex: 0 0 190px; padding: 16px; border-radius: 18px; border: 1px solid #bfdbfe; background: #f8fbff; display: flex; flex-direction: column; overflow: hidden;">
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 10px;">
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #ffffff; color: #1d4ed8; font-size: 10px; font-weight: 800; border: 1px solid #bfdbfe;">{{ $item['source_label'] }}</span>
                                            <span style="font-size: 12px; color: #64748b;">to</span>
                                            <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #ffffff; color: #1d4ed8; font-size: 10px; font-weight: 800; border: 1px solid #bfdbfe;">{{ $item['target_label'] }}</span>
                                        </div>
                                        @if ($loop->first && $this->canShowResponseEdit($selectedWorkItem))
                                            <button
                                                type="button"
                                                wire:click="openResponseComposer({{ $selectedWorkItem->getKey() }})"
                                                style="display: inline-flex; align-items: center; gap: 6px; flex: 0 0 auto; padding: 6px 9px; border-radius: 999px; border: 1px solid #bfdbfe; background: #ffffff; color: #1d4ed8; font-size: 11px; font-weight: 800;"
                                            >
                                                <x-heroicon-o-pencil-square style="width: 13px; height: 13px;" />
                                                Edit Response
                                            </button>
                                        @endif
                                    </div>
                                    @if ($showTitle)
                                        <div style="font-size: 15px; line-height: 1.5; color: #0f172a; font-weight: 700; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $item['title'] }}</div>
                                    @endif
                                    <div style="margin-top: {{ $showTitle ? '12px' : '4px' }}; display: flex; flex: 1 1 auto; flex-direction: column; gap: 6px; min-height: 0;">
                                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #1d4ed8;">{{ $item['message_label'] }}</div>
                                        <div style="font-size: 14px; line-height: 1.7; color: #475569; display: -webkit-box; -webkit-line-clamp: {{ $showTitle ? '3' : '4' }}; -webkit-box-orient: vertical; overflow: hidden;">{{ $messageText }}</div>
                                    </div>
                                    <div style="margin-top: 10px; font-size: 12px; color: #64748b;">{{ $item['actor'] }} · {{ $item['role'] }} · {{ $item['date'] }}</div>
                                    @if ($loop->first && $responseAttachments->isNotEmpty())
                                        <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
                                            @foreach ($responseAttachments as $attachment)
                                                @php($attachmentLabel = $attachment->original_file_name ?: $attachment->title ?: ('Uploaded document ' . $loop->iteration))
                                                <button
                                                    type="button"
                                                    wire:click="openResponseAttachmentPreview({{ $attachment->getKey() }})"
                                                    style="display: inline-flex; align-items: center; gap: 6px; max-width: 100%; padding: 7px 10px; border-radius: 999px; border: 1px solid #bfdbfe; background: #ffffff; color: #1d4ed8; font-size: 12px; font-weight: 800; text-decoration: none; cursor: pointer;"
                                                >
                                                    <x-heroicon-o-paper-clip style="width: 14px; height: 14px; flex: 0 0 auto;" />
                                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        {{ $attachmentLabel }}
                                                    </span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div style="padding: 10px 0; font-size: 14px; line-height: 1.7; color: #64748b;">No response has been received for this verification yet.</div>
                            @endforelse
                        </div>
                    </section>
                    </div>
                </div>

                <div style="padding: 18px 24px 20px; border-top: 1px solid #edf2f7; display: flex; align-items: center; justify-content: flex-end; gap: 12px; background: #ffffff;">
                    <button
                        type="button"
                        wire:click="closeDetails"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 700;"
                    >
                        Close
                    </button>
                    @if ($this->canShowRequestShortcut($selectedWorkItem))
                        <button
                            type="button"
                            wire:click="openRequestComposer({{ $selectedWorkItem->getKey() }})"
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 14px; font-weight: 700; text-decoration: none;"
                        >
                            {{ $this->requestActionLabel($selectedWorkItem) }}
                        </button>
                    @endif
                    @if ($this->canShowResponseShortcut($selectedWorkItem))
                        <button
                            type="button"
                            wire:click="openResponseComposer({{ $selectedWorkItem->getKey() }})"
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 14px; font-weight: 700; text-decoration: none;"
                        >
                            Add Response
                        </button>
                    @endif
                    <a
                        href="{{ $this->openWorkItemUrl($selectedWorkItem) }}"
                        wire:navigate
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 14px; border: 1px solid #0f766e; background: linear-gradient(180deg, #14b8a6 0%, #0f766e 100%); color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none;"
                    >
                        Open Verification
                    </a>
                </div>

            </div>
        </div>
    @endif

    @php($selectedResponseAttachment = $this->getSelectedResponseAttachment())
    @if ($showResponseAttachmentPreview && $selectedResponseAttachment && $selectedWorkItem)
        @php($attachmentTitle = $selectedResponseAttachment->original_file_name ?: $selectedResponseAttachment->title ?: 'Uploaded document')
        @php($attachmentUrl = $this->responseAttachmentDownloadUrl($selectedResponseAttachment))
        @php($attachmentPreviewUrl = $this->responseAttachmentPreviewUrl($selectedResponseAttachment))
        @php($attachmentMimeType = (string) ($selectedResponseAttachment->mime_type ?? ''))
        @php($isImageAttachment = str_starts_with($attachmentMimeType, 'image/'))
        @php($isPdfAttachment = $attachmentMimeType === 'application/pdf')

        <div
            wire:click.self="closeResponseAttachmentPreview"
            style="position: fixed; inset: 0; z-index: 100; background: rgba(15, 23, 42, 0.54); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: clamp(12px, 2.4vw, 28px);"
        >
            <section style="width: min(1120px, 96vw); max-height: min(880px, 94vh); border-radius: 26px; background: #ffffff; box-shadow: 0 28px 80px rgba(15, 23, 42, 0.32); overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 16px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div style="min-width: 0;">
                        <div style="display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase;">
                            Response Document
                        </div>
                        <h3 style="margin: 8px 0 0; font-size: 22px; font-weight: 900; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $attachmentTitle }}</h3>
                        <p style="margin: 6px 0 0; font-size: 13px; color: #64748b;">
                            {{ $selectedWorkItem->verificationProfile?->patient_full_name ?: ($selectedWorkItem->patient?->full_name ?: 'Unknown patient') }}
                            &middot; {{ $selectedWorkItem->clinic?->clinic_name ?: '-' }}
                            &middot; {{ optional($selectedResponseAttachment->created_at)->format('M d, Y h:i A') ?: '-' }}
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                        <a href="{{ $attachmentPreviewUrl }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 900; text-decoration: none;">Open New Tab</a>
                        <a href="{{ $attachmentUrl }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 900; text-decoration: none;">Download</a>
                        <button type="button" wire:click="closeResponseAttachmentPreview" style="width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 22px; line-height: 1; cursor: pointer;">&times;</button>
                    </div>
                </div>

                <div style="flex: 1; background: #f8fafc; padding: clamp(10px, 1.8vw, 18px); min-height: 0; overflow: auto;">
                    @if ($isImageAttachment)
                        <div style="display: flex; align-items: center; justify-content: center; min-height: min(640px, 70vh); border: 1px solid #dbe4ee; border-radius: 18px; background: #ffffff; overflow: auto;">
                            <img
                                src="{{ $attachmentPreviewUrl }}"
                                alt="{{ $attachmentTitle }}"
                                style="display: block; width: auto; height: auto; max-width: 100%; max-height: 72vh; object-fit: contain;"
                            >
                        </div>
                    @elseif ($isPdfAttachment)
                        <iframe
                            src="{{ $attachmentPreviewUrl }}"
                            title="{{ $attachmentTitle }}"
                            style="display: block; width: 100%; height: min(700px, 72vh); border: 1px solid #dbe4ee; border-radius: 18px; background: #ffffff;"
                        ></iframe>
                    @else
                        <div style="display: flex; min-height: min(420px, 64vh); align-items: center; justify-content: center; border: 1px solid #dbe4ee; border-radius: 18px; background: #ffffff; padding: 28px; text-align: center;">
                            <div>
                                <div style="font-size: 18px; font-weight: 900; color: #0f172a;">Preview is not available for this file type</div>
                                <div style="margin-top: 8px; font-size: 13px; line-height: 1.7; color: #64748b;">Open the file in a new tab or download it to review the clinic response document.</div>
                                <div style="margin-top: 18px; display: inline-flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                                    <a href="{{ $attachmentPreviewUrl }}" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 900; text-decoration: none;">Open New Tab</a>
                                    <a href="{{ $attachmentUrl }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 900; text-decoration: none;">Download</a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    @endif

    @if ($showResponseComposerModal && filled($responseComposerWorkItemId) && $selectedWorkItem)
        <div
            id="verification-response-composer-modal"
            wire:key="verification-response-composer-{{ $responseComposerWorkItemId }}"
            style="position: fixed; inset: 0; z-index: 90; background: rgba(15, 23, 42, 0.22); display: flex; align-items: center; justify-content: center; padding: 24px;"
        >
            <div style="position: relative; width: min(720px, 100%); border-radius: 26px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #1d4ed8;">Clinic Response</div>
                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Add Response</h3>
                        <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Send the requested information back to the verification team so they can continue the verification.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeResponseComposer"
                        wire:loading.attr="disabled"
                        wire:target="sendClinicResponse"
                        style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px;"
                    >
                        <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                    </button>
                </div>

                <div style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                        <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fbff;">
                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Patient</div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $selectedWorkItem->verificationProfile?->patient_full_name ?: ($selectedWorkItem->patient?->full_name ?: 'Unknown patient') }}</div>
                        </div>
                        <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fbff;">
                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Reference</div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $selectedWorkItem->reference_number ?: '-' }}</div>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">
                            Response Details
                        </label>
                        <textarea
                            wire:model.live="responseComposerNote"
                            placeholder="Add the requested information here."
                            style="width: 100%; min-height: 180px; padding: 14px 16px; border: 1px solid #d6dde8; border-radius: 16px; background: #ffffff; color: #0f172a; font-size: 14px; line-height: 1.7; resize: vertical;"
                        ></textarea>
                        @error('responseComposerNote')
                            <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">
                            Supporting Document or Image
                        </label>
                        <label style="display: flex; align-items: center; gap: 14px; padding: 16px; border: 1px dashed #bfdbfe; border-radius: 18px; background: #f8fbff; color: #334155; cursor: pointer;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 14px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                <x-heroicon-o-paper-clip style="width: 20px; height: 20px;" />
                            </span>
                            <span style="display: flex; flex-direction: column; gap: 4px;">
                                <span style="font-size: 14px; font-weight: 800; color: #0f172a;">Upload files if required</span>
                                <span style="font-size: 12px; color: #64748b;">PDF, image, DOC, or DOCX. Max 10 MB each.</span>
                            </span>
                            <input
                                type="file"
                                wire:model="responseComposerAttachments"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                style="display: none;"
                            >
                        </label>

                        <div wire:loading wire:target="responseComposerAttachments" style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #1d4ed8;">
                            Uploading attachment...
                        </div>

                        @if (! empty($responseComposerAttachments))
                            <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
                                @foreach ($responseComposerAttachments as $attachment)
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700;">
                                        <x-heroicon-o-document style="width: 14px; height: 14px;" />
                                        {{ method_exists($attachment, 'getClientOriginalName') ? $attachment->getClientOriginalName() : 'Attachment' }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @error('responseComposerAttachments.*')
                            <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="padding: 18px 22px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 12px;">
                    <button
                        type="button"
                        wire:click="closeResponseComposer"
                        wire:loading.attr="disabled"
                        wire:target="sendClinicResponse"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 132px; padding: 12px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 13px; font-weight: 800;"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="sendClinicResponse"
                        wire:loading.attr="disabled"
                        wire:target="sendClinicResponse"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 172px; padding: 12px 16px; border-radius: 14px; border: 1px solid #0f766e; background: linear-gradient(180deg, #14b8a6 0%, #0f766e 100%); color: #ffffff; font-size: 13px; font-weight: 800;"
                    >
                        Send Response
                    </button>
                </div>

                <div
                    wire:loading.flex
                    wire:target="sendClinicResponse"
                    style="position: absolute; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.82); backdrop-filter: blur(2px); z-index: 5;"
                >
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 24px 28px; border-radius: 22px; border: 1px solid #dbe4ee; background: rgba(255, 255, 255, 0.96); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);">
                        <div style="width: 42px; height: 42px; border-radius: 999px; border: 3px solid #ccfbf1; border-top-color: #0f766e; animation: verification-request-spin 0.8s linear infinite;"></div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">Sending response. Please wait...</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showRequestComposerModal && $selectedWorkItem)
        <div id="verification-request-composer-modal" style="position: fixed; inset: 0; z-index: 90; background: rgba(15, 23, 42, 0.22); display: flex; align-items: center; justify-content: center; padding: 24px;">
            <div style="position: relative; width: min(720px, 100%); border-radius: 26px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24); overflow: hidden;">
                <div style="padding: 20px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 11px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #1d4ed8;">Verification Follow-Up</div>
                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">{{ $this->requestActionLabel($selectedWorkItem) }}</h3>
                        <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Ask the clinic exactly what is missing so the verification team can continue without leaving this workflow.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeRequestComposer"
                        wire:loading.attr="disabled"
                        wire:target="sendRequestToClinic"
                        style="display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 20px;"
                    >
                        <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                    </button>
                </div>

                <div style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                        <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fbff;">
                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Patient</div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $selectedWorkItem->verificationProfile?->patient_full_name ?: ($selectedWorkItem->patient?->full_name ?: 'Unknown patient') }}</div>
                        </div>
                        <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #dbe4ee; background: #f8fbff;">
                            <div style="margin-bottom: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Clinic</div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $selectedWorkItem->clinic?->clinic_name ?: '-' }}</div>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">
                            Information Asked
                        </label>
                        <textarea
                            wire:model.live="requestComposerReason"
                            placeholder="Example: Please upload the updated insurance card and confirm the subscriber date of birth before verification can continue."
                            style="width: 100%; min-height: 180px; padding: 14px 16px; border: 1px solid #d6dde8; border-radius: 16px; background: #ffffff; color: #0f172a; font-size: 14px; line-height: 1.7; resize: vertical;"
                        ></textarea>
                        @error('requestComposerReason')
                            <div style="margin-top: 8px; font-size: 12px; font-weight: 700; color: #be123c;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="padding: 18px 22px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 12px;">
                    <button
                        type="button"
                        wire:click="closeRequestComposer"
                        wire:loading.attr="disabled"
                        wire:target="sendRequestToClinic"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 132px; padding: 12px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; font-size: 13px; font-weight: 800;"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="sendRequestToClinic"
                        wire:loading.attr="disabled"
                        wire:target="sendRequestToClinic"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 172px; padding: 12px 16px; border-radius: 14px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 800;"
                    >
                        Send to Clinic
                    </button>
                </div>

                <div
                    wire:loading.flex
                    wire:target="sendRequestToClinic"
                    style="position: absolute; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.82); backdrop-filter: blur(2px); z-index: 5;"
                >
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 24px 28px; border-radius: 22px; border: 1px solid #dbe4ee; background: rgba(255, 255, 255, 0.96); box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);">
                        <div style="width: 42px; height: 42px; border-radius: 999px; border: 3px solid #dbeafe; border-top-color: #2563eb; animation: verification-request-spin 0.8s linear infinite;"></div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">Sending request. Please wait...</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .pd-response-workspace {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .pd-response-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            overflow: hidden;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            background: #ffffff;
        }

        .pd-response-summary-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 62px;
            padding: 14px 16px;
            border: 0;
            border-right: 1px solid #e7edf4;
            background: #ffffff;
            color: #475569;
            text-align: left;
        }

        .pd-response-summary-item:last-child {
            border-right: 0;
        }

        .pd-response-summary-item.is-active {
            box-shadow: inset 0 -2px 0 #0f766e;
            background: #f0fdfa;
            color: #0f766e;
        }

        .pd-response-summary-item span {
            font-size: 12px;
            font-weight: 700;
        }

        .pd-response-summary-item strong {
            font-size: 20px;
            font-weight: 800;
        }

        .pd-response-table-card {
            overflow: hidden;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            background: #ffffff;
        }

        .pd-response-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #e7edf4;
        }

        .pd-response-search {
            position: relative;
            width: min(360px, 100%);
        }

        .pd-response-search svg {
            position: absolute;
            left: 12px;
            top: 50%;
            width: 17px;
            height: 17px;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .pd-response-search input,
        .pd-response-filter select {
            width: 100%;
            height: 40px;
            border: 1px solid #d6dde8;
            border-radius: 8px;
            background: #ffffff;
            color: #0f172a;
            font-size: 13px;
        }

        .pd-response-search input {
            padding: 8px 12px 8px 38px;
        }

        .pd-response-filter {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pd-response-filter label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .pd-response-filter select {
            min-width: 170px;
            padding: 8px 12px;
        }

        .pd-response-table-wrap {
            overflow-x: auto;
        }

        .pd-response-table {
            width: 100%;
            min-width: 920px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .pd-response-table th {
            padding: 12px 14px;
            border-bottom: 1px solid #dbe4ee;
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-align: left;
        }

        .pd-response-table th:nth-child(1) { width: 20%; }
        .pd-response-table th:nth-child(2) { width: 18%; }
        .pd-response-table th:nth-child(3) { width: 18%; }
        .pd-response-table th:nth-child(4) { width: 14%; }
        .pd-response-table th:nth-child(5) { width: 10%; }
        .pd-response-table th:nth-child(6) { width: 20%; }

        .pd-response-table td {
            padding: 14px;
            border-bottom: 1px solid #e7edf4;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .pd-response-primary-cell,
        .pd-response-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 4px;
        }

        .pd-response-primary-cell strong {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
        }

        .pd-response-primary-cell span,
        .pd-response-count {
            color: #64748b;
            font-size: 11px;
        }

        .pd-response-copy {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-height: 1.45;
        }

        .pd-response-count {
            margin-top: 5px;
        }

        .pd-response-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border: 1px solid var(--status-border);
            border-radius: 999px;
            background: var(--status-bg);
            color: var(--status-text);
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .pd-response-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 7px;
            white-space: nowrap;
        }

        .pd-response-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 34px;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .pd-response-action-neutral {
            border: 1px solid #d6dde8;
            background: #ffffff;
            color: #334155;
        }

        .pd-response-action-primary {
            border: 1px solid #0f766e;
            background: #0f766e;
            color: #ffffff;
        }

        .pd-response-pagination {
            padding: 12px 16px;
            border-top: 1px solid #e7edf4;
        }

        @keyframes verification-request-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1180px) {
            .pd-response-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .pd-response-summary-item:nth-child(2) {
                border-right: 0;
            }
        }

        @media (max-width: 960px) {
            .fi-main div[style*="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px;"] {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media (max-width: 720px) {
            .pd-response-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .pd-response-summary {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            .pd-response-summary-item {
                border-right: 0;
                border-bottom: 1px solid #e7edf4;
            }

            .pd-response-summary-item:last-child {
                border-bottom: 0;
            }

            .pd-response-search {
                width: 100%;
            }

            .pd-response-filter {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('verification-request-composer-closed', () => {
                const modal = document.getElementById('verification-request-composer-modal');

                if (modal) {
                    modal.style.display = 'none';
                }
            });

        });
    </script>
</x-filament-panels::page>
