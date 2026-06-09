<x-filament-panels::page>
    @php($summary = $this->getStorageSummary())

    <x-verification-management-shell
        :items="$this->getVerificationNavItems()"
        active="inbox"
        menu-title="Verification"
        menu-eyebrow="Admin Settings"
        menu-description="Configure verification output, portal tools, inbox sync, and notification behavior from one workspace."
    >
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 22px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: inline-flex; align-items: center; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; width: fit-content;">
                            Shared Mailbox
                        </div>
                        <div>
                            <h2 style="margin: 0; font-size: 30px; font-weight: 800; color: #0f172a;">Inbox Configuration</h2>
                            <p style="margin: 10px 0 0; max-width: 980px; font-size: 15px; line-height: 1.7; color: #64748b;">
                                Connect the shared mailbox used for portal registration emails, MFA codes, and payer notices. Set the sync cadence, cleanup policy, and "do not delete" behavior here.
                            </p>
                        </div>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                        <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Stored Messages</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $summary['messages'] }}</div>
                        </div>
                        <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Attachments</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $summary['attachments'] }}</div>
                        </div>
                        <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Last Sync</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $summary['last_sync'] }}</div>
                        </div>
                        <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Last Cleanup</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $summary['last_cleanup'] }}</div>
                        </div>
                    </div>
                </div>
                <div style="padding: 22px 24px;">
                    {{ $this->form }}
                </div>
            </section>
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
