<x-filament-panels::page>
    <style>
        .client-onboarding-shell { display: grid; gap: 24px; }
        .client-type-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .client-type-card { border: 1px solid #dbe4ee; border-radius: 22px; background: #ffffff; padding: 20px; box-shadow: 0 14px 30px rgba(15, 23, 42, .05); text-decoration: none; transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease; }
        .client-type-card:hover { transform: translateY(-2px); border-color: #93c5fd; box-shadow: 0 20px 40px rgba(15, 23, 42, .09); }
        .client-type-card.is-active { border-color: #f59e0b; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%); box-shadow: 0 18px 38px rgba(245, 158, 11, .14); }
        .client-type-label { display: inline-flex; border: 1px solid #bfdbfe; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 850; letter-spacing: .12em; padding: 7px 11px; text-transform: uppercase; }
        .client-type-title { margin: 16px 0 8px; color: #0f172a; font-size: 20px; font-weight: 900; }
        .client-type-copy { margin: 0; color: #64748b; font-size: 14px; line-height: 1.6; }
        .client-type-action { display: inline-flex; margin-top: 16px; color: #0f766e; font-size: 13px; font-weight: 850; }
        .client-flow-hero { border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); padding: 28px 32px; box-shadow: 0 16px 34px rgba(15, 23, 42, .06); }
        .client-flow-pill { display: inline-flex; align-items: center; border: 1px solid #bfdbfe; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 850; letter-spacing: .14em; padding: 8px 14px; text-transform: uppercase; }
        .client-flow-title { margin: 16px 0 8px; color: #020617; font-size: 34px; line-height: 1.1; font-weight: 900; letter-spacing: -.04em; }
        .client-flow-copy { margin: 0; max-width: 880px; color: #52637a; font-size: 15px; line-height: 1.7; }
        .client-flow-steps { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-top: 22px; }
        .client-flow-step { border: 1px solid #e2e8f0; border-radius: 18px; background: #ffffff; padding: 14px; }
        .client-flow-step span { display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 999px; background: #fef3c7; color: #b45309; font-size: 12px; font-weight: 900; }
        .client-flow-step strong { display: block; margin-top: 10px; color: #0f172a; font-size: 13px; font-weight: 850; }
        .client-flow-form { border: 1px solid #dbe4ee; border-radius: 26px; background: #ffffff; padding: 24px; box-shadow: 0 16px 34px rgba(15, 23, 42, .05); }
        .client-flow-switch { display: inline-flex; margin-top: 16px; color: #0f766e; font-size: 13px; font-weight: 850; text-decoration: none; }
        @media (max-width: 1100px) { .client-type-grid, .client-flow-steps { grid-template-columns: 1fr; } }
    </style>

    <div class="client-onboarding-shell">
    <section style="margin-bottom: 24px; border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); padding: 24px; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);">
        <div style="display: inline-flex; align-items: center; border: 1px solid #bfdbfe; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; padding: 8px 14px;">
            SaaS Setup
        </div>

        <h1 style="margin: 16px 0 8px; font-size: 32px; line-height: 1.15; font-weight: 850; color: #0f172a;">
            Client Onboarding
        </h1>

        <p style="margin: 0; max-width: 820px; color: #52637a; font-size: 15px; line-height: 1.7;">
            Select whether the client is a DSO, an organization, or a single clinic, then complete the matching guided setup.
        </p>
    </section>

    <section class="client-type-grid">
        <a class="client-type-card" href="{{ \App\Filament\Saas\Pages\DsoOnboarding::getUrl() }}">
            <span class="client-type-label">Enterprise</span>
            <h2 class="client-type-title">DSO</h2>
            <p class="client-type-copy">Create a DSO, first organization, first clinic, DSO subscription scope, and DSO admin user.</p>
            <span class="client-type-action">Start DSO onboarding</span>
        </a>

        <a class="client-type-card {{ $clientType === 'organization' ? 'is-active' : '' }}" href="{{ \App\Filament\Saas\Pages\TenantOnboarding::getUrl(['client_type' => 'organization']) }}">
            <span class="client-type-label">Group</span>
            <h2 class="client-type-title">Organization</h2>
            <p class="client-type-copy">Create a practice group or business entity with its first clinic, owner login, plan, and workspace access.</p>
            <span class="client-type-action">Use organization flow</span>
        </a>

        <a class="client-type-card {{ $clientType === 'single_clinic' ? 'is-active' : '' }}" href="{{ \App\Filament\Saas\Pages\TenantOnboarding::getUrl(['client_type' => 'single_clinic']) }}">
            <span class="client-type-label">Clinic</span>
            <h2 class="client-type-title">Single Clinic</h2>
            <p class="client-type-copy">Create one clinic customer without linking it to a DSO or larger organization structure.</p>
            <span class="client-type-action">Use single clinic flow</span>
        </a>
    </section>

    @if (in_array($clientType, ['organization', 'single_clinic'], true))
        <section class="client-flow-hero">
            <div class="client-flow-pill">{{ $clientType === 'single_clinic' ? 'Clinic Setup' : 'Organization Setup' }}</div>
            <h1 class="client-flow-title">
                {{ $clientType === 'single_clinic' ? 'Single Clinic Onboarding' : 'Organization Onboarding' }}
            </h1>
            <p class="client-flow-copy">
                {{ $clientType === 'single_clinic'
                    ? 'Create the clinic business record, first clinic, location, owner login, plan, and workspace access in one guided flow.'
                    : 'Create the organization, first clinic, location, owner login, plan, and workspace access in one guided flow.' }}
            </p>
            <a class="client-flow-switch" href="{{ \App\Filament\Saas\Pages\TenantOnboarding::getUrl() }}">Change client type</a>

            <div class="client-flow-steps">
                <div class="client-flow-step"><span>1</span><strong>{{ $clientType === 'single_clinic' ? 'Create Business' : 'Create Organization' }}</strong></div>
                <div class="client-flow-step"><span>2</span><strong>Add Clinic</strong></div>
                <div class="client-flow-step"><span>3</span><strong>Add Location</strong></div>
                <div class="client-flow-step"><span>4</span><strong>Create Owner</strong></div>
                <div class="client-flow-step"><span>5</span><strong>Assign Plan</strong></div>
            </div>
        </section>

        <section class="client-flow-form">
            <form wire:submit="create">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit">
                        Complete {{ $clientType === 'single_clinic' ? 'Single Clinic' : 'Organization' }} Setup
                    </x-filament::button>
                </div>
            </form>
        </section>
    @else
        <section style="border: 1px dashed #bfdbfe; border-radius: 24px; background: #f8fbff; padding: 28px; text-align: center; color: #52637a; font-size: 15px; line-height: 1.7;">
            Select one client type above to open the correct onboarding flow.
        </section>
    @endif
    </div>
</x-filament-panels::page>
