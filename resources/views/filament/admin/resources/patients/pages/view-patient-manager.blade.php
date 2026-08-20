<x-filament-panels::page>
    @php
        use App\Filament\Admin\Resources\Patients\PatientResource;

        /** @var \App\Models\Patient $patient */
        $patient = $this->getRecord();
        $stats = $this->getPatientStats();
        $requests = $this->getVerificationRequests();
        $formLogs = $this->getFormLogs();
        $timeline = $this->getActivityTimeline();
        $patientName = $patient->full_name ?: 'Patient';
        $initials = collect([$patient->first_name, $patient->last_name])
            ->filter()
            ->map(fn ($part) => strtoupper(substr((string) $part, 0, 1)))
            ->implode('') ?: 'PT';
        $gender = filled($patient->gender) ? str($patient->gender)->replace('_', ' ')->title()->toString() : '-';
    @endphp

    <div style="display: flex; flex-direction: column; gap: 20px;">
        <section style="border: 1px solid #dbe4ee; border-radius: 22px; background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%); padding: 22px 24px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06); display: flex; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 18px;">
                <div style="width: 72px; height: 72px; border-radius: 18px; background: #ecfdf5; border: 1px solid #99f6e4; display: flex; align-items: center; justify-content: center; color: #0f766e; font-size: 25px; font-weight: 900;">
                    {{ $initials }}
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 900; color: #0f766e; letter-spacing: 0.14em; text-transform: uppercase;">Patient Manager</div>
                    <h1 style="margin: 8px 0 0; color: #0f172a; font-size: 31px; line-height: 1.1; font-weight: 900;">{{ $patientName }}</h1>
                    <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 10px; color: #64748b; font-size: 13px;">
                        <span>{{ $patient->clinic?->clinic_name ?: 'No clinic' }}</span>
                        <span>&bull;</span>
                        <span>{{ $patient->location?->location_name ?: 'No location' }}</span>
                        <span>&bull;</span>
                        <span>{{ $patient->age_label ?: 'Age unavailable' }}</span>
                    </div>
                </div>
            </div>

            <a href="{{ PatientResource::getUrl('index') }}" style="display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 15px; border-radius: 12px; border: 1px solid #d7e2ef; background: #ffffff; color: #334155; text-decoration: none; font-size: 13px; font-weight: 800;">
                Back
            </a>
        </section>

        <section style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px;">
            @foreach ($stats as $stat)
                <div style="border: 1px solid #dbe4ee; border-radius: 16px; background: #ffffff; padding: 16px 18px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);">
                    <div style="font-size: 12px; color: #64748b; font-weight: 800;">{{ $stat['label'] }}</div>
                    <div style="margin-top: 9px; color: #0f172a; font-size: 25px; font-weight: 900;">{{ $stat['value'] }}</div>
                </div>
            @endforeach
        </section>

        <section style="display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 18px; align-items: start;">
            <div style="border: 1px solid #dbe4ee; border-radius: 20px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); overflow: hidden;">
                <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 12px; color: #0f766e; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase;">General Data</div>
                    <h2 style="margin: 7px 0 0; font-size: 20px; font-weight: 900; color: #0f172a;">Patient Context</h2>
                </div>
                <div style="padding: 18px 20px; display: grid; gap: 13px;">
                    @foreach ([
                        ['label' => 'DOB', 'value' => $patient->dob?->format('M d, Y') ?: '-'],
                        ['label' => 'Gender', 'value' => $gender],
                        ['label' => 'Phone', 'value' => $patient->phone ?: '-'],
                        ['label' => 'Email', 'value' => $patient->email ?: '-'],
                        ['label' => 'PMS Patient ID', 'value' => $patient->pms_patient_id ?: '-'],
                        ['label' => 'Insurance', 'value' => $patient->insurance_provider ?: '-'],
                        ['label' => 'Member ID', 'value' => $patient->insurance_number ?: '-'],
                        ['label' => 'Guarantor', 'value' => $patient->guarantor_name ?: '-'],
                    ] as $field)
                        <div style="display: flex; justify-content: space-between; gap: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                            <span style="color: #64748b; font-size: 13px;">{{ $field['label'] }}</span>
                            <strong style="color: #0f172a; font-size: 13px; text-align: right;">{{ $field['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 18px;">
                <section style="border: 1px solid #dbe4ee; border-radius: 20px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); overflow: hidden;">
                    <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                        <div style="font-size: 12px; color: #0f766e; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase;">Verification</div>
                        <h2 style="margin: 7px 0 0; font-size: 20px; font-weight: 900; color: #0f172a;">Request History</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    @foreach (['Request', 'Status', 'Outcome', 'Assigned', 'Template', 'Logs', 'Created'] as $heading)
                                        <th style="padding: 12px 14px; text-align: left; color: #64748b; font-size: 12px; font-weight: 900;">{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $request)
                                    <tr>
                                        <td style="padding: 13px 14px; border-top: 1px solid #edf2f7;">
                                            <a href="{{ $request['url'] }}" style="color: #0f766e; font-size: 13px; font-weight: 900; text-decoration: none;">{{ $request['reference'] }}</a>
                                            <div style="margin-top: 3px; color: #64748b; font-size: 12px;">{{ $request['title'] }}</div>
                                        </td>
                                        <td style="padding: 13px 14px; border-top: 1px solid #edf2f7; color: #334155; font-size: 13px;">{{ $request['status'] }}</td>
                                        <td style="padding: 13px 14px; border-top: 1px solid #edf2f7; color: #334155; font-size: 13px;">{{ $request['outcome'] }}</td>
                                        <td style="padding: 13px 14px; border-top: 1px solid #edf2f7; color: #334155; font-size: 13px;">{{ $request['assigned_to'] }}</td>
                                        <td style="padding: 13px 14px; border-top: 1px solid #edf2f7; color: #334155; font-size: 13px;">{{ $request['template'] }}</td>
                                        <td style="padding: 13px 14px; border-top: 1px solid #edf2f7; color: #334155; font-size: 13px;">{{ $request['submissions'] }} forms / {{ $request['activities'] }} events</td>
                                        <td style="padding: 13px 14px; border-top: 1px solid #edf2f7; color: #64748b; font-size: 12px;">{{ $request['created_at'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="padding: 18px 14px; border-top: 1px solid #edf2f7; color: #64748b; font-size: 14px;">No verification requests found for this patient.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px;">
                    <div style="border: 1px solid #dbe4ee; border-radius: 20px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); overflow: hidden;">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h2 style="margin: 0; font-size: 18px; font-weight: 900; color: #0f172a;">Form Logs</h2>
                        </div>
                        <div style="padding: 14px 20px; display: grid; gap: 12px;">
                            @forelse ($formLogs as $log)
                                <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                                    <div style="display: flex; justify-content: space-between; gap: 10px;">
                                        <strong style="font-size: 13px; color: #0f172a;">{{ $log['request'] }} {{ $log['version'] }}</strong>
                                        <span style="font-size: 12px; color: #64748b;">{{ $log['panel'] }}</span>
                                    </div>
                                    <div style="margin-top: 5px; font-size: 12px; color: #64748b;">{{ $log['status'] }} by {{ $log['submitted_by'] }}</div>
                                    <div style="margin-top: 4px; font-size: 12px; color: #94a3b8;">{{ $log['submitted_at'] }}</div>
                                </div>
                            @empty
                                <div style="color: #64748b; font-size: 14px;">No saved form submissions yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div style="border: 1px solid #dbe4ee; border-radius: 20px; background: #ffffff; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); overflow: hidden;">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                            <h2 style="margin: 0; font-size: 18px; font-weight: 900; color: #0f172a;">Activity Timeline</h2>
                        </div>
                        <div style="padding: 14px 20px; display: grid; gap: 12px;">
                            @forelse ($timeline as $event)
                                <div style="padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                                    <div style="font-size: 13px; font-weight: 900; color: #0f172a;">{{ $event['type'] }} &middot; {{ $event['request'] }}</div>
                                    <div style="margin-top: 5px; font-size: 12px; color: #475569;">{{ $event['description'] }}</div>
                                    <div style="margin-top: 4px; font-size: 12px; color: #94a3b8;">{{ $event['author'] }} &middot; {{ $event['created_at'] }}</div>
                                </div>
                            @empty
                                <div style="color: #64748b; font-size: 14px;">No verification activity recorded yet.</div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
</x-filament-panels::page>
