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
            @if ($this->hasClinicScope())
            <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                @foreach ([
                    'Current Clinic' => $this->getSelectedClinicLabel(),
                    'Stored Messages' => $summary['messages'],
                    'Attachments' => $summary['attachments'],
                    'Last Sync' => $summary['last_sync'],
                    'Last Cleanup' => $summary['last_cleanup'],
                ] as $label => $value)
                    <div style="padding: 14px 16px; border-radius: 8px; border: 1px solid #dbe4ee; background: #ffffff;">
                        <div style="margin-bottom: 5px; color: #667085; font-size: 14px; font-weight: 600;">{{ $label }}</div>
                        <div style="color: #101828; font-size: 13px; font-weight: 800;">{{ $value }}</div>
                    </div>
                @endforeach
            </section>
            <section>
                <div>
                    {{ $this->form }}
                </div>
            </section>
            @else
                <h2 class="text-base font-semibold">Select a clinic to view inbox settings</h2>
                <p class="text-sm text-gray-600">Choose a clinic from the workspace selector. Reload this page if you changed clinics in another tab.</p>
                @if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'clinic')
                    <x-filament::button tag="a" :href="\App\Filament\Clinic\Pages\VerificationSettings::getUrl(panel: 'clinic')" color="gray">
                        Select clinic in Verification Settings
                    </x-filament::button>
                @endif
            @endif
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
