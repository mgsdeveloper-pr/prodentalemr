<x-filament-panels::page>
    <section style="margin-bottom: 24px; border: 1px solid #dbe4ee; border-radius: 24px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); padding: 24px; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);">
        <div style="display: inline-flex; align-items: center; border: 1px solid #bfdbfe; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; padding: 8px 14px;">
            SaaS Setup
        </div>

        <h1 style="margin: 16px 0 8px; font-size: 32px; line-height: 1.15; font-weight: 850; color: #0f172a;">
            Client Onboarding
        </h1>

        <p style="margin: 0; max-width: 820px; color: #52637a; font-size: 15px; line-height: 1.7;">
            Create the organization, first clinic, plan, owner login, and workspace access in one guided flow.
        </p>
    </section>

    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit">
                Complete Client Setup
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
