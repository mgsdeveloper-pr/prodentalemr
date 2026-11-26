@php
    $selectedClinic = $this->getSelectedClinic();
    $clinicOptions = $this->getClinicOptions();
    $clinics = $this->getClinicRows();
@endphp

<x-filament-panels::page>
    <style>
        .dso-page {
            display: grid;
            gap: 24px;
            max-width: none;
        }

        .dso-panel {
            border: 1px solid var(--pd-border);
            border-radius: 24px;
            background: var(--pd-card);
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .dso-hero {
            padding: 28px 32px;
            background: linear-gradient(135deg, var(--pd-card), var(--pd-bg));
        }

        .dso-pill {
            display: inline-flex;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--pwdl-border-subtle);
            background: var(--pwdl-status-info-soft);
            color: var(--pwdl-status-info-text);
            font-size: 12px;
            font-weight: 850;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .dso-title {
            margin: 14px 0 8px;
            color: var(--pd-text);
            font-size: 38px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .dso-copy {
            color: var(--pwdl-text-secondary);
            font-size: 16px;
            line-height: 1.7;
        }

        .dso-scope {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 26px;
            border-top: 1px solid var(--pwdl-border-subtle);
            background: var(--pd-bg);
        }

        .dso-select {
            min-width: min(520px, 100%);
            border: 1px solid var(--pwdl-border-strong);
            border-radius: 16px;
            background: var(--pd-card);
            color: var(--pd-text);
            font-weight: 750;
            padding: 12px 14px;
        }

        .dso-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dso-table th {
            background: var(--pd-bg);
            color: var(--pd-muted);
            font-size: 12px;
            font-weight: 850;
            letter-spacing: 0.12em;
            padding: 16px 20px;
            text-align: left;
            text-transform: uppercase;
        }

        .dso-table td {
            border-top: 1px solid var(--pwdl-border-subtle);
            color: var(--pwdl-text-secondary);
            font-size: 14px;
            padding: 18px 20px;
            vertical-align: middle;
        }

        .dso-name {
            color: var(--pd-text);
            font-weight: 850;
        }

        .dso-muted {
            margin-top: 4px;
            color: var(--pd-muted);
            font-size: 13px;
        }

        .dso-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--pwdl-border-subtle);
            background: var(--pwdl-status-info-soft);
            color: var(--pwdl-status-info-text);
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .dso-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--pwdl-border-subtle);
            border-radius: 14px;
            background: var(--pwdl-status-info-soft);
            color: var(--pwdl-status-info-text);
            font-size: 13px;
            font-weight: 850;
            padding: 10px 14px;
            text-decoration: none;
        }

        .dso-button--active {
            border-color: var(--pwdl-border-subtle);
            background: var(--pwdl-status-success-soft);
            color: var(--pwdl-status-success-text);
            pointer-events: none;
        }

        .dso-empty {
            padding: 42px 24px;
            color: var(--pd-muted);
            text-align: center;
        }

        @media (max-width: 900px) {
            .dso-scope {
                align-items: stretch;
                flex-direction: column;
            }

            .dso-table {
                min-width: 920px;
            }

            .dso-panel {
                overflow-x: auto;
            }
        }
    </style>

    <div class="dso-page">
        <section class="dso-panel">
            <div class="dso-hero">
                <div class="dso-pill">Network</div>
                <h1 class="dso-title">Clinic Directory</h1>
                <div class="dso-copy">
                    Select a clinic context for DSO-level review, then monitor appointment volume, verification demand, and service coverage across the network.
                </div>
            </div>
            <form class="dso-scope" method="GET" action="{{ route('dso.clinic-scope') }}">
                <div>
                    <div class="dso-name">Active clinic context</div>
                    <div class="dso-muted">{{ $selectedClinic ? $selectedClinic->clinic_name . ' - ' . ($selectedClinic->organization?->name ?? '') : 'All clinics in this DSO' }}</div>
                </div>
                <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">
                <select class="dso-select" name="clinic_id" onchange="this.form.submit()">
                    <option value="">All clinics</option>
                    @foreach ($clinicOptions as $clinicId => $label)
                        <option value="{{ $clinicId }}" @selected((int) $clinicId === (int) ($selectedClinic?->id ?? 0))>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </section>

        <section class="dso-panel">
            @if ($clinics->isEmpty())
                <div class="dso-empty">No clinics are linked to this DSO yet.</div>
            @else
                <table class="dso-table">
                    <thead>
                        <tr>
                            <th>Clinic</th>
                            <th>Services</th>
                            <th>Appointments MTD</th>
                            <th>Open Verification</th>
                            <th>Waiting</th>
                            <th>Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clinics as $clinic)
                            <tr>
                                <td>
                                    <div class="dso-name">{{ $clinic['name'] }}</div>
                                    <div class="dso-muted">{{ $clinic['organization'] }} · {{ $clinic['timezone'] }} · {{ $clinic['status'] }}</div>
                                </td>
                                <td>{{ $clinic['services'] }}</td>
                                <td>{{ $clinic['appointments_mtd'] }}</td>
                                <td>{{ $clinic['open_verifications'] }}</td>
                                <td>{{ $clinic['waiting_on_clinic'] }}</td>
                                <td>
                                    @if ($clinic['is_selected'])
                                        <span class="dso-button dso-button--active">Active</span>
                                    @else
                                        <a class="dso-button" href="{{ route('dso.clinic-scope', ['clinic_id' => $clinic['id'], 'redirect' => request()->fullUrl()]) }}">Set Active</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</x-filament-panels::page>
