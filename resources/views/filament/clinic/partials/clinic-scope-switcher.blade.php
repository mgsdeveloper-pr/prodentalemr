@php
    $selectedClinicId = \App\Support\ClinicPanelScope::selectedClinicId();
@endphp

<style>
    .clinic-workspace-scope {
        margin: 0.35rem 0 1rem;
        padding: 0.95rem;
        border: 1px solid rgba(14, 116, 144, 0.09);
        border-radius: 1rem;
        background: linear-gradient(180deg, #f8fdff 0%, #ffffff 100%);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .clinic-workspace-scope-wrap {
        position: relative;
        margin: 0.15rem 0 1.1rem;
        padding-bottom: 1.05rem;
    }

    .clinic-workspace-scope-wrap::after {
        content: '';
        position: absolute;
        inset-inline: 0.2rem;
        bottom: 0;
        height: 1px;
        background: linear-gradient(90deg, rgba(6, 182, 212, 0.2) 0%, rgba(148, 163, 184, 0.35) 52%, rgba(6, 182, 212, 0.12) 100%);
    }

    .clinic-workspace-scope-wrap__section {
        margin: 0 0 0.65rem;
        padding: 0 0.15rem;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: #94a3b8;
    }

    .clinic-workspace-scope__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.45rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0f766e;
    }

    .clinic-workspace-scope__eyebrow::before {
        content: '';
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 999px;
        background: #06b6d4;
        box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.14);
    }

    .clinic-workspace-scope__title {
        margin: 0;
        font-size: 0.96rem;
        font-weight: 700;
        color: #0f172a;
    }

    .clinic-workspace-scope__hint {
        margin: 0.25rem 0 0.8rem;
        font-size: 0.8rem;
        line-height: 1.35;
        color: #64748b;
    }

    .clinic-workspace-scope__select {
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

    .clinic-workspace-scope__select:focus {
        outline: none;
        border-color: #7dd3fc;
        box-shadow: 0 0 0 3px rgba(125, 211, 252, 0.2);
    }

    .clinic-workspace-scope__status {
        margin-top: 0.65rem;
        font-size: 0.77rem;
        font-weight: 600;
        color: #64748b;
    }

    .clinic-workspace-scope__status strong {
        color: #0f172a;
    }

    html.dark .clinic-workspace-scope {
        border-color: rgba(255, 255, 255, 0.08);
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.92) 0%, rgba(17, 24, 39, 0.96) 100%);
        box-shadow: none;
    }

    html.dark .clinic-workspace-scope-wrap::after {
        background: linear-gradient(90deg, rgba(34, 211, 238, 0.16) 0%, rgba(71, 85, 105, 0.72) 52%, rgba(34, 211, 238, 0.12) 100%);
    }

    html.dark .clinic-workspace-scope-wrap__section {
        color: #64748b;
    }

    html.dark .clinic-workspace-scope__eyebrow {
        color: #67e8f9;
    }

    html.dark .clinic-workspace-scope__title {
        color: #f8fafc;
    }

    html.dark .clinic-workspace-scope__hint,
    html.dark .clinic-workspace-scope__status {
        color: #94a3b8;
    }

    html.dark .clinic-workspace-scope__status strong {
        color: #f8fafc;
    }

    html.dark .clinic-workspace-scope__select {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(255, 255, 255, 0.1);
        color: #f8fafc;
    }
</style>

<div class="clinic-workspace-scope-wrap">
    <div class="clinic-workspace-scope-wrap__section">Workspace</div>

    <form method="GET" action="{{ route('clinic.clinic-scope') }}" class="clinic-workspace-scope">
        <div class="clinic-workspace-scope__eyebrow">Workspace</div>
        <h3 class="clinic-workspace-scope__title">Clinic Scope</h3>
        <p class="clinic-workspace-scope__hint">
            Choose one clinic to work inside this panel, or keep <strong>All Clinics</strong> selected to browse across all clinic records.
        </p>

        <input type="hidden" name="redirect" value="{{ url()->full() }}">

        <select name="clinic_id" class="clinic-workspace-scope__select" onchange="this.form.submit()">
            <option value="">All Clinics</option>

            @foreach ($clinicOptions as $clinicId => $clinicLabel)
                <option value="{{ $clinicId }}" @selected((int) $selectedClinicId === (int) $clinicId)>
                    {{ $clinicLabel }}
                </option>
            @endforeach
        </select>

        <div class="clinic-workspace-scope__status">
            Active scope:
            <strong>
                {{ $selectedClinicId ? ($clinicOptions[$selectedClinicId] ?? 'Selected clinic') : 'All Clinics' }}
            </strong>
        </div>
    </form>
</div>
