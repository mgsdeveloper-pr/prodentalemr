<x-filament-panels::page>
    @php($status = $this->getConnectionStatus())

    <div style="display: flex; flex-direction: column; gap: 22px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div>
                    <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Universal Mailbox</div>
                    <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Mailbox Settings</h3>
                    <p style="margin: 10px 0 0; max-width: 760px; font-size: 14px; line-height: 1.7; color: #64748b;">
                        Configure your user-bound mailbox for live receive and send access. The default Meditya server is prefilled, but you can replace it with another provider any time.
                    </p>
                </div>
                <div style="display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 16px; border: 1px solid {{ $status['tone'] === 'success' ? '#86efac' : ($status['tone'] === 'warning' ? '#fde68a' : '#fecaca') }}; background: {{ $status['tone'] === 'success' ? '#f0fdf4' : ($status['tone'] === 'warning' ? '#fffbeb' : '#fef2f2') }}; color: {{ $status['tone'] === 'success' ? '#166534' : ($status['tone'] === 'warning' ? '#92400e' : '#b91c1c') }};">
                    <span style="width: 10px; height: 10px; border-radius: 999px; background: currentColor;"></span>
                    <span style="font-size: 13px; font-weight: 800;">{{ $status['label'] }}</span>
                </div>
            </div>

            <div style="padding: 22px;">
                <form wire:submit="save">
                    {{ $this->form }}
                </form>
            </div>
        </section>
    </div>
</x-filament-panels::page>
