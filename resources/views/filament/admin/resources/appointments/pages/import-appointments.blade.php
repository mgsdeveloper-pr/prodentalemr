<x-filament-panels::page>
    @php
        $acceptedColumns = $this->getAcceptedColumns();
        $requiredColumns = ['patient name', 'appointment_date', 'service'];
    @endphp

    <div style="display:flex;flex-direction:column;gap:22px;">
        <form wire:submit="importAppointments" style="border:1px solid #dbe4ee;border-radius:26px;background:#ffffff;box-shadow:0 16px 34px rgba(15,23,42,0.06);overflow:hidden;">
            <div style="display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:0;">
                <div style="padding:26px 28px;border-right:1px solid #edf2f7;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:20px;">
                        <div>
                            <h2 style="margin:0;font-size:30px;line-height:1.1;font-weight:900;color:#0f172a;">Appointment Import</h2>
                            <p style="margin:8px 0 0;max-width:720px;font-size:14px;line-height:1.7;color:#64748b;">
                                Use one file per clinic scope. CSV and Excel imports are supported.
                            </p>
                        </div>

                        <div style="display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:999px;background:#f8fafc;border:1px solid #dbe4ee;color:#334155;font-size:13px;font-weight:800;">
                            {{ $this->getSelectedClinicScopeLabel() }}
                        </div>
                    </div>

                    <div style="border:1px solid #e5e7eb;border-radius:20px;background:#ffffff;padding:16px;margin-bottom:18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                            <div>
                                <div style="font-size:12px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Accepted Columns</div>
                                <div style="margin-top:4px;font-size:13px;line-height:1.5;color:#64748b;">Start with the sample file, update the rows, then upload it below.</div>
                            </div>
                            <a
                                href="{{ asset('samples/appointment-import-sample.csv') }}"
                                download
                                style="display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:14px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:13px;font-weight:900;text-decoration:none;box-shadow:0 8px 18px rgba(37,99,235,0.08);"
                            >
                                Download Sample File
                            </a>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
                            @foreach ($acceptedColumns as $column)
                                <span style="display:inline-flex;align-items:center;padding:6px 9px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:11px;font-weight:800;">
                                    {{ $column }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div style="border:1px dashed #93c5fd;border-radius:22px;background:linear-gradient(135deg,#eff6ff 0%,#ffffff 100%);padding:18px;">
                        {{ $this->form }}

                        <div style="margin-top:18px;padding-top:18px;border-top:1px solid #dbeafe;display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;">
                            <a
                                href="{{ \App\Filament\Admin\Resources\Appointments\AppointmentResource::getUrl('index') }}"
                                style="display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:14px;border:1px solid #dbe4ee;background:#ffffff;color:#0f172a;font-size:13px;font-weight:800;text-decoration:none;"
                            >
                                Back to Appointments
                            </a>
                            <button
                                type="submit"
                                style="display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:14px;border:0;background:linear-gradient(135deg,#f59e0b 0%,#f97316 100%);color:#ffffff;font-size:13px;font-weight:800;box-shadow:0 10px 22px rgba(249,115,22,0.22);cursor:pointer;"
                            >
                                Import Appointments
                            </button>
                        </div>
                    </div>
                </div>

                <aside style="padding:26px;background:linear-gradient(180deg,#f8fafc 0%,#ffffff 100%);display:flex;flex-direction:column;gap:16px;">
                    <div style="border:1px solid #dbeafe;border-radius:20px;background:#eff6ff;padding:16px;">
                        <div style="font-size:12px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#1d4ed8;">Required</div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
                            @foreach ($requiredColumns as $column)
                                <span style="display:inline-flex;align-items:center;padding:7px 10px;border-radius:999px;background:#ffffff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:12px;font-weight:800;">
                                    {{ $column }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div style="border:1px solid #fed7aa;border-radius:20px;background:#fff7ed;padding:16px;">
                        <div style="font-size:12px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;color:#c2410c;">Import Rules</div>
                        <div style="display:flex;flex-direction:column;gap:10px;margin-top:12px;font-size:13px;line-height:1.6;color:#475569;">
                            <div><strong style="color:#0f172a;">No duplicate work:</strong> existing patients are matched before creating new ones.</div>
                            <div><strong style="color:#0f172a;">Optional time:</strong> blank appointment time defaults safely during import.</div>
                            <div><strong style="color:#0f172a;">Clinic scope:</strong> every row imports into the selected clinic.</div>
                        </div>
                    </div>
                </aside>
            </div>

        </form>

        @if ($lastImportResult)
            <section style="border:1px solid #dbe4ee;border-radius:22px;background:#ffffff;box-shadow:0 12px 28px rgba(15,23,42,0.05);overflow:hidden;">
                <div style="padding:18px 22px;border-bottom:1px solid #edf2f7;">
                    <h3 style="margin:0;font-size:18px;font-weight:800;color:#0f172a;">Last Import Summary</h3>
                    <p style="margin:6px 0 0;font-size:13px;color:#64748b;">
                        {{ $lastImportResult['imported'] ?? 0 }} imported from {{ $lastImportResult['total'] ?? 0 }} row(s). {{ $lastImportResult['failed'] ?? 0 }} failed.
                    </p>
                </div>
                <div style="padding:18px 22px;display:flex;flex-direction:column;gap:10px;">
                    @foreach (array_slice($lastImportResult['row_results'] ?? [], 0, 10) as $row)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;border:1px solid {{ ($row['status'] ?? null) === 'imported' ? '#bbf7d0' : '#fecaca' }};border-radius:16px;background:{{ ($row['status'] ?? null) === 'imported' ? '#f0fdf4' : '#fef2f2' }};padding:12px 14px;">
                            <div style="font-size:13px;color:#334155;">
                                <strong>Row {{ $row['row'] ?? '-' }}</strong>
                                <span style="color:#64748b;">{{ $row['patient'] ?? 'Unknown patient' }} · {{ $row['date'] ?? '-' }} · {{ $row['service'] ?? '-' }}</span>
                            </div>
                            <div style="font-size:12px;font-weight:800;color:{{ ($row['status'] ?? null) === 'imported' ? '#166534' : '#b91c1c' }};">
                                {{ $row['message'] ?? str($row['status'] ?? '')->title() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
