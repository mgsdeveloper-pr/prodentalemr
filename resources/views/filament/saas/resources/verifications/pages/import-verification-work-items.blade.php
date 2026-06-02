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

        $columnGuidance = [
            'location_name' => 'Must match an existing location name in the selected clinic exactly.',
            'provider_name' => 'Must match the provider display name in that clinic/location.',
            'appointment_date' => 'Use a readable date such as 2026-05-28, 05/28/2026, or May 28 2026.',
            'appointment_time' => 'Use a readable time such as 09:30, 9:30 AM, or 14:00.',
            'patient_full_name' => 'Enter the full patient name. This helps match an internal patient when available.',
            'patient_dob' => 'Use a readable date of birth such as 1998-06-25 or 06/25/1998.',
            'payer_name' => 'Insurance carrier / payer name for the primary plan on that row.',
            'priority' => 'Allowed values: urgent or normal. Blank defaults to normal.',
            'form_type' => 'Allowed values: full form or short form. Blank defaults to full form.',
            'pms_id' => 'Optional PMS patient id used for a stronger patient match.',
            'patient_identifier' => 'Optional SSN / member id style identifier.',
            'patient_zip' => 'Optional ZIP / postal code for patient matching context.',
            'is_pre_registered' => 'Optional boolean. Use yes, y, true, or 1 for Yes.',
            'member_id' => 'Optional insurance member id. Helps match an existing policy.',
            'group_number' => 'Optional insurance group number.',
            'subscriber_name' => 'Optional subscriber full name.',
            'subscriber_dob' => 'Optional subscriber date of birth.',
            'plan_priority' => 'Allowed values: primary, secondary, tertiary. Blank defaults to primary.',
            'notes' => 'Optional free-text notes for the verification team.',
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
                <div style="font-size:20px;font-weight:800;color:#0f172a;">Creating Admin verification queue items...</div>
                <div style="font-size:14px;line-height:1.7;color:#475569;">Please keep this page open while we validate rows and create requests.</div>
            </div>
            <div style="min-width:240px;display:flex;flex-direction:column;gap:10px;">
                <div style="height:12px;border-radius:999px;background:#dbeafe;overflow:hidden;">
                    <div style="width:100%;height:100%;border-radius:999px;background:linear-gradient(90deg,#0ea5e9 0%,#06b6d4 50%,#0ea5e9 100%);background-size:200% 100%;animation:verification-import-progress 1.2s linear infinite;"></div>
                </div>
                <div style="font-size:13px;font-weight:700;color:#0f766e;text-align:right;">Validating rows and building queue records</div>
            </div>
        </section>

        <section style="border:1px solid #e5e7eb;border-radius:26px;background:linear-gradient(135deg,#ffffff 0%,#f8fbff 100%);box-shadow:0 18px 40px rgba(15,23,42,0.06);overflow:hidden;">
            <div style="padding:24px 28px;display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,0.95fr);gap:22px;align-items:start;">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div style="display:inline-flex;align-items:center;gap:8px;width:max-content;padding:8px 12px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;">
                        Admin Verification Import
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <h2 style="margin:0;font-size:30px;line-height:1.1;font-weight:800;color:#0f172a;">Load verification requests directly into the service queue.</h2>
                        <p style="margin:0;max-width:760px;font-size:15px;line-height:1.75;color:#64748b;">
                            Upload the completed Excel template and create multiple verification requests for the selected clinic in one pass.
                            This import is ideal for agency teams and internal operations users who receive request spreadsheets directly from clinics.
                        </p>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;">
                        <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Step 1</div>
                            <div style="margin-top:8px;font-size:18px;font-weight:800;color:#0f172a;">Select clinic scope</div>
                            <div style="margin-top:6px;font-size:14px;line-height:1.6;color:#64748b;">Choose the target clinic from the Admin workspace menu first.</div>
                        </div>
                        <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Step 2</div>
                            <div style="margin-top:8px;font-size:18px;font-weight:800;color:#0f172a;">Upload workbook</div>
                            <div style="margin-top:6px;font-size:14px;line-height:1.6;color:#64748b;">Use the same sample workbook or CSV format used by clinics.</div>
                        </div>
                        <div style="border:1px solid #e5e7eb;border-radius:18px;background:#ffffff;padding:16px 18px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Step 3</div>
                            <div style="margin-top:8px;font-size:18px;font-weight:800;color:#0f172a;">Work the queue</div>
                            <div style="margin-top:6px;font-size:14px;line-height:1.6;color:#64748b;">Imported rows land directly in the Admin verification queue.</div>
                        </div>
                    </div>
                </div>

                <div style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;padding:22px;box-shadow:0 12px 28px rgba(37,99,235,0.08);display:flex;flex-direction:column;gap:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;">Workspace</div>
                            <div style="margin-top:4px;font-size:20px;font-weight:800;color:#0f172a;">Clinic scope</div>
                        </div>
                        <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#334155;font-size:13px;font-weight:700;">
                            {{ \App\Support\AdminClinicScope::selectedClinic()?->clinic_name ?? 'Choose a clinic in the sidebar' }}
                        </div>
                    </div>

                    <div style="border:1px solid #dbeafe;border-radius:18px;background:#eff6ff;padding:14px 16px;font-size:13px;line-height:1.7;color:#1e3a8a;">
                        This import is for your internal service team. The created requests always enter the Admin verification queue, while still linking back to the clinic and any active verification enrollment when available.
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
                    <div style="margin-top:6px;font-size:15px;line-height:1.7;color:#64748b;">Use the same sample workbook or a CSV with matching headers. One row equals one verification request.</div>
                </div>
                <div style="padding:22px;">
                    {{ $this->form }}
                </div>
                <div style="padding:0 22px 22px;display:flex;justify-content:flex-end;align-items:center;">
                    <button
                        type="button"
                        wire:click="importRequests"
                        wire:loading.attr="disabled"
                        wire:target="importRequests"
                        style="display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:12px 18px;border:0;border-radius:14px;background:#f59e0b;color:#ffffff;font-size:14px;font-weight:800;box-shadow:0 12px 24px rgba(245,158,11,0.24);cursor:pointer;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" style="height:18px;width:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16" />
                        </svg>
                        <span wire:loading.remove wire:target="importRequests">Import requests</span>
                        <span wire:loading wire:target="importRequests">Importing...</span>
                    </button>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:18px;">
                <section style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;box-shadow:0 14px 30px rgba(15,23,42,0.05);overflow:hidden;">
                    <div style="padding:18px 20px;border-bottom:1px solid #edf2f7;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Import instructions</div>
                        <div style="margin-top:8px;font-size:18px;font-weight:800;color:#0f172a;">Prepare the workbook correctly</div>
                    </div>
                    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
                        <div style="border:1px solid #dbeafe;border-radius:16px;background:#eff6ff;padding:14px 16px;font-size:13px;line-height:1.7;color:#1e3a8a;">
                            Keep the first row as headers. Each row after that becomes one verification request in the service queue.
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
                            <div style="border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc;padding:14px 16px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Accepted files</div>
                                <div style="margin-top:8px;font-size:14px;line-height:1.7;color:#0f172a;">`.xlsx` and `.csv`</div>
                            </div>
                            <div style="border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc;padding:14px 16px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">One row means</div>
                                <div style="margin-top:8px;font-size:14px;line-height:1.7;color:#0f172a;">1 verification request</div>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
                            <div style="border:1px solid #fef3c7;border-radius:16px;background:#fffbeb;padding:14px 16px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#92400e;">Allowed values</div>
                                <div style="margin-top:8px;font-size:13px;line-height:1.8;color:#78350f;">
                                    <strong>priority</strong>: urgent, normal<br>
                                    <strong>form_type</strong>: full form, short form<br>
                                    <strong>plan_priority</strong>: primary, secondary, tertiary
                                </div>
                            </div>
                            <div style="border:1px solid #dcfce7;border-radius:16px;background:#f0fdf4;padding:14px 16px;">
                                <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#166534;">Boolean format</div>
                                <div style="margin-top:8px;font-size:13px;line-height:1.8;color:#14532d;">
                                    For <strong>is_pre_registered</strong>, use:<br>
                                    yes, y, true, or 1
                                </div>
                            </div>
                        </div>
                        <div style="border:1px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:14px 16px;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Helpful example</div>
                            <div style="margin-top:8px;padding:10px 12px;border:1px solid #fde68a;border-radius:12px;background:#fffbeb;font-size:12px;line-height:1.7;color:#92400e;">
                                Caution: the values below are dummy sample data for representation only.
                            </div>
                            <div style="margin-top:8px;font-size:13px;line-height:1.8;color:#334155;">
                                <strong>location_name</strong> = Sample Downtown Clinic<br>
                                <strong>provider_name</strong> = Dr. Alex Morgan<br>
                                <strong>appointment_date</strong> = 2026-06-15<br>
                                <strong>appointment_time</strong> = 09:30 AM<br>
                                <strong>patient_full_name</strong> = Sample Patient One<br>
                                <strong>patient_dob</strong> = 1992-04-18<br>
                                <strong>payer_name</strong> = Sample Dental Plan
                            </div>
                        </div>
                    </div>
                </section>

                <section style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;box-shadow:0 14px 30px rgba(15,23,42,0.05);overflow:hidden;">
                    <div style="padding:18px 20px;border-bottom:1px solid #edf2f7;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Required columns</div>
                    </div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr;gap:10px;">
                        @foreach ($requiredColumns as $column)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc;">
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <span style="font-size:14px;font-weight:700;color:#0f172a;">{{ $column }}</span>
                                    <span style="font-size:12px;line-height:1.6;color:#64748b;">{{ $columnGuidance[$column] ?? '' }}</span>
                                </div>
                                <span style="font-size:11px;font-weight:800;letter-spacing:0.1em;text-transform:uppercase;color:#1d4ed8;">Required</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section style="border:1px solid #e5e7eb;border-radius:22px;background:#ffffff;box-shadow:0 14px 30px rgba(15,23,42,0.05);overflow:hidden;">
                    <div style="padding:18px 20px;border-bottom:1px solid #edf2f7;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">Optional columns</div>
                    </div>
                    <div style="padding:18px 20px;display:grid;grid-template-columns:1fr;gap:10px;">
                        @foreach ($optionalColumns as $column)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:14px;background:#ffffff;">
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <span style="font-size:14px;font-weight:700;color:#0f172a;">{{ $column }}</span>
                                    <span style="font-size:12px;line-height:1.6;color:#64748b;">{{ $columnGuidance[$column] ?? '' }}</span>
                                </div>
                                <span style="font-size:11px;font-weight:800;letter-spacing:0.1em;text-transform:uppercase;color:#64748b;">Optional</span>
                            </div>
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
                                            <span style="display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;">
                                                Service
                                            </span>
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
