<x-filament-panels::page>
    @php
        $stats = $this->getAppointmentStats();
        $isVerificationStats = array_key_exists('not_sent', $stats);
        $workspaceBadge = method_exists($this, 'getWorkspaceBadgeLabel') ? $this->getWorkspaceBadgeLabel() : 'Scheduling Workspace';
        $pageTitle = method_exists($this, 'getAppointmentPageTitle') ? $this->getAppointmentPageTitle() : 'All Appointments';
        $pageDescription = method_exists($this, 'getAppointmentPageDescription')
            ? $this->getAppointmentPageDescription()
            : 'Review the full clinic schedule in one operational view with patient details, provider context, appointment timing, and journey status.';
        $controlsTitle = method_exists($this, 'getControlsTitle') ? $this->getControlsTitle() : 'Appointment controls';
        $controlsDescription = method_exists($this, 'getControlsDescription')
            ? $this->getControlsDescription()
            : 'Showing timing in <strong>' . e($this->getDisplayTimezone()) . '</strong>. Use the built-in search, filters, and row actions below to manage the schedule quickly.';
        $canCreateAppointments = method_exists($this, 'canCreateAppointments') ? $this->canCreateAppointments() : (auth()->user()?->canCreateClinicAppointments() ?? false);
        $canImportAppointments = method_exists($this, 'canImportAppointments') ? $this->canImportAppointments() : false;
        $importUrl = method_exists($this, 'getImportUrl') ? $this->getImportUrl() : null;
        $hasDashboardDateFilter = method_exists($this, 'getDashboardDatePresetOptions');
        $dashboardDateOptions = $hasDashboardDateFilter ? $this->getDashboardDatePresetOptions() : [];
        $dashboardDateRangeLabel = $hasDashboardDateFilter ? $this->getDashboardDateRangeLabel() : null;
        $indiaNow = now('Asia/Kolkata');
    @endphp

    <div style="display:flex;flex-direction:column;gap:22px;">
        <section
            wire:poll.visible.10s
            style="border:1px solid #e5e7eb;border-radius:26px;background:linear-gradient(135deg,#ffffff 0%,#f8fbff 100%);box-shadow:0 18px 40px rgba(15,23,42,0.06);overflow:hidden;"
        >
            <div style="padding:24px 28px;display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,0.8fr);gap:22px;align-items:start;">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div style="display:inline-flex;align-items:center;gap:8px;width:max-content;padding:8px 12px;border-radius:999px;background:#eef2ff;border:1px solid #c7d2fe;color:#4338ca;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;">
                        {{ $workspaceBadge }}
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <h2 style="margin:0;font-size:32px;line-height:1.08;font-weight:800;color:#0f172a;">{{ $pageTitle }}</h2>
                        <p style="margin:0;max-width:760px;font-size:15px;line-height:1.75;color:#64748b;">
                            {{ $pageDescription }}
                        </p>
                    </div>

                    @if ($hasDashboardDateFilter)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border:1px solid #dbeafe;border-radius:18px;background:#f8fbff;padding:12px 14px;">
                            <div style="display:flex;flex-direction:column;gap:3px;">
                                <div style="font-size:11px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#1d4ed8;">Dashboard Range</div>
                                <div style="font-size:13px;font-weight:700;color:#334155;">{{ $dashboardDateRangeLabel }}</div>
                            </div>

                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <select
                                    wire:model.live="appointmentDatePreset"
                                    style="min-width:160px;border:1px solid #cbd5e1;border-radius:14px;background:#ffffff;padding:10px 12px;font-size:13px;font-weight:700;color:#0f172a;outline:none;"
                                >
                                    @foreach ($dashboardDateOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                @if ($this->appointmentDatePreset === 'custom')
                                    <input
                                        type="date"
                                        wire:model.live="customDateFrom"
                                        style="border:1px solid #cbd5e1;border-radius:14px;background:#ffffff;padding:10px 12px;font-size:13px;font-weight:700;color:#0f172a;outline:none;"
                                    >
                                    <input
                                        type="date"
                                        wire:model.live="customDateTo"
                                        style="border:1px solid #cbd5e1;border-radius:14px;background:#ffffff;padding:10px 12px;font-size:13px;font-weight:700;color:#0f172a;outline:none;"
                                    >
                                @endif
                            </div>
                        </div>
                    @endif

                    <div style="display:grid;grid-template-columns:repeat({{ $isVerificationStats ? 5 : 4 }},minmax(0,1fr));gap:14px;">
                        @if ($isVerificationStats)
                            <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Not Sent</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#0f172a;">{{ $stats['not_sent'] }}</div>
                            </div>
                            <div style="border:1px solid #bfdbfe;border-radius:18px;background:#eff6ff;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#1d4ed8;">Sent</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#1d4ed8;">{{ $stats['sent'] }}</div>
                            </div>
                            <div style="border:1px solid #fde68a;border-radius:18px;background:#fffbeb;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#b45309;">In Progress</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#b45309;">{{ $stats['in_progress'] }}</div>
                            </div>
                            <div style="border:1px solid #bbf7d0;border-radius:18px;background:#f0fdf4;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#166534;">Completed</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#166534;">{{ $stats['completed'] }}</div>
                            </div>
                            <div style="border:1px solid #fecaca;border-radius:18px;background:#fef2f2;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#b91c1c;">Cancelled</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#b91c1c;">{{ $stats['cancelled'] }}</div>
                            </div>
                        @else
                            <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Upcoming</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#0f172a;">{{ $stats['upcoming'] }}</div>
                            </div>
                            <div style="border:1px solid #dbeafe;border-radius:18px;background:#eff6ff;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#1d4ed8;">Today</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#1d4ed8;">{{ $stats['today'] }}</div>
                            </div>
                            <div style="border:1px solid #bbf7d0;border-radius:18px;background:#f0fdf4;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#166534;">Completed</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#166534;">{{ $stats['completed'] }}</div>
                            </div>
                            <div style="border:1px solid #fde68a;border-radius:18px;background:#fffbeb;padding:16px 18px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#b45309;">Pending</div>
                                <div style="margin-top:8px;font-size:28px;font-weight:800;color:#b45309;">{{ $stats['pending'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;padding:22px;box-shadow:0 12px 28px rgba(79,70,229,0.08);display:flex;flex-direction:column;gap:16px;align-self:end;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Workspace</div>
                            <div style="margin-top:4px;font-size:20px;font-weight:800;color:#0f172a;">{{ $controlsTitle }}</div>
                        </div>
                        <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:13px;font-weight:700;">
                            {{ $this->getSelectedClinicName() ?: 'Clinic scope not selected' }}
                        </div>
                    </div>

                    @if (filled($controlsDescription))
                        <div style="border:1px solid #e0e7ff;border-radius:18px;background:#f8fafc;padding:14px 16px;font-size:13px;line-height:1.7;color:#475569;">
                            {!! $controlsDescription !!}
                        </div>
                    @endif

                    @if ($isVerificationStats)
                        <div style="border:1px solid #dbeafe;border-radius:18px;background:linear-gradient(135deg,#eff6ff 0%,#ffffff 100%);padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:14px;">
                            <div style="display:flex;flex-direction:column;gap:4px;">
                                <div style="font-size:11px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#1d4ed8;">Today</div>
                                <div style="font-size:15px;font-weight:800;color:#0f172a;">{{ $indiaNow->format('M d, Y') }}</div>
                            </div>
                            <div style="display:flex;align-items:flex-end;gap:8px;">
                                <span style="font-size:26px;font-weight:900;letter-spacing:-0.04em;color:#0f172a;">{{ $indiaNow->format('h:i A') }}</span>
                                <span style="padding-bottom:4px;font-size:11px;font-weight:900;letter-spacing:0.12em;color:#1d4ed8;">IST</span>
                            </div>
                        </div>
                    @endif

                    @if ($canCreateAppointments || ($canImportAppointments && filled($importUrl)))
                        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:space-between;">
                            @if ($canImportAppointments && filled($importUrl))
                                <a
                                    href="{{ $importUrl }}"
                                    style="display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:16px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:13px;font-weight:800;text-decoration:none;box-shadow:0 10px 22px rgba(37,99,235,0.08);"
                                >
                                    Import Appointments
                                </a>
                            @endif

                            @if ($canCreateAppointments)
                            <a
                                href="{{ $this->getCreateUrl() }}"
                                style="display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:16px;background:linear-gradient(135deg,#f97316 0%,#fb7185 100%);color:#ffffff;font-size:13px;font-weight:800;text-decoration:none;box-shadow:0 10px 22px rgba(249,115,22,0.22);"
                            >
                                Add Appointment
                            </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section style="border:1px solid #e5e7eb;border-radius:26px;background:#ffffff;box-shadow:0 16px 34px rgba(15,23,42,0.06);overflow:hidden;">
            <div style="padding:22px;">
                {{ $this->table }}
            </div>
        </section>
    </div>
</x-filament-panels::page>
