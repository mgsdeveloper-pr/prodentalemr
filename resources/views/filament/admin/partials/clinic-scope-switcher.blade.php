@php
    $selectedClinicId = \App\Support\AdminClinicScope::selectedClinicId();
    $viewer = auth()->user();
    $showAllClinicsOption = count($clinicOptions) > 1;
    $scopeHint = $showAllClinicsOption
        ? (($viewer?->hasFullVerificationClinicAccess() || $viewer?->canManageVerificationQueue())
            ? 'Choose one clinic or keep <strong>All Clinics</strong> selected to work the full verification queue.'
            : 'Choose one clinic or keep <strong>All Clinics</strong> selected to work across your assigned clinics only.')
        : 'Choose from your assigned clinics only to work verification requests in your scope.';
    $activeScopeLabel = $selectedClinicId
        ? ($clinicOptions[$selectedClinicId] ?? 'Selected clinic')
        : ($showAllClinicsOption
            ? (($viewer?->hasFullVerificationClinicAccess() || $viewer?->canManageVerificationQueue()) ? 'All Clinics' : 'All Assigned Clinics')
            : 'Assigned clinics');
@endphp

<style>
    .admin-workspace-scope {
        margin: 0.35rem 0 1rem;
        padding: 0.95rem;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: linear-gradient(180deg, #fffdf7 0%, #ffffff 100%);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .admin-workspace-scope-wrap {
        position: relative;
        margin: 0.15rem 0 1.1rem;
        padding-bottom: 1.05rem;
    }

    .admin-workspace-scope-wrap::after {
        content: '';
        position: absolute;
        inset-inline: 0.2rem;
        bottom: 0;
        height: 1px;
        background: linear-gradient(90deg, rgba(245, 158, 11, 0.22) 0%, rgba(148, 163, 184, 0.4) 52%, rgba(245, 158, 11, 0.12) 100%);
    }

    .admin-workspace-scope-wrap__section {
        margin: 0 0 0.65rem;
        padding: 0 0.15rem;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: #94a3b8;
    }

    .admin-workspace-scope__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.45rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #8b5e00;
    }

    .admin-workspace-scope__eyebrow::before {
        content: '';
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 999px;
        background: #f59e0b;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.16);
    }

    .admin-workspace-scope__title {
        margin: 0;
        font-size: 0.96rem;
        font-weight: 700;
        color: #0f172a;
    }

    .admin-workspace-scope__hint {
        margin: 0.25rem 0 0.8rem;
        font-size: 0.8rem;
        line-height: 1.35;
        color: #64748b;
    }

    .admin-workspace-scope__select {
        width: 100%;
        border: 1px solid #d8dee8;
        border-radius: 0.85rem;
        background: #ffffff;
        color: #111827;
        padding: 0.72rem 0.9rem;
        font-size: 0.88rem;
        line-height: 1.25rem;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
    }

    .admin-workspace-scope__select:focus {
        outline: none;
        border-color: #f5c76c;
        box-shadow: 0 0 0 3px rgba(245, 199, 108, 0.2);
    }

    .admin-workspace-scope__status {
        margin-top: 0.65rem;
        font-size: 0.77rem;
        font-weight: 600;
        color: #64748b;
    }

    .admin-workspace-scope__status strong {
        color: #0f172a;
    }

    html.dark .admin-workspace-scope {
        border-color: rgba(255, 255, 255, 0.08);
        background: linear-gradient(180deg, rgba(30, 41, 59, 0.92) 0%, rgba(17, 24, 39, 0.96) 100%);
        box-shadow: none;
    }

    html.dark .admin-workspace-scope-wrap::after {
        background: linear-gradient(90deg, rgba(250, 204, 21, 0.22) 0%, rgba(71, 85, 105, 0.75) 52%, rgba(250, 204, 21, 0.12) 100%);
    }

    html.dark .admin-workspace-scope-wrap__section {
        color: #64748b;
    }

    html.dark .admin-workspace-scope__eyebrow {
        color: #f8d17d;
    }

    html.dark .admin-workspace-scope__title {
        color: #f8fafc;
    }

    html.dark .admin-workspace-scope__hint,
    html.dark .admin-workspace-scope__status {
        color: #94a3b8;
    }

    html.dark .admin-workspace-scope__status strong {
        color: #f8fafc;
    }

    html.dark .admin-workspace-scope__select {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(255, 255, 255, 0.1);
        color: #f8fafc;
    }
</style>

<div class="admin-workspace-scope-wrap">
    <div class="admin-workspace-scope-wrap__section">Workspace</div>

    <form method="GET" action="{{ route('admin.clinic-scope') }}" class="admin-workspace-scope">
        <div class="admin-workspace-scope__eyebrow">Workspace</div>
        <h3 class="admin-workspace-scope__title">Clinic Scope</h3>
        <p class="admin-workspace-scope__hint">{!! $scopeHint !!}</p>

        <input type="hidden" name="redirect" value="{{ url()->full() }}">

        <select name="clinic_id" class="admin-workspace-scope__select" onchange="this.form.submit()">
            @if ($showAllClinicsOption)
                <option value="">All Clinics</option>
            @endif

            @foreach ($clinicOptions as $clinicId => $clinicLabel)
                <option value="{{ $clinicId }}" @selected((int) $selectedClinicId === (int) $clinicId)>
                    {{ $clinicLabel }}
                </option>
            @endforeach
        </select>

        <div class="admin-workspace-scope__status">
            Active scope:
            <strong>{{ $activeScopeLabel }}</strong>
        </div>
    </form>
</div>
