@php
    $rows = $this->getClinicReportRows();
    $services = $this->getServiceSummary();
@endphp

<x-filament-panels::page>
    <style>
        .dso-report { display: grid; gap: 24px; max-width: none; }
        .dso-report-card { border: 1px solid #dbe4ee; border-radius: 24px; background: #fff; box-shadow: 0 18px 42px rgba(15,23,42,.06); overflow: hidden; }
        .dso-select { border:1px solid #cbd5e1; border-radius:16px; background:#fff; color:#0f172a; font-weight:750; padding:12px 14px; min-width:220px; }
        .dso-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
        .dso-stat { border:1px solid var(--border,#dbe4ee); border-radius:22px; background:var(--bg,#fff); padding:20px; }
        .dso-stat-label { color:var(--accent,#64748b); font-size:12px; font-weight:850; letter-spacing:.12em; text-transform:uppercase; }
        .dso-stat-value { margin-top:12px; color:#020617; font-size:36px; font-weight:900; line-height:1; }
        .dso-table { width:100%; border-collapse:collapse; }
        .dso-table th { background:#f8fafc; color:#64748b; font-size:12px; font-weight:850; letter-spacing:.12em; padding:16px 20px; text-align:left; text-transform:uppercase; }
        .dso-table td { border-top:1px solid #edf2f7; color:#334155; font-size:14px; padding:18px 20px; }
        .dso-name { color:#0f172a; font-weight:850; }
        .dso-muted { margin-top:4px; color:#64748b; font-size:13px; }
        .dso-empty { padding:42px 24px; color:#64748b; text-align:center; }
        @media (max-width:1000px){ .dso-stats{grid-template-columns:1fr;} .dso-report-card{overflow-x:auto;} .dso-table{min-width:760px;} }
    </style>

    @include('filament.shared.partials.page-hero', [
        'eyebrow' => 'Reports',
        'title' => 'DSO Reports',
        'description' => 'Clinic-wise, month-wise, and service-wise reporting across the selected DSO network scope.',
        'scopeLabel' => 'Range',
        'scopeValue' => $this->getRangeLabel(),
        'rightContent' => '
            <select class="dso-select" wire:model.live="range">
                <option value="current_month">Current Month</option>
                <option value="last_month">Last Month</option>
                <option value="week">This Week</option>
            </select>
        ',
    ])

    <div class="dso-report" style="margin-top: 24px;">
        <section class="dso-stats">
            <div class="dso-stat" style="--bg:#eff6ff;--border:#bfdbfe;--accent:#1d4ed8;">
                <div class="dso-stat-label">Clinic Operations</div>
                <div class="dso-stat-value">{{ $services['clinic_operations'] }}</div>
            </div>
            <div class="dso-stat" style="--bg:#f0fdf4;--border:#bbf7d0;--accent:#047857;">
                <div class="dso-stat-label">Verification</div>
                <div class="dso-stat-value">{{ $services['verification'] }}</div>
            </div>
            <div class="dso-stat" style="--bg:#fff7ed;--border:#fed7aa;--accent:#c2410c;">
                <div class="dso-stat-label">Both Modules</div>
                <div class="dso-stat-value">{{ $services['both'] }}</div>
            </div>
            <div class="dso-stat" style="--bg:#f8fafc;--border:#cbd5e1;--accent:#475569;">
                <div class="dso-stat-label">Managed Services</div>
                <div class="dso-stat-value">{{ $services['managed_services'] }}</div>
            </div>
        </section>

        <section class="dso-report-card">
            @if ($rows->isEmpty())
                <div class="dso-empty">No clinics found for this DSO scope.</div>
            @else
                <table class="dso-table">
                    <thead>
                        <tr>
                            <th>Clinic</th>
                            <th>Appointments</th>
                            <th>Open Requests</th>
                            <th>Completed</th>
                            <th>Waiting on Clinic</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <div class="dso-name">{{ $row['clinic'] }}</div>
                                    <div class="dso-muted">{{ $row['organization'] }}</div>
                                </td>
                                <td>{{ $row['appointments'] }}</td>
                                <td>{{ $row['open'] }}</td>
                                <td>{{ $row['completed'] }}</td>
                                <td>{{ $row['waiting'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</x-filament-panels::page>
