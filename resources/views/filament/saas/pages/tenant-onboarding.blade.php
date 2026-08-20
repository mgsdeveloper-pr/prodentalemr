<x-filament-panels::page>
    @php
        $structureLabel = $clientType === 'single_clinic' ? 'Solo Practice' : 'Multi Location Organization';
        $modelLabel = match ($verificationModel) {
            'self_service' => 'Self-Managed',
            'hybrid' => 'Hybrid',
            default => 'Managed Service',
        };
    @endphp

    <style>
        .onboarding-page { display: grid; gap: 16px; }
        .onboarding-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; padding: 4px 0 18px; border-bottom: 1px solid #dbe4ee; }
        .onboarding-eyebrow { margin: 0 0 6px; color: #0f766e; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .onboarding-title { margin: 0; color: #0f172a; font-size: 28px; line-height: 1.2; font-weight: 800; }
        .onboarding-copy { margin: 6px 0 0; color: #64748b; font-size: 14px; }
        .onboarding-context { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
        .onboarding-badge { display: inline-flex; align-items: center; min-height: 30px; padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #334155; font-size: 12px; font-weight: 700; }
        .onboarding-flow { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border: 1px solid #dbe4ee; border-radius: 8px; background: #fff; overflow: hidden; }
        .onboarding-step { min-width: 0; padding: 12px; border-right: 1px solid #e2e8f0; color: #475569; font-size: 12px; font-weight: 700; }
        .onboarding-step:last-child { border-right: 0; }
        .onboarding-step span { display: block; margin-bottom: 3px; color: #0f766e; font-size: 11px; }
        .onboarding-form { border: 1px solid #dbe4ee; border-radius: 8px; background: #fff; padding: 20px; }
        .onboarding-actions { display: flex; justify-content: flex-end; padding-top: 18px; }
        @media (max-width: 1050px) { .onboarding-flow { grid-template-columns: repeat(2, minmax(0, 1fr)); } .onboarding-step { border-bottom: 1px solid #e2e8f0; } }
        @media (max-width: 720px) { .onboarding-header { align-items: flex-start; flex-direction: column; } .onboarding-context { justify-content: flex-start; } .onboarding-flow { grid-template-columns: 1fr; } .onboarding-step { border-right: 0; } }
    </style>

    <div class="onboarding-page">
        <header class="onboarding-header">
            <div>
                <p class="onboarding-eyebrow">Client Onboarding</p>
                <h1 class="onboarding-title">{{ $structureLabel }} Setup</h1>
                <p class="onboarding-copy">Complete each step, review the setup, then activate the client account.</p>
            </div>
            <div class="onboarding-context" aria-label="Selected onboarding configuration">
                <span class="onboarding-badge">{{ $structureLabel }}</span>
                <span class="onboarding-badge">{{ $modelLabel }}</span>
            </div>
        </header>

        <nav class="onboarding-flow" aria-label="Onboarding steps">
            <div class="onboarding-step"><span>01</span>Organization</div>
            <div class="onboarding-step"><span>02</span>Clinic</div>
            <div class="onboarding-step"><span>03</span>Location</div>
            <div class="onboarding-step"><span>04</span>Administrator</div>
            <div class="onboarding-step"><span>05</span>Services & Plan</div>
            <div class="onboarding-step"><span>06</span>Workspace</div>
            <div class="onboarding-step"><span>07</span>Review</div>
        </nav>

        <section class="onboarding-form">
            <form wire:submit="create">
                {{ $this->form }}
                <div class="onboarding-actions">
                    <x-filament::button type="submit">Review & Activate Client</x-filament::button>
                </div>
            </form>
        </section>
    </div>
</x-filament-panels::page>
