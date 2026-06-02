<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="insurance"
        menu-title="Verification"
        menu-eyebrow="Clinic Settings"
        menu-description="Configure verification output, local insurance overrides, question content, and section ordering from one workspace."
    >
        <div style="display: flex; flex-direction: column; gap: 22px;">
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
                    <div>
                        <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #ecfeff; border: 1px solid #99f6e4; color: #0f766e; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase;">
                            Verification Workspace
                        </div>
                        <h2 style="margin: 14px 0 0; font-size: 30px; line-height: 1.08; font-weight: 800; color: #0f172a;">
                            Insurance Directory
                        </h2>
                        <p style="margin: 10px 0 0; max-width: 920px; font-size: 15px; line-height: 1.7; color: #64748b;">
                            Browse the shared insurance carrier master and keep clinic-specific changes isolated to this clinic only.
                        </p>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                        <div style="min-width: 220px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Clinic Scope</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $this->getSelectedClinicName() ?: 'Select clinic scope' }}</div>
                        </div>
                    </div>
                </div>

                <div style="padding: 22px;">
                    {{ $this->table }}
                </div>
            </section>
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
