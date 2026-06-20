<x-filament-panels::page>
    <style>
        .dso-onboarding { display: grid; gap: 24px; }
        .dso-onboarding-hero { border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); padding: 28px 32px; box-shadow: 0 16px 34px rgba(15, 23, 42, .06); }
        .dso-onboarding-pill { display: inline-flex; align-items: center; border: 1px solid #bfdbfe; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 850; letter-spacing: .14em; padding: 8px 14px; text-transform: uppercase; }
        .dso-onboarding-title { margin: 16px 0 8px; color: #020617; font-size: 34px; line-height: 1.1; font-weight: 900; letter-spacing: -.04em; }
        .dso-onboarding-copy { margin: 0; max-width: 880px; color: #52637a; font-size: 15px; line-height: 1.7; }
        .dso-onboarding-flow { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-top: 22px; }
        .dso-onboarding-step { border: 1px solid #e2e8f0; border-radius: 18px; background: #ffffff; padding: 14px; }
        .dso-onboarding-step span { display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 999px; background: #fef3c7; color: #b45309; font-size: 12px; font-weight: 900; }
        .dso-onboarding-step strong { display: block; margin-top: 10px; color: #0f172a; font-size: 13px; font-weight: 850; }
        .dso-onboarding-form { border: 1px solid #dbe4ee; border-radius: 26px; background: #ffffff; padding: 24px; box-shadow: 0 16px 34px rgba(15, 23, 42, .05); }
        @media (max-width: 1100px) { .dso-onboarding-flow { grid-template-columns: 1fr; } }
    </style>

    <div class="dso-onboarding">
        <section class="dso-onboarding-hero">
            <div class="dso-onboarding-pill">Enterprise Setup</div>
            <h1 class="dso-onboarding-title">DSO Onboarding</h1>
            <p class="dso-onboarding-copy">
                Create the DSO, first organization, first clinic, subscription scope, and first DSO admin in one controlled flow.
            </p>

            <div class="dso-onboarding-flow">
                <div class="dso-onboarding-step"><span>1</span><strong>Create DSO</strong></div>
                <div class="dso-onboarding-step"><span>2</span><strong>Add Organization</strong></div>
                <div class="dso-onboarding-step"><span>3</span><strong>Add Clinic</strong></div>
                <div class="dso-onboarding-step"><span>4</span><strong>Assign Plan</strong></div>
                <div class="dso-onboarding-step"><span>5</span><strong>Invite DSO User</strong></div>
            </div>
        </section>

        <section class="dso-onboarding-form">
            <form wire:submit="create">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit">
                        Complete DSO Setup
                    </x-filament::button>
                </div>
            </form>
        </section>
    </div>
</x-filament-panels::page>
