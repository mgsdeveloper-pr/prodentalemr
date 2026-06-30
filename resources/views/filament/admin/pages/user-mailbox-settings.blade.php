<x-filament-panels::page>
    @php($status = $this->getConnectionStatus())

    <div style="display: flex; flex-direction: column; gap: 22px;">
        @include('filament.shared.partials.page-hero', [
            'eyebrow' => 'Universal Mailbox',
            'title' => 'Mailbox Settings',
            'description' => 'Configure your user-bound mailbox for live receive and send access. The default Meditya server is prefilled, but you can replace it with another provider any time.',
            'rightContent' => '<div style="display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 16px; border: 1px solid ' . ($status['tone'] === 'success' ? '#86efac' : ($status['tone'] === 'warning' ? '#fde68a' : '#fecaca')) . '; background: ' . ($status['tone'] === 'success' ? '#f0fdf4' : ($status['tone'] === 'warning' ? '#fffbeb' : '#fef2f2')) . '; color: ' . ($status['tone'] === 'success' ? '#166534' : ($status['tone'] === 'warning' ? '#92400e' : '#b91c1c')) . ';"><span style="width: 10px; height: 10px; border-radius: 999px; background: currentColor;"></span><span style="font-size: 13px; font-weight: 800;">' . e($status['label']) . '</span></div>',
        ])

        <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
            <div style="padding: 22px;">
                <form wire:submit="save">
                    {{ $this->form }}
                </form>
            </div>
        </section>
    </div>
</x-filament-panels::page>
