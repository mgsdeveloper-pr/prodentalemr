<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="mailbox-personal"
        menu-title="Settings"
        menu-eyebrow="Verification"
        menu-description="Clinic, mailbox, output, and administrative configuration."
    >
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <section style="border: 1px solid var(--pd-border); border-radius: 8px; background: var(--pd-card); overflow: hidden;">
                <div style="padding: 18px 20px; border-bottom: 1px solid var(--pwdl-border-subtle);">
                    <h2 style="margin: 0; color: var(--pd-text); font-size: 20px; font-weight: 800;">My Mailbox</h2>
                    <p style="margin: 6px 0 0; color: var(--pd-muted); font-size: 14px; line-height: 1.55;">My account: personal mailbox for verification email.</p>
                    <p class="mt-2 text-sm">{{ $this->getConnectionStatus()['description'] }}</p>
                </div>
                <div style="padding: 20px;">
                    <form wire:submit="save">
                        {{ $this->form }}
                    </form>
                </div>
            </section>
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
