<x-filament-panels::page>
    @php($messages = $this->getMessages())
    @php($selectedMessage = $this->getSelectedMessage())
    @php($folderCounts = $this->getFolderCounts())
    @php($status = $this->getConnectionStatus())

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08); overflow: hidden;">
            <div class="verification-inbox-header" style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: flex-start; gap: 18px;">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                        Clinic Inbox
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 32px; font-weight: 800; color: #0f172a;">Inbox</h2>
                        <p style="margin: 10px 0 0; max-width: 980px; font-size: 15px; line-height: 1.75; color: #64748b;">
                            Review inbox and spam together, surface portal OTP emails quickly, and keep a synced operations trail for the selected clinic scope.
                        </p>
                        <p style="margin: 8px 0 0; font-size: 13px; font-weight: 700; color: #0f172a;">
                            Scope: {{ \App\Support\AdminClinicScope::selectedClinic()?->clinic_name ?? 'All accessible clinics' }}
                        </p>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end; justify-self: end;">
                    <div style="display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 16px; border: 1px solid {{ $status['tone'] === 'success' ? '#86efac' : ($status['tone'] === 'warning' ? '#fde68a' : '#fecaca') }}; background: {{ $status['tone'] === 'success' ? '#f0fdf4' : ($status['tone'] === 'warning' ? '#fffbeb' : '#fef2f2') }}; color: {{ $status['tone'] === 'success' ? '#166534' : ($status['tone'] === 'warning' ? '#92400e' : '#b91c1c') }};">
                        <span style="width: 10px; height: 10px; border-radius: 999px; background: currentColor;"></span>
                        <span style="font-size: 13px; font-weight: 800;">{{ $status['label'] }}</span>
                    </div>
                    <button
                        type="button"
                        wire:click="refreshInbox"
                        style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 16px; border-radius: 14px; border: 1px solid #0f766e; background: linear-gradient(180deg, #14b8a6 0%, #0f766e 100%); color: #ffffff; font-size: 13px; font-weight: 700; box-shadow: 0 10px 22px rgba(15, 118, 110, 0.16);"
                    >
                        <x-heroicon-o-arrow-path style="width: 16px; height: 16px;" />
                        <span>Refresh Inbox</span>
                    </button>
                </div>
            </div>

        </section>

        <section style="border: 1px solid #dbe4ee; border-radius: 26px; background: #ffffff; box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07); overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    @foreach ([
                        'all' => ['label' => 'All Mail', 'count' => $folderCounts['all']],
                        'inbox' => ['label' => 'Inbox', 'count' => $folderCounts['inbox']],
                        'spam' => ['label' => 'Spam', 'count' => $folderCounts['spam']],
                    ] as $folder => $meta)
                        <button
                            type="button"
                            wire:click="selectFolder('{{ $folder }}')"
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px; border: 1px solid {{ $this->folderFilter === $folder ? '#99f6e4' : '#dbe4ee' }}; background: {{ $this->folderFilter === $folder ? '#ecfeff' : '#ffffff' }}; color: {{ $this->folderFilter === $folder ? '#0f766e' : '#0f172a' }}; font-size: 13px; font-weight: 700;"
                        >
                            <span>{{ $meta['label'] }}</span>
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 6px; border-radius: 999px; background: {{ $this->folderFilter === $folder ? '#ccfbf1' : '#f8fafc' }}; color: inherit; font-size: 12px;">{{ $meta['count'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <select wire:model.live="readFilter" style="padding: 11px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px; min-width: 140px;">
                        <option value="all">All status</option>
                        <option value="unread">Unread only</option>
                        <option value="read">Read only</option>
                    </select>
                    <div style="position: relative; min-width: 260px;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                            <x-heroicon-o-magnifying-glass style="width: 18px; height: 18px;" />
                        </span>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search subject, sender, or preview"
                            style="width: 100%; padding: 12px 14px 12px 42px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;"
                        >
                    </div>
                </div>
            </div>

            <div class="verification-inbox-shell" style="display: grid; grid-template-columns: 420px minmax(0, 1fr); min-height: 680px;">
                <aside style="border-right: 1px solid #edf2f7; background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);">
                    <div style="padding: 14px 16px; border-bottom: 1px solid #edf2f7; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">
                        Synced Messages
                    </div>
                    <div style="display: flex; flex-direction: column; max-height: 620px; overflow: auto;">
                        @forelse ($messages as $message)
                            <button
                                type="button"
                                wire:click="openMessage({{ $message->getKey() }})"
                                style="display: block; width: 100%; text-align: left; padding: 16px 18px; border: 0; border-bottom: 1px solid #edf2f7; background: {{ $selectedMessage && $selectedMessage->is($message) ? '#f8fbff' : '#ffffff' }};"
                            >
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                                    <div style="display: flex; flex-direction: column; gap: 6px; min-width: 0;">
                                        <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                            @if (! $message->is_read)
                                                <span style="width: 10px; height: 10px; border-radius: 999px; background: #2563eb; flex-shrink: 0;"></span>
                                            @endif
                                            <div style="font-size: 14px; font-weight: 800; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                {{ $message->from_name ?: ($message->from_email ?: 'Unknown sender') }}
                                            </div>
                                        </div>
                                        <div style="font-size: 14px; font-weight: 700; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $message->subject }}
                                        </div>
                                        <div style="font-size: 12px; line-height: 1.6; color: #64748b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $message->previewSnippet() }}
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0;">
                                        <div style="font-size: 11px; font-weight: 700; color: #64748b;">{{ $message->shortReceivedLabel() }}</div>
                                        <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                            @if ($message->folder_type === 'spam')
                                                <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #fff7ed; color: #c2410c; font-size: 10px; font-weight: 800;">Spam</span>
                                            @endif
                                            @if ($message->has_attachments)
                                                <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 10px; font-weight: 800;">{{ $message->attachment_count }} file{{ $message->attachment_count > 1 ? 's' : '' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div style="padding: 22px 20px; color: #64748b; font-size: 14px; line-height: 1.7;">
                                No mailbox messages match the current filters.
                            </div>
                        @endforelse
                    </div>
                </aside>

                <main style="background: #ffffff;">
                    @if ($selectedMessage)
                        <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; flex-direction: column; gap: 16px;">
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                                <div style="display: flex; flex-direction: column; gap: 10px; min-width: 0;">
                                    <h3 style="margin: 0; font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ $selectedMessage->subject }}</h3>
                                    <div style="font-size: 14px; color: #334155; line-height: 1.7;">
                                        <strong>From:</strong> {{ $selectedMessage->senderDisplayName() }}<br>
                                        @if (! empty($selectedMessage->to_emails))
                                            <strong>To:</strong> {{ implode(', ', $selectedMessage->to_emails) }}<br>
                                        @endif
                                        <strong>Received:</strong> {{ $selectedMessage->receivedLabel() }}
                                    </div>
                                </div>
                            </div>

                            @if ($selectedMessage->has_attachments && $selectedMessage->attachments->isNotEmpty())
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Attachments</div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                        @foreach ($selectedMessage->attachments as $attachment)
                                            <a
                                                href="{{ $this->attachmentDownloadUrl($attachment) }}"
                                                style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; text-decoration: none;"
                                            >
                                                <x-heroicon-o-paper-clip style="width: 16px; height: 16px;" />
                                                <span>{{ $attachment->file_name }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div style="padding: 22px 24px; display: flex; flex-direction: column; gap: 18px;">
                            <section style="min-width: 0;">
                                <div style="padding: 18px; border-radius: 20px; border: 1px solid #dbe4ee; background: #ffffff; min-height: 620px;">
                                    <iframe
                                        title="Inbox email preview"
                                        sandbox="allow-same-origin"
                                        src="{{ $this->messagePreviewUrl($selectedMessage) }}"
                                        style="width: 100%; min-height: 560px; border: 0; border-radius: 12px; background: #ffffff;"
                                    ></iframe>
                                </div>
                            </section>

                        </div>
                    @else
                        <div style="padding: 34px 28px; color: #64748b; font-size: 14px; line-height: 1.8;">
                            Connect the mailbox and refresh Inbox to start viewing synchronized emails here.
                        </div>
                    @endif
                </main>
            </div>
        </section>
    </div>

    <style>
        @media (max-width: 980px) {
            .verification-inbox-header {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media (max-width: 1180px) {
            .verification-inbox-shell {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
    </style>
</x-filament-panels::page>
