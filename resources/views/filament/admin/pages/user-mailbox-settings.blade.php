<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="mailbox-personal"
        menu-title="Settings"
        menu-eyebrow="Verification"
        menu-description="Clinic, mailbox, output, and administrative configuration."
    >
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <section style="border: 1px solid #dbe4ee; border-radius: 8px; background: #ffffff; overflow: hidden;">
                <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                    <h2 style="margin: 0; color: #101828; font-size: 20px; font-weight: 800;">My Mailbox</h2>
                    <p style="margin: 6px 0 0; color: #667085; font-size: 13px; line-height: 1.55;">Connect your personal mailbox for verification email.</p>
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
