<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="assignment"
        menu-title="Verification"
        menu-eyebrow="Admin Settings"
        menu-description="Configure verification output, assignment logic, question content, and workspace controls from one place."
    >
        <div style="display: flex; flex-direction: column; gap: 22px;">
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 18px 22px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Verification Workflow</div>
                        <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Assignment Management</h3>
                        <p style="margin: 10px 0 0; max-width: 760px; font-size: 14px; line-height: 1.7; color: #64748b;">
                            Control how managed-service verification work is routed when no assignee is selected manually. Keep assignment rules separate from PDF template settings for a cleaner workflow.
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
