<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="notifications"
        menu-title="Settings"
        menu-eyebrow="Verification"
        menu-description="Clinic, mailbox, output, and administrative configuration."
    >
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <section>
                <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                        Platform-wide settings
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;">All verification clinics</h2>
                        <p style="margin: 10px 0 0; max-width: 980px; font-size: 15px; line-height: 1.7; color: #64748b;">
                            Configure which verification events generate notifications, who receives them, and which secure email alerts are enabled. Verification users receive only the clinics they are permitted to access.
                        </p>
                    </div>
                </div>
                <div style="padding: 22px 24px;">
                    {{ $this->form }}
                </div>
            </section>
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
