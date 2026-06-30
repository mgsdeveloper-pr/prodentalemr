<x-filament-panels::page>
    @php($status = $this->getConnectionStatus())

    <div style="display: flex; flex-direction: column; gap: 24px;">
        @include('filament.shared.partials.page-hero', [
            'eyebrow' => 'Universal Mailbox',
            'title' => 'Mailbox',
            'description' => 'Review live inbox, spam, and sent mail from your connected mailbox in one workspace.',
            'rightContent' => '<div style="display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 16px; border: 1px solid ' . ($status['tone'] === 'success' ? '#86efac' : ($status['tone'] === 'warning' ? '#fde68a' : '#fecaca')) . '; background: ' . ($status['tone'] === 'success' ? '#f0fdf4' : ($status['tone'] === 'warning' ? '#fffbeb' : '#fef2f2')) . '; color: ' . ($status['tone'] === 'success' ? '#166534' : ($status['tone'] === 'warning' ? '#92400e' : '#b91c1c')) . ';"><span style="width: 10px; height: 10px; border-radius: 999px; background: currentColor;"></span><span style="font-size: 13px; font-weight: 800;">' . e($status['label']) . '</span></div>',
        ])

        <section style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap; padding-inline: 4px;">
            @foreach ($this->getToolbarActions() as $action)
                {{ $action }}
            @endforeach
            @if ($this->isConfigured())
                <button
                    type="button"
                    wire:click="openComposeModal"
                    style="display: inline-flex; align-items: center; gap: 10px; padding: 11px 16px; border: 1px solid #fbbf24; border-radius: 14px; background: #fbbf24; color: #7c2d12; font-size: 14px; font-weight: 700; cursor: pointer;"
                >
                    <x-heroicon-o-pencil-square style="width: 18px; height: 18px;" />
                    <span>Compose</span>
                </button>
                <button
                    type="button"
                    wire:click="openReplyModal"
                    @disabled(blank($this->selectedMessage['from_email'] ?? null))
                    style="display: inline-flex; align-items: center; gap: 10px; padding: 11px 16px; border: 1px solid #dbe4ee; border-radius: 14px; background: {{ blank($this->selectedMessage['from_email'] ?? null) ? '#f8fafc' : '#ffffff' }}; color: {{ blank($this->selectedMessage['from_email'] ?? null) ? '#94a3b8' : '#0f172a' }}; font-size: 14px; font-weight: 700; cursor: {{ blank($this->selectedMessage['from_email'] ?? null) ? 'not-allowed' : 'pointer' }};"
                >
                    <x-heroicon-o-arrow-uturn-left style="width: 18px; height: 18px;" />
                    <span>Reply</span>
                </button>
                <button
                    type="button"
                    wire:click="openReplyAllModal"
                    @disabled(blank($this->selectedMessage['from_email'] ?? null))
                    style="display: inline-flex; align-items: center; gap: 10px; padding: 11px 16px; border: 1px solid #dbe4ee; border-radius: 14px; background: {{ blank($this->selectedMessage['from_email'] ?? null) ? '#f8fafc' : '#ffffff' }}; color: {{ blank($this->selectedMessage['from_email'] ?? null) ? '#94a3b8' : '#0f172a' }}; font-size: 14px; font-weight: 700; cursor: {{ blank($this->selectedMessage['from_email'] ?? null) ? 'not-allowed' : 'pointer' }};"
                >
                    <x-heroicon-o-arrow-uturn-left style="width: 18px; height: 18px;" />
                    <span>Reply All</span>
                </button>
            @endif
        </section>

        @if (! $this->isConfigured())
            <section style="border: 1px solid #dbe4ee; border-radius: 26px; background: #ffffff; box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07); overflow: hidden;">
                <div style="padding: 34px 32px; display: flex; flex-direction: column; gap: 18px; max-width: 760px;">
                    <h3 style="margin: 0; font-size: 28px; font-weight: 800; color: #0f172a;">Connect your mailbox</h3>
                    <p style="margin: 0; font-size: 15px; line-height: 1.8; color: #64748b;">
                        Open <strong>Mailbox Settings</strong> from the sidebar to enter your mailbox user ID and password. By default, we prefill:
                        <strong>Host:</strong> mail.medityaglobalservices.com
                        and
                        <strong>IMAP Port:</strong> 993.
                        You can also replace those values if your email provider changes later.
                    </p>
                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;">
                        <div style="padding: 18px; border-radius: 18px; border: 1px solid #dbe4ee; background: #fbfdff;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Default Host</div>
                            <div style="margin-top: 8px; font-size: 18px; font-weight: 800; color: #0f172a;">mail.medityaglobalservices.com</div>
                        </div>
                        <div style="padding: 18px; border-radius: 18px; border: 1px solid #dbe4ee; background: #fbfdff;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">IMAP Port</div>
                            <div style="margin-top: 8px; font-size: 18px; font-weight: 800; color: #0f172a;">993</div>
                        </div>
                        <div style="padding: 18px; border-radius: 18px; border: 1px solid #dbe4ee; background: #fbfdff;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Mode</div>
                            <div style="margin-top: 8px; font-size: 18px; font-weight: 800; color: #0f172a;">Live IMAP</div>
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section style="border: 1px solid #dbe4ee; border-radius: 26px; background: #ffffff; box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07); overflow: hidden;">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        @foreach ([
                            'inbox' => ['label' => 'Inbox', 'count' => $this->folderCounts['inbox'] ?? 0],
                            'spam' => ['label' => 'Spam', 'count' => $this->folderCounts['spam'] ?? 0],
                            'sent' => ['label' => 'Sent', 'count' => $this->folderCounts['sent'] ?? 0],
                            'all' => ['label' => 'All Mail', 'count' => $this->folderCounts['all'] ?? 0],
                        ] as $folder => $meta)
                            <button
                                type="button"
                                wire:click="$set('folderFilter', '{{ $folder }}')"
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

                <div class="user-mailbox-shell" style="display: grid; grid-template-columns: 420px minmax(0, 1fr); min-height: 680px;">
                    <aside style="border-right: 1px solid #edf2f7; background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);">
                        <div style="padding: 14px 16px; border-bottom: 1px solid #edf2f7; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">
                            Live Messages
                        </div>
                        <div style="display: flex; flex-direction: column; max-height: 620px; overflow: auto;">
                            @forelse ($this->messages as $message)
                                <button
                                    type="button"
                                    wire:click="openMessage('{{ $message['folder_key'] }}', '{{ $message['uid'] }}')"
                                    style="display: block; width: 100%; text-align: left; padding: 16px 18px; border: 0; border-bottom: 1px solid #edf2f7; background: {{ $this->selectedMessageUid === $message['uid'] && $this->selectedMessageFolder === $message['folder_key'] ? '#f8fbff' : '#ffffff' }};"
                                >
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                                        <div style="display: flex; flex-direction: column; gap: 6px; min-width: 0;">
                                            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                                @if (! $message['is_read'])
                                                    <span style="width: 10px; height: 10px; border-radius: 999px; background: #2563eb; flex-shrink: 0;"></span>
                                                @endif
                                                <div style="font-size: 14px; font-weight: 800; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    @if ($message['folder_key'] === 'sent')
                                                        You
                                                    @else
                                                        {{ $message['from_name'] ?: ($message['from_email'] ?: 'Unknown sender') }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div style="font-size: 14px; font-weight: 700; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                {{ $message['subject'] }}
                                            </div>
                                            <div style="font-size: 12px; line-height: 1.6; color: #64748b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                {{ $message['snippet'] }}
                                            </div>
                                        </div>
                                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0;">
                                            <div style="font-size: 11px; font-weight: 700; color: #64748b;">{{ $message['short_received_label'] }}</div>
                                            <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                                @if ($message['folder_key'] === 'spam')
                                                    <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #fff7ed; color: #c2410c; font-size: 10px; font-weight: 800;">Spam</span>
                                                @elseif ($message['folder_key'] === 'sent')
                                                    <span style="display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 10px; font-weight: 800;">Sent</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            @empty
                                <div style="padding: 22px 20px; color: #64748b; font-size: 14px; line-height: 1.7;">
                                    No live mailbox messages match the current filters.
                                </div>
                            @endforelse
                        </div>
                    </aside>

                    <main style="background: #ffffff;">
                        @if ($this->selectedMessage)
                            <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; flex-direction: column; gap: 18px;">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                                    <div style="display: flex; flex-direction: column; gap: 12px; min-width: 0;">
                                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1.25;">{{ $this->selectedMessage['subject'] }}</h3>
                                        <div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <span style="display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; background: {{ $this->selectedMessage['folder_key'] === 'spam' ? '#fff7ed' : ($this->selectedMessage['folder_key'] === 'sent' ? '#eff6ff' : '#f8fafc') }}; color: {{ $this->selectedMessage['folder_key'] === 'spam' ? '#c2410c' : ($this->selectedMessage['folder_key'] === 'sent' ? '#1d4ed8' : '#475569') }}; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">
                                                {{ $this->selectedMessage['folder_key'] }}
                                            </span>
                                            @if (! $this->selectedMessage['is_read'])
                                                <span style="display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">
                                                    Unread
                                                </span>
                                            @endif
                                        </div>
                                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 18px; font-size: 13px; color: #334155; line-height: 1.6;">
                                            <div><strong>From:</strong> {{ $this->selectedMessage['folder_key'] === 'sent' ? 'You' : ($this->selectedMessage['from_name'] ?: ($this->selectedMessage['from_email'] ?? 'Unknown sender')) }}</div>
                                            @if (! empty($this->selectedMessage['to']))
                                                <div><strong>To:</strong> {{ implode(', ', $this->selectedMessage['to']) }}</div>
                                            @endif
                                            @if (! empty($this->selectedMessage['cc']))
                                                <div><strong>CC:</strong> {{ implode(', ', $this->selectedMessage['cc']) }}</div>
                                            @endif
                                            <div><strong>Received:</strong> {{ $this->selectedMessage['received_label'] }}</div>
                                        </div>
                                    </div>
                                </div>

                                @if (! empty($this->selectedMessage['attachments']))
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Attachments</div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                            @foreach ($this->selectedMessage['attachments'] as $attachment)
                                                <a
                                                    href="{{ $this->attachmentDownloadUrl($attachment) }}"
                                                    style="display: inline-flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; text-decoration: none;"
                                                >
                                                    <x-heroicon-o-paper-clip style="width: 16px; height: 16px;" />
                                                    <span>{{ $attachment['name'] }}</span>
                                                    <span style="color: #64748b; font-size: 11px; font-weight: 700;">{{ $attachment['size_label'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div style="padding: 12px 16px 16px;">
                                <iframe
                                    title="Mailbox email preview"
                                    sandbox="allow-same-origin"
                                    src="{{ $this->messagePreviewUrl() }}"
                                    style="width: 100%; min-height: 640px; border: 0; border-radius: 18px; background: #ffffff;"
                                ></iframe>
                            </div>
                        @else
                            <div style="padding: 34px 28px; color: #64748b; font-size: 14px; line-height: 1.8;">
                                Refresh the live mailbox to start reading messages here.
                            </div>
                        @endif
                    </main>
                </div>
            </section>
        @endif
    </div>

    <style>
        @media (max-width: 980px) {
            .user-mailbox-header {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media (max-width: 1180px) {
            .user-mailbox-shell {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        .mailbox-compose-form-disabled {
            opacity: 0.82;
            transition: opacity 0.18s ease;
        }

        @keyframes mailbox-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>

    @if ($this->composeModalOpen)
        <div style="position: fixed; inset: 0; z-index: 80; background: rgba(15, 23, 42, 0.42); display: flex; align-items: flex-end; justify-content: flex-end; padding: 24px;">
            <div style="width: min(640px, 100%); max-height: calc(100vh - 48px); border-radius: 22px; border: 1px solid #dbe4ee; background: #ffffff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22); overflow: hidden; display: flex; flex-direction: column;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #edf2f7; background: #f8fbff;">
                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">{{ $this->composeModalHeading() }}</div>
                    <button
                        type="button"
                        wire:click="closeComposeModal"
                        style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #475569; cursor: pointer;"
                    >
                        <x-heroicon-o-x-mark style="width: 18px; height: 18px;" />
                    </button>
                </div>

                <div style="position: relative; padding: 18px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto; min-height: 0;">
                    <div wire:loading.flex wire:target="submitCompose" style="display: none; position: absolute; inset: 56px 18px 18px; z-index: 2; background: rgba(255, 255, 255, 0.16); backdrop-filter: blur(1px); border-radius: 18px; pointer-events: all; align-items: center; justify-content: center;">
                        <div style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 999px; border: 1px solid #cbd5e1; background: rgba(255, 255, 255, 0.96); color: #334155; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12); font-size: 13px; font-weight: 800;">
                            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; animation: mailbox-spin 1s linear infinite;">
                                <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="42" stroke-dashoffset="12"></circle>
                            </svg>
                            <span>Sending email. Please wait...</span>
                        </div>
                    </div>

                    <div wire:loading.class="mailbox-compose-form-disabled" wire:target="submitCompose">
                        <label style="display: block; margin-bottom: 6px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">To</label>
                        <input type="text" wire:model.defer="composeFormData.to" wire:loading.attr="disabled" wire:target="submitCompose" placeholder="recipient@example.com or comma-separated list" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;">
                        @error('composeFormData.to')
                            <div style="margin-top: 6px; font-size: 12px; color: #dc2626;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div wire:loading.class="mailbox-compose-form-disabled" wire:target="submitCompose" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px;">
                        <div>
                            <label style="display: block; margin-bottom: 6px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">CC</label>
                            <input type="text" wire:model.defer="composeFormData.cc" wire:loading.attr="disabled" wire:target="submitCompose" placeholder="Optional comma-separated list" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;">
                            @error('composeFormData.cc')
                                <div style="margin-top: 6px; font-size: 12px; color: #dc2626;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 6px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">BCC</label>
                            <input type="text" wire:model.defer="composeFormData.bcc" wire:loading.attr="disabled" wire:target="submitCompose" placeholder="Optional comma-separated list" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;">
                            @error('composeFormData.bcc')
                                <div style="margin-top: 6px; font-size: 12px; color: #dc2626;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div wire:loading.class="mailbox-compose-form-disabled" wire:target="submitCompose">
                        <label style="display: block; margin-bottom: 6px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Subject</label>
                        <input type="text" wire:model.defer="composeFormData.subject" wire:loading.attr="disabled" wire:target="submitCompose" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px;">
                        @error('composeFormData.subject')
                            <div style="margin-top: 6px; font-size: 12px; color: #dc2626;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div wire:loading.class="mailbox-compose-form-disabled" wire:target="submitCompose">
                        <label style="display: block; margin-bottom: 6px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Message</label>
                        <textarea wire:model.defer="composeFormData.body" wire:loading.attr="disabled" wire:target="submitCompose" rows="10" placeholder="Write your message here." style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px; line-height: 1.7; resize: vertical;"></textarea>
                        @error('composeFormData.body')
                            <div style="margin-top: 6px; font-size: 12px; color: #dc2626;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div wire:loading.class="mailbox-compose-form-disabled" wire:target="submitCompose">
                        <label style="display: block; margin-bottom: 6px; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Attachments</label>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <input
                                type="file"
                                wire:model="composeAttachments"
                                wire:loading.attr="disabled"
                                wire:target="composeAttachments,submitCompose"
                                multiple
                                style="display: block; width: 100%; padding: 11px 12px; border-radius: 14px; border: 1px dashed #cbd5e1; background: #f8fafc; color: #0f172a; font-size: 14px;"
                            >
                            <div style="font-size: 12px; color: #64748b;">
                                Max {{ $this->mailbox()?->attachment_limit_mb ?? 25 }} MB per attachment.
                            </div>

                            @error('composeAttachments.*')
                                <div style="font-size: 12px; color: #dc2626;">{{ $message }}</div>
                            @enderror

                            <div wire:loading.flex wire:target="composeAttachments" style="display: none; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #1d4ed8;">
                                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; animation: mailbox-spin 1s linear infinite;">
                                    <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="42" stroke-dashoffset="12"></circle>
                                </svg>
                                <span>Uploading attachment...</span>
                            </div>

                            @if ($this->composeAttachments !== [])
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    @foreach ($this->composeAttachments as $index => $attachment)
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff;">
                                            <div style="display: flex; flex-direction: column; gap: 4px; min-width: 0;">
                                                <div style="font-size: 13px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    {{ method_exists($attachment, 'getClientOriginalName') ? $attachment->getClientOriginalName() : 'Attachment' }}
                                                </div>
                                                <div style="font-size: 11px; color: #64748b;">
                                                    {{ method_exists($attachment, 'getSize') ? number_format(($attachment->getSize() ?? 0) / 1024 / 1024, 2) : '0.00' }} MB
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="removeComposeAttachment({{ $index }})"
                                                wire:loading.attr="disabled"
                                                wire:target="composeAttachments,submitCompose"
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #64748b; cursor: pointer; flex-shrink: 0;"
                                            >
                                                <x-heroicon-o-x-mark style="width: 16px; height: 16px;" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($this->isReplyMode() && $this->selectedMessage)
                        <div wire:loading.class="mailbox-compose-form-disabled" wire:target="submitCompose" style="display: flex; flex-direction: column; gap: 10px;">
                            <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Original Message</div>
                            <div style="border: 1px solid #dbe4ee; border-radius: 16px; background: #f8fbff; overflow: hidden;">
                                <div style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 12px; line-height: 1.7; color: #475569;">
                                    <strong>From:</strong> {{ $this->selectedMessage['from_name'] ?: ($this->selectedMessage['from_email'] ?? 'Unknown sender') }}<br>
                                    <strong>Received:</strong> {{ $this->selectedMessage['received_label'] ?? 'Unknown time' }}
                                </div>
                                <iframe
                                    title="Original email preview"
                                    sandbox="allow-same-origin"
                                    src="{{ $this->messagePreviewUrl() }}"
                                    style="width: 100%; min-height: 260px; border: 0; background: #ffffff;"
                                ></iframe>
                            </div>
                        </div>
                    @endif
                </div>

                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 16px 18px; border-top: 1px solid #edf2f7; background: #ffffff; flex-shrink: 0;">
                    <button
                        type="button"
                        wire:click="closeComposeModal"
                        wire:loading.attr="disabled"
                        wire:target="submitCompose"
                        style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 14px; font-weight: 700; cursor: pointer;"
                    >
                        Close
                    </button>
                    <button
                        type="button"
                        wire:click="submitCompose"
                        wire:loading.attr="disabled"
                        wire:target="submitCompose"
                        style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 18px; border-radius: 14px; border: 1px solid #0f766e; background: #0f766e; color: #ffffff; font-size: 14px; font-weight: 700; cursor: pointer;"
                    >
                        <span wire:loading.remove wire:target="submitCompose">Send</span>
                        <span wire:loading.inline wire:target="submitCompose" style="display: none;">Sending...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
