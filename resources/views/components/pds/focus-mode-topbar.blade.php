@props([
    'title',
    'reference' => null,
    'patient' => null,
    'saveStatus' => 'neutral',
    'saveLabel' => 'Saved',
])

<section {{ $attributes->merge(['class' => 'pds-focus-mode-topbar']) }} aria-label="Focus Mode">
    <div class="pds-focus-mode-topbar__identity">
        <div class="pds-focus-mode-topbar__eyebrow">Focus Mode</div>
        <div class="pds-focus-mode-topbar__title-row">
            <h1>{{ $title }}</h1>
            @if (filled($reference))
                <x-pds.badge>{{ $reference }}</x-pds.badge>
            @endif
            @if (filled($patient))
                <span class="pds-focus-mode-topbar__patient">Patient: {{ $patient }}</span>
            @endif
        </div>
    </div>

    <div class="pds-focus-mode-topbar__meta">
        <span wire:loading wire:target="saveAsDraft,saveTemplateThreeVerification,save,auditVerification,refreshVerificationTemplate,saveAndBack">
            <x-pds.auto-save-indicator status="info">Saving...</x-pds.auto-save-indicator>
        </span>
        <span wire:loading.remove wire:target="saveAsDraft,saveTemplateThreeVerification,save,auditVerification,refreshVerificationTemplate,saveAndBack">
            <x-pds.auto-save-indicator :status="$saveStatus">{{ $saveLabel }}</x-pds.auto-save-indicator>
        </span>
        {{ $slot }}
    </div>
</section>
