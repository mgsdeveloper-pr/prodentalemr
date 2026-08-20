@php
    $selectedClinicId = \App\Support\AdminClinicScope::selectedClinicId();
    $selectedClinicName = \App\Support\AdminClinicScope::selectedClinic()?->clinic_name;
    $viewer = auth()->user();
    $showAllClinicsOption = count($clinicOptions) > 1;
    $activeScopeLabel = $selectedClinicId
        ? ($selectedClinicName ?: 'Selected clinic')
        : ($showAllClinicsOption
            ? (($viewer?->hasFullVerificationClinicAccess() || $viewer?->canManageVerificationQueue()) ? 'All Clinics' : 'All Assigned Clinics')
            : 'Assigned clinics');
@endphp

<style>
    .admin-workspace-scope {
        margin: 0;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #ffffff;
        box-shadow: none;
    }

    .admin-workspace-scope-wrap {
        position: relative;
        margin: 1rem 0 1rem;
        padding: 0 1rem 1rem;
    }

    .admin-workspace-scope-wrap::after {
        content: '';
        position: absolute;
        inset-inline: 1rem;
        bottom: 0;
        height: 1px;
        background: #e2e8f0;
    }

    .admin-workspace-scope__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.68rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0f766e;
    }

    .admin-workspace-scope__eyebrow::before {
        content: '';
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 999px;
        background: #0f766e;
        box-shadow: 0 0 0 4px #e8f8f4;
    }

    .admin-workspace-scope__selector {
        position: relative;
    }

    .admin-workspace-scope__trigger {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        width: 100%;
        height: 2.5rem;
        border: 1px solid #cfd8e3;
        border-radius: 0.65rem;
        background: #ffffff;
        color: #101828;
        padding: 0 0.65rem;
        font-size: 0.8rem;
        font-weight: 750;
        line-height: 1;
        box-shadow: none;
        cursor: pointer;
        text-align: left;
    }

    .admin-workspace-scope__trigger:focus-visible {
        outline: none;
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.14);
    }

    .admin-workspace-scope__chevron {
        width: 0.45rem;
        height: 0.45rem;
        flex: 0 0 auto;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        transform: rotate(45deg) translateY(-2px);
        transition: transform 150ms ease;
    }

    .admin-workspace-scope__trigger[aria-expanded='true'] .admin-workspace-scope__chevron {
        transform: rotate(225deg) translate(-2px, -2px);
    }

    .admin-workspace-scope__menu {
        position: absolute;
        z-index: 50;
        top: calc(100% + 0.35rem);
        inset-inline: 0;
        max-height: 15rem;
        overflow-y: auto;
        padding: 0.35rem;
        border: 1px solid #d8e0ea;
        border-radius: 0.65rem;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
    }

    .admin-workspace-scope__option {
        display: block;
        width: 100%;
        padding: 0.6rem 0.65rem;
        border: 0;
        border-radius: 0.45rem;
        background: transparent;
        color: #334155;
        font-size: 0.75rem;
        font-weight: 650;
        line-height: 1.35;
        text-align: left;
        cursor: pointer;
    }

    .admin-workspace-scope__option:hover,
    .admin-workspace-scope__option:focus-visible,
    .admin-workspace-scope__option[aria-current='true'] {
        outline: none;
        background: #e8f7f4;
        color: #0f766e;
    }

    .admin-workspace-scope__status {
        margin-top: 0.55rem;
        color: #667085;
        font-size: 0.72rem;
        font-weight: 650;
        line-height: 1.35;
    }

    .admin-workspace-scope__status strong {
        color: #0f172a;
    }

    html.dark .admin-workspace-scope {
        border-color: rgba(255, 255, 255, 0.08);
        background: rgba(15, 23, 42, 0.92);
        box-shadow: none;
    }

    html.dark .admin-workspace-scope-wrap::after {
        background: rgba(71, 85, 105, 0.75);
    }

    html.dark .admin-workspace-scope__eyebrow {
        color: #5eead4;
    }

    html.dark .admin-workspace-scope__status {
        color: #94a3b8;
    }

    html.dark .admin-workspace-scope__status strong {
        color: #f8fafc;
    }

    html.dark .admin-workspace-scope__trigger,
    html.dark .admin-workspace-scope__menu {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(255, 255, 255, 0.1);
        color: #f8fafc;
    }

    html.dark .admin-workspace-scope__option {
        color: #cbd5e1;
    }

    html.dark .admin-workspace-scope__option:hover,
    html.dark .admin-workspace-scope__option:focus-visible,
    html.dark .admin-workspace-scope__option[aria-current='true'] {
        background: rgba(15, 118, 110, 0.24);
        color: #99f6e4;
    }
</style>

<div class="admin-workspace-scope-wrap">
    <form method="GET" action="{{ route('admin.clinic-scope') }}" class="admin-workspace-scope" x-data="{ open: false }" @keydown.escape.window="open = false">
        <div class="admin-workspace-scope__eyebrow">Clinic Scope</div>

        <input type="hidden" name="redirect" value="{{ url()->full() }}">

        <div class="admin-workspace-scope__selector" @click.outside="open = false">
            <button type="button" class="admin-workspace-scope__trigger" aria-haspopup="listbox" :aria-expanded="open.toString()" @click="open = ! open">
                <span>{{ $activeScopeLabel }}</span>
                <span class="admin-workspace-scope__chevron" aria-hidden="true"></span>
            </button>

            <div class="admin-workspace-scope__menu" role="listbox" x-cloak x-show="open" x-transition.opacity>
                @if ($showAllClinicsOption)
                    <button type="submit" name="clinic_id" value="" class="admin-workspace-scope__option" role="option" aria-current="{{ $selectedClinicId ? 'false' : 'true' }}">
                        All Clinics
                    </button>
                @endif

                @foreach ($clinicOptions as $clinicId => $clinicLabel)
                    <button type="submit" name="clinic_id" value="{{ $clinicId }}" class="admin-workspace-scope__option" role="option" aria-current="{{ (int) $selectedClinicId === (int) $clinicId ? 'true' : 'false' }}">
                        {{ $clinicLabel }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="admin-workspace-scope__status">
            Active: <strong>{{ $activeScopeLabel }}</strong>
        </div>
    </form>
</div>
