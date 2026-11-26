<x-filament-panels::page>
    <form wire:submit="save" style="display: flex; flex-direction: column; gap: 24px;">
        {{ $this->form }}
    </form>

    @php
        $deliverySummary = $this->getDeliverySummary();
        $recentDeliveries = $this->getRecentDeliveries();
    @endphp

    <section style="margin-top: 24px; border: 1px solid var(--pd-border); border-radius: 8px; background: var(--pd-card); overflow: hidden;">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; padding: 20px 22px; border-bottom: 1px solid var(--pwdl-border-subtle);">
            <div>
                <h2 style="margin: 0; color: var(--pd-text); font-size: 18px; font-weight: 750;">Delivery activity</h2>
                <p style="margin: 5px 0 0; color: var(--pd-muted); font-size: 13px; line-height: 1.5;">Recent notification attempts across in-app and email channels.</p>
            </div>
            <div style="display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px;">
                <span style="padding: 5px 9px; border: 1px solid var(--pwdl-border-subtle); border-radius: 999px; background: var(--pwdl-status-success-soft); color: var(--pwdl-status-success-text); font-size: 12px; font-weight: 700;">{{ $deliverySummary['delivered'] }} delivered</span>
                <span style="padding: 5px 9px; border: 1px solid var(--pwdl-border-subtle); border-radius: 999px; background: var(--pwdl-status-info-soft); color: var(--pwdl-status-info-text); font-size: 12px; font-weight: 700;">{{ $deliverySummary['pending'] }} pending</span>
                <span style="padding: 5px 9px; border: 1px solid var(--pwdl-border-subtle); border-radius: 999px; background: var(--pwdl-status-danger-soft); color: var(--pwdl-status-danger-text); font-size: 12px; font-weight: 700;">{{ $deliverySummary['failed'] }} failed</span>
                <span style="padding: 5px 9px; border: 1px solid var(--pwdl-border-subtle); border-radius: 999px; background: var(--pwdl-surface-muted); color: var(--pwdl-text-secondary); font-size: 12px; font-weight: 700;">{{ $deliverySummary['skipped'] }} skipped</span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; min-width: 860px; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--pwdl-surface-muted); color: var(--pwdl-text-secondary); text-align: left; font-size: 12px;">
                        <th style="padding: 11px 16px; font-weight: 700;">Notification</th>
                        <th style="padding: 11px 16px; font-weight: 700;">Recipient</th>
                        <th style="padding: 11px 16px; font-weight: 700;">Panel</th>
                        <th style="padding: 11px 16px; font-weight: 700;">Channel</th>
                        <th style="padding: 11px 16px; font-weight: 700;">Status</th>
                        <th style="padding: 11px 16px; font-weight: 700;">Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentDeliveries as $delivery)
                        @php
                            $statusStyle = match ($delivery->status) {
                                'delivered' => 'border-color: var(--pwdl-border-subtle); background: var(--pwdl-status-success-soft); color: var(--pwdl-status-success-text);',
                                'failed' => 'border-color: var(--pwdl-border-subtle); background: var(--pwdl-status-danger-soft); color: var(--pwdl-status-danger-text);',
                                'pending', 'processing' => 'border-color: var(--pwdl-border-subtle); background: var(--pwdl-status-info-soft); color: var(--pwdl-status-info-text);',
                                default => 'border-color: var(--pwdl-border-subtle); background: var(--pwdl-surface-muted); color: var(--pwdl-text-secondary);',
                            };
                        @endphp
                        <tr style="border-top: 1px solid var(--pwdl-border-subtle); color: var(--pwdl-text-secondary); font-size: 13px;">
                            <td style="padding: 13px 16px;">
                                <strong style="display: block; color: var(--pd-text); font-weight: 700;">{{ $delivery->event?->title ?? 'Notification event' }}</strong>
                                <span style="display: block; margin-top: 3px; color: var(--pd-muted); font-size: 11px;">{{ $delivery->event?->event_type ?? 'system' }}</span>
                            </td>
                            <td style="padding: 13px 16px;">
                                <strong style="display: block; color: var(--pd-text); font-weight: 650;">{{ $delivery->recipient?->name ?? 'System recipient' }}</strong>
                                <span style="display: block; margin-top: 3px; color: var(--pd-muted); font-size: 11px;">{{ $delivery->channel === 'email' ? ($delivery->destination ?? $delivery->recipient?->email) : 'Secure in-app inbox' }}</span>
                            </td>
                            <td style="padding: 13px 16px; text-transform: capitalize;">{{ str_replace('_', ' ', $delivery->panel) }}</td>
                            <td style="padding: 13px 16px; text-transform: capitalize;">{{ str_replace('_', ' ', $delivery->channel) }}</td>
                            <td style="padding: 13px 16px;">
                                <span style="display: inline-flex; padding: 4px 8px; border: 1px solid; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: capitalize; {{ $statusStyle }}">{{ $delivery->status }}</span>
                            </td>
                            <td style="padding: 13px 16px; white-space: nowrap; color: var(--pd-muted);">{{ $delivery->created_at?->format('M j, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 32px 16px; text-align: center; color: var(--pd-muted); font-size: 13px;">No notification deliveries have been recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>
