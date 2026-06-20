@php
    $dso = $this->getDso();
    $stats = $this->getStats();
    $clinics = $this->getClinicRows();
    $recentVerifications = $this->getRecentVerificationRows();
@endphp

<x-filament-panels::page>
    <style>
        .dso-shell {
            display: grid;
            gap: 24px;
            max-width: none;
        }

        .dso-hero,
        .dso-card {
            border: 1px solid #dbe4ee;
            border-radius: 24px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.06);
        }

        .dso-hero {
            padding: 28px 32px;
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
        }

        .dso-pill {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 850;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .dso-title {
            margin: 14px 0 8px;
            color: #020617;
            font-size: clamp(30px, 3vw, 44px);
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .dso-copy {
            max-width: 780px;
            color: #52637a;
            font-size: 16px;
            line-height: 1.7;
        }

        .dso-health {
            min-width: 260px;
            border: 1px solid #bbf7d0;
            border-radius: 22px;
            background: #f0fdf4;
            padding: 18px;
        }

        .dso-health__label {
            color: #047857;
            font-size: 12px;
            font-weight: 850;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .dso-health__value {
            margin-top: 8px;
            color: #064e3b;
            font-size: 28px;
            font-weight: 900;
        }

        .dso-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .dso-stat {
            border: 1px solid var(--dso-border, #dbe4ee);
            border-radius: 22px;
            background: var(--dso-bg, #ffffff);
            padding: 20px;
            min-height: 142px;
        }

        .dso-stat__label {
            color: var(--dso-accent, #64748b);
            font-size: 12px;
            font-weight: 850;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .dso-stat__value {
            margin-top: 12px;
            color: #020617;
            font-size: 38px;
            line-height: 1;
            font-weight: 900;
        }

        .dso-stat__hint {
            margin-top: 12px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .dso-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(360px, 0.8fr);
            gap: 20px;
        }

        .dso-card__header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #edf2f7;
        }

        .dso-card__title {
            margin: 0;
            color: #0f172a;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .dso-card__body {
            padding: 0;
            overflow: hidden;
        }

        .dso-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dso-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 850;
            letter-spacing: 0.12em;
            padding: 14px 18px;
            text-align: left;
            text-transform: uppercase;
        }

        .dso-table td {
            border-top: 1px solid #edf2f7;
            color: #334155;
            font-size: 14px;
            padding: 16px 18px;
            vertical-align: top;
        }

        .dso-name {
            color: #0f172a;
            font-weight: 850;
        }

        .dso-muted {
            margin-top: 4px;
            color: #64748b;
            font-size: 13px;
        }

        .dso-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 800;
        }

        .dso-empty {
            padding: 36px 24px;
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 1180px) {
            .dso-stats,
            .dso-grid {
                grid-template-columns: 1fr;
            }

            .dso-hero {
                flex-direction: column;
            }

            .dso-health {
                width: 100%;
            }
        }
    </style>

    <div class="dso-shell">
        <section class="dso-hero">
            <div>
                <div class="dso-pill">DSO Workspace</div>
                <h1 class="dso-title">{{ $dso?->name ?? 'Enterprise Dashboard' }}</h1>
                <div class="dso-copy">
                    Review your organization network, clinic service coverage, appointment volume, and verification activity from one executive workspace.
                </div>
            </div>
            <div class="dso-health">
                <div class="dso-health__label">Network Status</div>
                <div class="dso-health__value">{{ $dso?->service_status ? str($dso->service_status)->replace('_', ' ')->headline() : 'Active' }}</div>
                <div class="dso-muted">{{ $stats['active_clinics'] }} active clinics across {{ $stats['organizations'] }} organization(s)</div>
            </div>
        </section>

        <section class="dso-stats">
            <div class="dso-stat" style="--dso-bg:#ffffff; --dso-border:#dbe4ee; --dso-accent:#475569;">
                <div class="dso-stat__label">Organizations</div>
                <div class="dso-stat__value">{{ $stats['organizations'] }}</div>
                <div class="dso-stat__hint">Practice groups linked to this DSO.</div>
            </div>
            <div class="dso-stat" style="--dso-bg:#eff6ff; --dso-border:#bfdbfe; --dso-accent:#1d4ed8;">
                <div class="dso-stat__label">Clinics</div>
                <div class="dso-stat__value">{{ $stats['clinics'] }}</div>
                <div class="dso-stat__hint">{{ $stats['pms_clinics'] }} clinic operations, {{ $stats['verification_clinics'] }} verification enabled.</div>
            </div>
            <div class="dso-stat" style="--dso-bg:#fff7ed; --dso-border:#fed7aa; --dso-accent:#c2410c;">
                <div class="dso-stat__label">Open Verifications</div>
                <div class="dso-stat__value">{{ $stats['open_verifications'] }}</div>
                <div class="dso-stat__hint">{{ $stats['waiting_on_clinic'] }} waiting on clinic response.</div>
            </div>
            <div class="dso-stat" style="--dso-bg:#f0fdf4; --dso-border:#bbf7d0; --dso-accent:#047857;">
                <div class="dso-stat__label">This Month</div>
                <div class="dso-stat__value">{{ $stats['appointments_mtd'] }}</div>
                <div class="dso-stat__hint">{{ $stats['completed_mtd'] }} verifications completed this month.</div>
            </div>
        </section>

        <section class="dso-grid">
            <div class="dso-card">
                <div class="dso-card__header">
                    <h2 class="dso-card__title">Clinic Network</h2>
                    <a class="dso-badge" href="{{ \App\Filament\Dso\Pages\ClinicDirectory::getUrl() }}">View all clinics</a>
                </div>
                <div class="dso-card__body">
                    @if ($clinics->isEmpty())
                        <div class="dso-empty">No clinics are linked to this DSO yet.</div>
                    @else
                        <table class="dso-table">
                            <thead>
                                <tr>
                                    <th>Clinic</th>
                                    <th>Services</th>
                                    <th>Appointments</th>
                                    <th>Open Verification</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($clinics as $clinic)
                                    <tr>
                                        <td>
                                            <div class="dso-name">{{ $clinic['name'] }}</div>
                                            <div class="dso-muted">{{ $clinic['organization'] }} · {{ $clinic['status'] }}</div>
                                        </td>
                                        <td>{{ $clinic['services'] }}</td>
                                        <td>{{ $clinic['appointments_mtd'] }}</td>
                                        <td>{{ $clinic['open_verifications'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="dso-card">
                <div class="dso-card__header">
                    <h2 class="dso-card__title">Recent Verification Activity</h2>
                </div>
                <div class="dso-card__body">
                    @if ($recentVerifications->isEmpty())
                        <div class="dso-empty">No verification activity yet.</div>
                    @else
                        <table class="dso-table">
                            <thead>
                                <tr>
                                    <th>Request</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentVerifications as $row)
                                    <tr>
                                        <td>
                                            <div class="dso-name">{{ $row['patient'] }}</div>
                                            <div class="dso-muted">{{ $row['reference'] }} · {{ $row['clinic'] }}</div>
                                            <div class="dso-muted">{{ $row['updated'] }}</div>
                                        </td>
                                        <td>
                                            <span class="dso-badge">{{ $row['status'] }}</span>
                                            <div class="dso-muted">{{ $row['priority'] }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
