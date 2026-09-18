<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="assignment"
        menu-title="Settings"
        menu-eyebrow="Verification"
        menu-description="Clinic, mailbox, output, and administrative configuration."
    >
        <div style="display: flex; flex-direction: column; gap: 22px;">
            <section>
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #0f766e;">Platform-wide settings</div>
                        <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;">All verification clinics</h3>
                        <p style="margin: 10px 0 0; max-width: 760px; font-size: 14px; line-height: 1.7; color: #64748b;">
                            These rules apply only to Auto assignment. Manual assignments and Unassigned requests are unchanged.
                        </p>
                    </div>
                </div>

                <div style="padding: 22px;">
                    <form wire:submit="save">
                        {{ $this->form }}
                    </form>
                </div>
            </section>
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
