<x-filament-panels::page>
    @php($summary = $this->getStorageSummary())

    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="mailbox-clinic"
        menu-title="Settings"
        menu-eyebrow="Verification"
        menu-description="Clinic, mailbox, output, and administrative configuration."
    >
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                @foreach ([
                    'Current Clinic' => $this->getSelectedClinicLabel(),
                    'Stored Messages' => $summary['messages'],
                    'Attachments' => $summary['attachments'],
                    'Last Sync' => $summary['last_sync'],
                    'Last Cleanup' => $summary['last_cleanup'],
                ] as $label => $value)
                    <div style="padding: 14px 16px; border-radius: 8px; border: 1px solid #dbe4ee; background: #ffffff;">
                        <div style="margin-bottom: 5px; color: #667085; font-size: 11px; font-weight: 700;">{{ $label }}</div>
                        <div style="color: #101828; font-size: 13px; font-weight: 800;">{{ $value }}</div>
                    </div>
                @endforeach
            </section>
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 22px 24px;">
                    {{ $this->form }}
                </div>
            </section>
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
