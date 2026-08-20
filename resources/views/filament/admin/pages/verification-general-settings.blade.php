<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="general"
        menu-title="Settings"
        menu-eyebrow="Verification"
        menu-description="Clinic, mailbox, output, and administrative configuration."
    >
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <section style="border: 1px solid #dbe4ee; border-radius: 8px; background: #ffffff; overflow: hidden;">
                <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                    <h2 style="margin: 0; color: #101828; font-size: 20px; font-weight: 800;">General</h2>
                    <p style="margin: 6px 0 0; color: #667085; font-size: 13px; line-height: 1.55;">
                        Review the selected clinic and its default verification behavior.
                    </p>
                </div>
                <div style="padding: 20px;">
                    <form wire:submit="save">
                        {{ $this->form }}
                    </form>
                </div>
            </section>

            @if (! $this->canManageClinicSettings())
                <div style="padding: 12px 14px; border: 1px solid #dbe4ee; border-radius: 8px; background: #f8fafc; color: #667085; font-size: 12px;">
                    Clinic settings are shown according to your role and permissions.
                </div>
            @endif
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
