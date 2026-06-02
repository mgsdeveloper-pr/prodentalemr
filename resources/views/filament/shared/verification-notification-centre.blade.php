<x-filament-panels::page>
    @php($summary = $this->getSummary())
    @php($notifications = $this->getNotifications())
    @php($typeOptions = $this->notificationTypeOptions())

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                        Verification Notifications
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 30px; font-weight: 800; color: #0f172a;">Notification Centre</h2>
                        <p style="margin: 10px 0 0; max-width: 900px; font-size: 15px; line-height: 1.7; color: #64748b;">
                            Track verification updates, assignment changes, clinic submissions, and outcome movements in one place.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    style="display: inline-flex; align-items: center; justify-content: center; padding: 12px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; white-space: nowrap;"
                >
                    Mark all read
                </button>
            </div>

            <div style="padding: 20px 24px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px;" class="verification-notification-summary-grid">
                @foreach ([
                    ['label' => 'Total', 'value' => $summary['total'], 'hint' => 'Notifications in view'],
                    ['label' => 'Unread', 'value' => $summary['unread'], 'hint' => 'Need review'],
                    ['label' => 'Today', 'value' => $summary['today'], 'hint' => 'Created today'],
                    ['label' => 'Attention', 'value' => $summary['critical'], 'hint' => 'Warning or danger'],
                ] as $card)
                    <div style="padding: 16px 18px; border-radius: 18px; border: 1px solid #dbe4ee; background: #ffffff;">
                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">{{ $card['label'] }}</div>
                        <div style="margin-top: 8px; font-size: 34px; font-weight: 800; color: #0f172a;">{{ $card['value'] }}</div>
                        <div style="margin-top: 6px; font-size: 13px; color: #64748b;">{{ $card['hint'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 18px 24px; border-bottom: 1px solid #edf2f7;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a;">Filters</h3>
            </div>
            <div style="padding: 18px 24px; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px;" class="verification-notification-filter-grid">
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155;">Search</label>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search notifications" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; font-size: 14px;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155;">Read Status</label>
                    <select wire:model.live="readFilter" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; font-size: 14px;">
                        <option value="all">All</option>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155;">Activity</label>
                    <select wire:model.live="typeFilter" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; font-size: 14px;">
                        <option value="">All activities</option>
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155;">From</label>
                    <input type="date" wire:model.live="fromDate" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; font-size: 14px;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155;">To</label>
                    <input type="date" wire:model.live="toDate" style="width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; font-size: 14px;">
                </div>
            </div>
        </section>

        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 18px 24px; border-bottom: 1px solid #edf2f7;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a;">Recent Verification Notifications</h3>
            </div>
            <div style="padding: 18px 24px; display: flex; flex-direction: column; gap: 14px;">
                @forelse ($notifications as $notification)
                    <article style="padding: 16px 18px; border-radius: 18px; border: 1px solid {{ $notification->read_at ? '#dbe4ee' : '#bfdbfe' }}; background: {{ $notification->read_at ? '#ffffff' : '#f8fbff' }}; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: flex-start; gap: 14px; flex: 1 1 540px;">
                            <div style="margin-top: 2px; width: 12px; height: 12px; border-radius: 999px; background: {{
                                match($notification->level) {
                                    'success' => '#16a34a',
                                    'warning' => '#f59e0b',
                                    'danger' => '#dc2626',
                                    default => '#2563eb',
                                }
                            }};"></div>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <h4 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">{{ $notification->title }}</h4>
                                    <span style="display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #64748b; font-size: 11px; font-weight: 700;">
                                        {{ str($notification->activity_type)->replace('_', ' ')->title() }}
                                    </span>
                                    @if (! $notification->read_at)
                                        <span style="display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 700;">
                                            Unread
                                        </span>
                                    @endif
                                </div>
                                <p style="margin: 0; font-size: 14px; line-height: 1.7; color: #475569;">{{ $notification->message }}</p>
                                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 12px; color: #64748b;">
                                    <span>{{ optional($notification->created_at)->format('d M Y, h:i A') }}</span>
                                    @if (filled($notification->meta['clinic_name'] ?? null))
                                        <span>Clinic: {{ $notification->meta['clinic_name'] }}</span>
                                    @endif
                                    @if (filled($notification->meta['reference_number'] ?? null))
                                        <span>Ref: {{ $notification->meta['reference_number'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            @if (filled($notification->target_url))
                                <a href="{{ $notification->target_url }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; text-decoration: none;">
                                    Open
                                </a>
                            @endif
                            @if (! $notification->read_at)
                                <button type="button" wire:click="markAsRead({{ $notification->getKey() }})" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700;">
                                    Mark read
                                </button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div style="border: 1px dashed #cbd5e1; border-radius: 20px; padding: 24px; background: #f8fafc; font-size: 14px; line-height: 1.7; color: #64748b;">
                        No verification notifications match the current filters.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <style>
        @media (max-width: 1200px) {
            .verification-notification-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .verification-notification-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 800px) {
            .verification-notification-summary-grid,
            .verification-notification-filter-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
    </style>
</x-filament-panels::page>
