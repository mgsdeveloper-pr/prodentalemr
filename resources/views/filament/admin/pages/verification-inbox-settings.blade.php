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
            @include('filament.shared.partials.page-hero', [
                'eyebrow' => 'Clinic Mailbox',
                'title' => 'Inbox Configuration',
                'description' => 'Connect the mailbox used for portal registration emails, MFA codes, and payer notices for the selected clinic. Set the sync cadence, cleanup policy, and \"do not delete\" behavior here.',
                'scopeLabel' => 'Current clinic',
                'scopeValue' => $this->getSelectedClinicLabel(),
                'rightContent' => '
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: flex-end;">
                        <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Stored Messages</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">' . e($summary['messages']) . '</div>
                        </div>
                        <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Attachments</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">' . e($summary['attachments']) . '</div>
                        </div>
                        <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Last Sync</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">' . e($summary['last_sync']) . '</div>
                        </div>
                        <div style="min-width: 180px; padding: 14px 16px; border-radius: 18px; border: 1px solid #dbe4ee; background: #f8fafc;">
                            <div style="margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Last Cleanup</div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">' . e($summary['last_cleanup']) . '</div>
                        </div>
                    </div>',
            ])
            <section style="border: 1px solid #dbe4ee; border-radius: 24px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); overflow: hidden;">
                <div style="padding: 22px 24px;">
                    {{ $this->form }}
                </div>
            </section>
        </div>
    </x-verification-management-shell>
</x-filament-panels::page>
