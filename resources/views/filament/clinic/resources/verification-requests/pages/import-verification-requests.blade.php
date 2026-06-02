<x-filament-panels::page>
    @php
        $requiredColumns = [
            'location_name',
            'provider_name',
            'appointment_date',
            'appointment_time',
            'patient_full_name',
            'patient_dob',
            'payer_name',
        ];

        $optionalColumns = [
            'priority',
            'form_type',
            'pms_id',
            'patient_identifier',
            'patient_zip',
            'is_pre_registered',
            'member_id',
            'group_number',
            'subscriber_name',
            'subscriber_dob',
            'plan_priority',
            'notes',
        ];
    @endphp

    <div style="display:flex;flex-direction:column;gap:24px;">
        <section
            wire:loading.flex
            wire:target="importRequests"
            style="display:none;align-items:center;justify-content:space-between;gap:20px;padding:18px 22px;border:1px solid #bae6fd;border-radius:20px;background:linear-gradient(135deg,#f0f9ff 0%,#ecfeff 100%);box-shadow:0 14px 28px rgba(14,165,233,0.10);"
        >
            <div style="display:flex;flex-direction:column;gap:6px;">
                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#0369a1;">Import in progress</div>
                <div style="font-size:20px;font-weight:800;color:#0f172a;">Processing verification requests...</div>
                <div style="font-size:14px;line-height:1.7;color:#475569;">Please keep this page open while we validate rows and create requests.</div>
            </div>
            <div style="min-width:240px;display:flex;flex-direction:column;gap:10px;">
                <div style="height:12px;border-radius:999px;background:#dbeafe;overflow:hidden;">
                    <div style="width:100%;height:100%;border-radius:999px;background:linear-gradient(90deg,#0ea5e9 0%,#06b6d4 50%,#0ea5e9 100%);background-size:200% 100%;animation:verification-import-progress 1.2s linear infinite;"></div>
                </div>
                <div style="font-size:13px;font-weight:700;color:#0f766e;text-align:right;">Running import checks and creating requests</div>
            </div>
        </section>

        <section style="border:1px solid #e5e7eb;border-radius:26px;background:linear-gradient(135deg,#fffdfa 0%,#fff7ed 100%);box-shadow:0 18px 40px rgba(15,23,42,0.06);overflow:hidden;">
            <div style="padding:24px 28px;display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,0.95fr);gap:22px;align-items:start;">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div style="display:inline-flex;align-items:center;gap:8px;width:max-content;padding:8px 12px;border-radius:999px;background:#ffffff;border:1px solid #fed7aa;color:#b45309;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;">
                        Import Verification Requests
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <h2 style="margin:0;font-size:30px;line-height:1.1;font-weight:800;color:#0f172a;">Bring verification-only clinics into the same workflow.</h2>
                        <p style="margin:0;max-width:760px;font-size:15px;line-height:1.75;color:#64748b;">
                            Upload the completed Excel template and create multiple insurance verification requests in one pass.
                            If the selected clinic has an active managed-service enrollment, those requests will also flow into the Admin verification queue automatically.
                        </p>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;">
                        <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Step 1</div>
                            <div style="margin-top:8px;font-size:18px;font-weight:800;color:#0f172a;">Download sample</div>
                            <div style="margin-top:6px;font-size:14px;line-height:1.6;color:#64748b;">Use the provided workbook so your column names match exactly.</div>
                        </div>
                        <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Step 2</div>
                            <div style="margin-top:8px;font-size:18px;font-weight:800;color:#0f172a;">Fill request rows</div>
                            <div style="margin-top:6px;font-size:14px;line-height:1.6;color:#64748b;">Enter one verification request per row with clinic-ready details.</div>
                        </div>
                        <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Step 3</div>
                            <div style="margin-top:8px;font-size:18px;font-weight:800;color:#0f172a;">Import and route</div>
                            <div style="margin-top:6px;font-size:14px;line-height:1.6;color:#64748b;">Requests become self-service or managed-service items automatically.</div>
                        </div>
                    </div>
                </div>

                <div style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;padding:22px;box-shadow:0 12px 28px rgba(249,115,22,0.08);display:flex;flex-direction:column;gap:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Workspace</div>
                            <div style="margin-top:4px;font-size:20px;font-weight:800;color:#0f172a;">Clinic scope</div>
                        </div>
                        <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:13px;font-weight:700;">
                            {{ \App\Support\ClinicPanelScope::selectedClinic()?->clinic_name ?? 'Choose a clinic in the sidebar' }}
                        </div>
                    </div>

                    <div style="border:1px solid #fcd34d;border-radius:18px;background:#fffbeb;padding:14px 16px;font-size:13px;line-height:1.7;color:#92400e;">
                        This import is designed for clients who purchased <strong>Insurance Verification</strong> without using the full Clinic PMS.
                        They can export their patient/request data into the Excel template and submit requests in bulk from here.
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
                        <div style="border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc;padding:14px 16px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Required columns</div>
                            <div style="margin-top:8px;font-size:24px;font-weight:800;color:#0f172a;">{{ count($requiredColumns) }}</div>
                        </div>
                        <div style="border:1px solid #dcfce7;border-radius:16px;background:#f0fdf4;padding:14px 16px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#166534;">Optional columns</div>
                            <div style="margin-top:8px;font-size:24px;font-weight:800;color:#14532d;">{{ count($optionalColumns) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section style="display:grid;grid-template-columns:minmax(0,1.25fr) minmax(300px,0.9fr);gap:24px;align-items:start;">
            <div style="border:1px solid #e5e7eb;border-radius:24px;background:#ffffff;box-shadow:0 14px 34px rgba(15,23,42,0.06);overflow:hidden;">
                <div style="padding:18px 22px;border-bottom:1px solid #edf2f7;">
                    <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#0f766e;">Upload file</div>
                    <div style="margin-top:6px;font-size:15px;line-height:1.7;color:#64748b;">Use the sample workbook or a CSV with matching headers. One row equals one verification request.</div>
                </div>
                <div style="padding:22px;">
                    {{ $this->form }}
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:18px;">
                <section style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;box-shadow:0 14px 30px rgba(15,23,42,0.05);overflow:hidden;">
                    <div style="padding:18px 20px;border-bottom:1px solid #edf2f7;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Required columns</div>
                    </div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr;gap:10px;">
                        @foreach ($requiredColumns as $column)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc;">
                                <span style="font-size:14px;font-weight:700;color:#0f172a;">{{ $column }}</span>
                                <span style="font-size:11px;font-weight:800;letter-spacing:0.1em;text-transform:uppercase;color:#b45309;">Required</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;box-shadow:0 14px 30px rgba(15,23,42,0.05);overflow:hidden;">
                    <div style="padding:18px 20px;border-bottom:1px solid #edf2f7;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Optional columns</div>
                    </div>
                    <div style="padding:18px 20px;display:flex;flex-wrap:wrap;gap:10px;">
                        @foreach ($optionalColumns as $column)
                            <span style="display:inline-flex;align-items:center;padding:10px 12px;border-radius:999px;border:1px solid #dbe4ee;background:#ffffff;color:#475569;font-size:13px;font-weight:700;">
                                {{ $column }}
                            </span>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>

        @if ($this->lastImportResult)
            @php
                $result = $this->lastImportResult;
                $totalRows = max((int) ($result['total'] ?? 0), 1);
                $importedRows = (int) ($result['imported'] ?? 0);
                $duplicateRows = (int) ($result['duplicates'] ?? 0);
                $failedRows = (int) ($result['failed'] ?? 0);
                $successWidth = round(($importedRows / $totalRows) * 100, 1);
                $duplicateWidth = round(($duplicateRows / $totalRows) * 100, 1);
                $failedWidth = round(($failedRows / $totalRows) * 100, 1);
                $rowResults = $result['row_results'] ?? [];
            @endphp

            <section style="border:1px solid #e5e7eb;border-radius:26px;background:#ffffff;box-shadow:0 16px 34px rgba(15,23,42,0.06);overflow:hidden;">
                <div style="padding:22px 24px;border-bottom:1px solid #edf2f7;display:grid;grid-template-columns:minmax(0,1.2fr) minmax(260px,0.8fr);gap:20px;align-items:start;">
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#0f766e;">Latest import summary</div>
                        <div style="font-size:28px;font-weight:800;color:#0f172a;">{{ $importedRows }} imported, {{ $duplicateRows }} skipped, {{ $failedRows }} failed</div>
                        <div style="font-size:14px;line-height:1.7;color:#64748b;">Review every row below. Duplicates were safely skipped, while failed rows still need workbook fixes before re-importing.</div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;">
                        <div style="border:1px solid #e2e8f0;border-radius:18px;background:#f8fafc;padding:14px 16px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Total</div>
                            <div style="margin-top:8px;font-size:24px;font-weight:800;color:#0f172a;">{{ $result['total'] ?? 0 }}</div>
                        </div>
                        <div style="border:1px solid #bbf7d0;border-radius:18px;background:#f0fdf4;padding:14px 16px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#166534;">Imported</div>
                            <div style="margin-top:8px;font-size:24px;font-weight:800;color:#166534;">{{ $importedRows }}</div>
                        </div>
                        <div style="border:1px solid #fde68a;border-radius:18px;background:#fffbeb;padding:14px 16px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#b45309;">Duplicate</div>
                            <div style="margin-top:8px;font-size:24px;font-weight:800;color:#b45309;">{{ $duplicateRows }}</div>
                        </div>
                        <div style="border:1px solid #fecaca;border-radius:18px;background:#fef2f2;padding:14px 16px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#b91c1c;">Failed</div>
                            <div style="margin-top:8px;font-size:24px;font-weight:800;color:#b91c1c;">{{ $failedRows }}</div>
                        </div>
                    </div>
                </div>

                <div style="padding:20px 24px;border-bottom:1px solid #edf2f7;display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                        <div style="font-size:13px;font-weight:700;color:#475569;">Import progress</div>
                        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:13px;font-weight:700;">
                            <span style="color:#166534;">Imported {{ $successWidth }}%</span>
                            <span style="color:#b45309;">Duplicate {{ $duplicateWidth }}%</span>
                            <span style="color:#b91c1c;">Failed {{ $failedWidth }}%</span>
                        </div>
                    </div>
                    <div style="height:14px;border-radius:999px;background:#e2e8f0;overflow:hidden;display:flex;">
                        <div style="width:{{ $successWidth }}%;background:linear-gradient(90deg,#22c55e 0%,#10b981 100%);"></div>
                        <div style="width:{{ $duplicateWidth }}%;background:linear-gradient(90deg,#fbbf24 0%,#f59e0b 100%);"></div>
                        <div style="width:{{ $failedWidth }}%;background:linear-gradient(90deg,#fb7185 0%,#ef4444 100%);"></div>
                    </div>
                </div>

                <div style="padding:0 0 8px;">
                    <div style="overflow:auto;">
                        <table style="width:100%;border-collapse:collapse;min-width:920px;">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e5e7eb;">Row</th>
                                    <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e5e7eb;">Patient</th>
                                    <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e5e7eb;">Location</th>
                                    <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e5e7eb;">Provider</th>
                                    <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e5e7eb;">Payer</th>
                                    <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e5e7eb;">Route</th>
                                    <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e5e7eb;">Reference</th>
                                    <th style="padding:14px 18px;text-align:left;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e5e7eb;">Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rowResults as $rowResult)
                                    @php
                                        $status = $rowResult['status'] ?? null;
                                        $isImported = $status === 'imported';
                                        $isDuplicate = $status === 'duplicate';
                                    @endphp
                                    <tr>
                                        <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;font-size:14px;font-weight:700;color:#0f172a;">{{ $rowResult['row'] ?? '-' }}</td>
                                        <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;font-size:14px;font-weight:700;color:#0f172a;">{{ $rowResult['patient'] ?: '-' }}</td>
                                        <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;font-size:14px;color:#334155;">{{ $rowResult['location'] ?: '-' }}</td>
                                        <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;font-size:14px;color:#334155;">{{ $rowResult['provider'] ?: '-' }}</td>
                                        <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;font-size:14px;color:#334155;">{{ $rowResult['payer'] ?: '-' }}</td>
                                        <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;">
                                            @if ($rowResult['mode'] === 'service')
                                                <span style="display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">Service</span>
                                            @elseif ($rowResult['mode'] === 'clinic')
                                                <span style="display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;background:#f8fafc;border:1px solid #cbd5e1;color:#334155;font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">Clinic</span>
                                            @else
                                                <span style="font-size:13px;color:#94a3b8;">-</span>
                                            @endif
                                        </td>
                                        <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;font-size:13px;font-weight:700;color:#334155;">{{ $rowResult['reference'] ?: '-' }}</td>
                                        <td style="padding:16px 18px;border-bottom:1px solid #edf2f7;">
                                            <div style="display:flex;flex-direction:column;gap:8px;">
                                                <span style="display:inline-flex;align-items:center;width:max-content;padding:8px 12px;border-radius:999px;font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;{{ $isImported ? 'background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;' : ($isDuplicate ? 'background:#fffbeb;border:1px solid #fde68a;color:#b45309;' : 'background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;') }}">
                                                    {{ $isImported ? 'Imported' : ($isDuplicate ? 'Duplicate' : 'Failed') }}
                                                </span>
                                                <div style="font-size:13px;line-height:1.6;color:#475569;">{{ $rowResult['message'] ?: '-' }}</div>
                                                @if ($isDuplicate && filled($rowResult['existing_url'] ?? null))
                                                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                                        @if (filled($rowResult['existing_status'] ?? null))
                                                            <span style="font-size:12px;font-weight:700;color:#92400e;">Open status: {{ $rowResult['existing_status'] }}</span>
                                                        @endif
                                                        <a href="{{ $rowResult['existing_url'] }}" style="display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;border-radius:12px;background:#fff7ed;border:1px solid #fdba74;color:#9a3412;font-size:12px;font-weight:800;text-decoration:none;">
                                                            Open existing request
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <style>
        @keyframes verification-import-progress {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</x-filament-panels::page>
