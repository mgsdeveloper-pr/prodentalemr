<x-filament-panels::page>
    @php
        $stats = $this->getAppointmentStats();
    @endphp

    <div style="display:flex;flex-direction:column;gap:22px;">
        <section style="border:1px solid #e5e7eb;border-radius:26px;background:linear-gradient(135deg,#ffffff 0%,#f8fbff 100%);box-shadow:0 18px 40px rgba(15,23,42,0.06);overflow:hidden;">
            <div style="padding:24px 28px;display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,0.8fr);gap:22px;align-items:start;">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div style="display:inline-flex;align-items:center;gap:8px;width:max-content;padding:8px 12px;border-radius:999px;background:#eef2ff;border:1px solid #c7d2fe;color:#4338ca;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;">
                        Scheduling Workspace
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <h2 style="margin:0;font-size:32px;line-height:1.08;font-weight:800;color:#0f172a;">All Appointments</h2>
                        <p style="margin:0;max-width:760px;font-size:15px;line-height:1.75;color:#64748b;">
                            Review the full clinic schedule in one operational view with patient details, provider context, appointment timing, and journey status.
                        </p>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;">
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
                    </div>
                </div>

                <div style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;padding:22px;box-shadow:0 12px 28px rgba(79,70,229,0.08);display:flex;flex-direction:column;gap:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Workspace</div>
                            <div style="margin-top:4px;font-size:20px;font-weight:800;color:#0f172a;">Appointment controls</div>
                        </div>
                        <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:13px;font-weight:700;">
                            {{ $this->getSelectedClinicName() ?: 'Clinic scope not selected' }}
                        </div>
                    </div>

                    <div style="border:1px solid #e0e7ff;border-radius:18px;background:#f8fafc;padding:14px 16px;font-size:13px;line-height:1.7;color:#475569;">
                        Showing timing in <strong>{{ $this->getDisplayTimezone() }}</strong>. Use the built-in search, filters, and row actions below to manage the schedule quickly.
                    </div>

                    @if (auth()->user()?->canCreateClinicAppointments() ?? false)
                        <div style="display:flex;gap:12px;flex-wrap:wrap;">
                            <a
                                href="{{ $this->getCreateUrl() }}"
                                style="display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:16px;background:linear-gradient(135deg,#f97316 0%,#fb7185 100%);color:#ffffff;font-size:13px;font-weight:800;text-decoration:none;box-shadow:0 10px 22px rgba(249,115,22,0.22);"
                            >
                                Add Appointment
                            </a>
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
