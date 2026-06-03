<x-filament-panels::page>
    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="participation"
        menu-title="Verification"
        menu-eyebrow="Admin Settings"
        menu-description="Configure payer network participation guidance, insurance master data, and verification workspace policies from one workspace."
    >
        <div style="display: flex; flex-direction: column; gap: 22px;">
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
                    <div>
                        <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase;">
                            Verification Workspace
                        </div>
                        <h2 style="margin: 14px 0 0; font-size: 30px; line-height: 1.08; font-weight: 800; color: #0f172a;">
                            Provider Participation Rules
                        </h2>
                        <p style="margin: 10px 0 0; max-width: 920px; font-size: 15px; line-height: 1.7; color: #64748b;">
                            Maintain payer-specific guidance for participating and non-participating providers, reimbursement basis, assignment of benefits, and out-of-network behavior. Verifiers can then see this directly inside the verification forms.
                        </p>
                    </div>
                </div>

                <div style="padding: 22px;">
                    {{ $this->table }}
                </div>
            </section>
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
