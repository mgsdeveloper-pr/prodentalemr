<x-filament-panels::page>
    @vite('resources/css/app.css')

    <style>
        .clinic-profile-header {
            border-bottom: 1px solid #dfe7f3;
            background: #ffffff;
            margin: -24px -24px 20px;
            padding: 20px 28px;
        }

        .clinic-profile-header h1 {
            margin: 0;
            color: #111936;
            font-size: 28px;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: 0;
        }

        .clinic-profile-header p {
            margin: 7px 0 0;
            color: #52617f;
            font-size: 14px;
        }

        .clinic-profile-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            color: #667697;
            font-size: 12px;
        }

        .clinic-profile-breadcrumb strong {
            color: #111936;
        }
    </style>

    <div class="space-y-5">
        <header class="clinic-profile-header">
            <h1>Clinic Profile</h1>
            <p>Maintain the selected clinic's operational identity, contacts, hours, and report branding.</p>
            <div class="clinic-profile-breadcrumb" aria-label="Breadcrumb">
                <span>Clinic Management</span>
                <span aria-hidden="true">›</span>
                <strong>Clinic Profile</strong>
            </div>
        </header>

        @unless ($this->canEditProfile())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                This profile is read-only. Clinic administrators and managers can edit it. SaaS support users must activate Support Mode for this clinic.
            </div>
        @endunless

        <form wire:submit="save" class="space-y-5">
            {{ $this->form }}

            @if ($this->canEditProfile())
                <div class="flex justify-end border-t border-slate-200 pt-4">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Save Clinic Profile
                    </x-filament::button>
                </div>
            @endif
        </form>
    </div>
</x-filament-panels::page>
