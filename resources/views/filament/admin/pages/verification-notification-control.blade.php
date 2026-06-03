<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="notifications"
        menu-title="Verification"
        menu-eyebrow="Admin Settings"
        menu-description="Configure verification output, question content, notification behavior, and section ordering from one workspace."
    >
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                        Verification Notifications
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 30px; font-weight: 800; color: #0f172a;">Notification Control</h2>
                        <p style="margin: 10px 0 0; max-width: 980px; font-size: 15px; line-height: 1.7; color: #64748b;">
                            Configure which verification events generate notifications, who receives them, and how urgent managed-service requests are escalated. Admin sees all, while clinics stay limited to self-service unless explicitly enabled.
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
