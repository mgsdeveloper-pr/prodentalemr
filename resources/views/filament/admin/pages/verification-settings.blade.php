<x-filament-panels::page>
    <x-verification-management-shell
        :items="\App\Support\VerificationSettingsNavigation::items()"
        active="pdf"
        menu-title="Settings"
        menu-eyebrow="Verification"
        menu-description="Clinic, mailbox, output, and administrative configuration."
    >
        @if ($this->hasClinicScope())
            @php
                $previewRecord = $this->getPreviewRecord();
                $hasChanges = ($this->data['verification_pdf_output_mode'] ?? 'standard') !== $this->getSavedMode();
            @endphp
            <div style="background: white; border: 1px solid #dbe4ee; border-radius: 8px; padding: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb;">
                    <h2 style="font-size: 18px; font-weight: 600; margin: 0;">PDF Output</h2>
                    <span style="font-size: 14px; color: #526173;">Saved default: <strong>{{ $this->getCurrentOutputLabel() }}</strong></span>
                </div>
                <form wire:submit="save" style="padding-top: 24px; max-width: 520px;">
                    {{ $this->form }}
                    <div aria-live="polite" style="min-height: 24px; margin-top: 8px; font-size: 14px; color: #9a6700;">
                        @if ($hasChanges) Unsaved selection @endif
                    </div>
                    <div style="margin-top: 20px; display: flex; align-items: center; flex-wrap: wrap; gap: 12px;">
                        @if ($previewRecord)
                            <x-filament::button tag="a" :href="$this->getPreviewUrl($previewRecord)" target="_blank" rel="noopener" color="gray" icon="heroicon-o-eye">
                                Preview PDF
                            </x-filament::button>
                            <span style="font-size: 14px; color: #526173;">{{ $previewRecord->reference_number }}</span>
                        @else
                            <x-filament::button disabled color="gray" icon="heroicon-o-eye">Preview PDF</x-filament::button>
                            <span style="font-size: 14px; color: #526173;">No completed verification available for preview.</span>
                        @endif
                    </div>
                </form>
            </div>
        @else
            <div style="padding: 24px;">
                <h2 style="font-size: 18px; font-weight: 600;">Select a clinic to view PDF settings</h2>
            </div>
        @endif
    </x-verification-management-shell>
</x-filament-panels::page>
