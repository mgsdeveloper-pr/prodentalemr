<x-filament-panels::page>
    @php
        use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
        use App\Filament\Clinic\Resources\Patients\PatientResource;
        use App\Filament\Clinic\Resources\TreatmentPlans\TreatmentPlanResource;

        /** @var \App\Models\Patient $patient */
        $patient = $this->getRecord();
        $stats = $this->getProfileStats();
        $appointments = $this->getRecentAppointments();
        $plans = $this->getRecentTreatmentPlans();
        $patientInitials = collect([$patient->first_name, $patient->last_name])
            ->filter()
            ->map(fn ($part) => strtoupper(substr((string) $part, 0, 1)))
            ->implode('');
        $patientInitials = $patientInitials ?: 'PT';
        $statusLabel = $patient->status ? 'Active' : 'Inactive';
        $genderLabel = filled($patient->gender) ? str($patient->gender)->replace('_', ' ')->title()->toString() : '-';
        $patientName = trim($patient->first_name . ' ' . $patient->last_name) ?: 'Patient Profile';
        $appointmentIndexUrl = AppointmentResource::getUrl('index');
        $treatmentIndexUrl = TreatmentPlanResource::getUrl('index');
        $patientIndexUrl = PatientResource::getUrl('index');
    @endphp

    <div style="display: flex; flex-direction: column; gap: 20px; background: #f5f7fb; margin: -10px; padding: 10px; border-radius: 22px;">
        <section style="border: 1px solid #e4ebf5; border-radius: 18px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); padding: 20px 22px; display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 18px;">
                <div style="width: 78px; height: 78px; border-radius: 24px; background: linear-gradient(145deg, #edf4ff 0%, #dae7ff 100%); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; color: #335cda; box-shadow: inset 0 0 0 1px #d5e2fb;">
                    {{ $patientInitials }}
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Patient Profile</div>
                    <div style="font-size: 32px; line-height: 1.1; font-weight: 900; color: #0f172a;">{{ $patientName }}</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px 12px; font-size: 14px; color: #64748b;">
                        <span>{{ $patient->age_label ?: 'Age unavailable' }}</span>
                        <span>&bull;</span>
                        <span>{{ $genderLabel }}</span>
                        <span>&bull;</span>
                        <span>{{ $patient->location?->location_name ?: 'No location assigned' }}</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: flex-start; gap: 10px; flex-wrap: wrap; margin-left: auto;">
                <div style="padding: 10px 14px; border-radius: 999px; border: 1px solid {{ $patient->status ? '#bbf7d0' : '#fecaca' }}; background: {{ $patient->status ? '#f0fdf4' : '#fef2f2' }}; color: {{ $patient->status ? '#15803d' : '#b91c1c' }}; font-size: 13px; font-weight: 800;">
                    {{ $statusLabel }}
                </div>
                <a href="{{ $patientIndexUrl }}" style="display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 16px; border-radius: 12px; border: 1px solid #d7e2ef; background: #ffffff; color: #1e293b; font-size: 14px; font-weight: 700; text-decoration: none;">
                    Back
                </a>
            </div>
        </section>

        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px;">
            @foreach ($stats as $stat)
                <section style="border: 1px solid #e6ebf2; border-radius: 16px; background: #ffffff; padding: 18px 18px 16px; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);">
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: #64748b;">{{ $stat['label'] }}</div>
                        <div style="margin-top: 10px; font-size: 20px; font-weight: 900; color: #0f172a;">{{ $stat['value'] }}</div>
                    </div>
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #eef2ff; display: flex; align-items: center; justify-content: center; color: #5b6fd8; font-size: 18px; font-weight: 800;">
                        @switch($stat['icon'])
                            @case('calendar') C @break
                            @case('check-calendar') A @break
                            @case('stethoscope') E @break
                            @default $
                        @endswitch
                    </div>
                </section>
            @endforeach
        </div>

        <section style="border: 1px solid #e6ebf2; border-radius: 16px; background: #ffffff; overflow: hidden; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.04);">
            <div style="padding: 0 22px; display: flex; align-items: center; gap: 24px; flex-wrap: wrap; border-bottom: 1px solid #edf2f7; overflow-x: auto;">
                <a href="#profile" style="padding: 16px 0 14px; border-bottom: 3px solid #4f6de6; color: #4f6de6; font-size: 13px; font-weight: 800; text-decoration: none; white-space: nowrap;">Profile</a>
                <a href="#reports" style="padding: 16px 0 14px; color: #64748b; font-size: 13px; font-weight: 800; text-decoration: none; white-space: nowrap;">Reports</a>
                <a href="#appointments" style="padding: 16px 0 14px; color: #64748b; font-size: 13px; font-weight: 800; text-decoration: none; white-space: nowrap;">Appointments</a>
                <a href="#follow-up" style="padding: 16px 0 14px; color: #64748b; font-size: 13px; font-weight: 800; text-decoration: none; white-space: nowrap;">Treatment &amp; Follow-Up</a>
            </div>

            <div style="padding: 22px; display: flex; flex-direction: column; gap: 28px;">
                <div id="profile" style="display: flex; flex-direction: column; gap: 18px;">
                    <div>
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Profile</div>
                        <h3 style="margin: 8px 0 0; font-size: 28px; line-height: 1.1; font-weight: 900; color: #0f172a;">My Profile</h3>
                    </div>

                    <div style="display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 20px; align-items: start;">
                        <div style="display: flex; flex-direction: column; gap: 18px;">
                            <div style="min-height: 280px; border-radius: 18px; background: linear-gradient(180deg, #f6f8fc 0%, #edf2f7 100%); display: flex; align-items: center; justify-content: center; border: 1px solid #e6ebf2;">
                                <div style="width: 150px; height: 150px; border-radius: 999px; background: #ffffff; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 900; color: #94a3b8; box-shadow: 0 12px 24px rgba(148, 163, 184, 0.12);">
                                    {{ $patientInitials }}
                                </div>
                            </div>

                            <div style="padding: 18px; border: 1px solid #e6ebf2; border-radius: 16px; background: #f8fafc; display: flex; flex-direction: column; gap: 12px;">
                                <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748b;">Patient Snapshot</div>
                                <div style="display: grid; gap: 10px;">
                                    <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px;">
                                        <span style="color: #64748b;">Email</span>
                                        <span style="color: #0f172a; font-weight: 700; text-align: right;">{{ $patient->email ?: '-' }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px;">
                                        <span style="color: #64748b;">Phone</span>
                                        <span style="color: #0f172a; font-weight: 700; text-align: right;">{{ $patient->phone ?: '-' }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px;">
                                        <span style="color: #64748b;">Location</span>
                                        <span style="color: #0f172a; font-weight: 700; text-align: right;">{{ $patient->location?->location_name ?: '-' }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 14px;">
                                        <span style="color: #64748b;">Open balance</span>
                                        <span style="color: #0f172a; font-weight: 700; text-align: right;">{{ $this->getOpenBalanceLabel() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                <div style="font-size: 18px; font-weight: 900; color: #0f172a;">Basic Information</div>
                                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 20px;">
                                    @foreach ([
                                        ['label' => 'First Name', 'value' => $patient->first_name ?: '-'],
                                        ['label' => 'Last Name', 'value' => $patient->last_name ?: '-'],
                                        ['label' => 'Primary Location', 'value' => $patient->location?->location_name ?: '-'],
                                        ['label' => 'Email', 'value' => $patient->email ?: '-'],
                                        ['label' => 'Contact Number', 'value' => $patient->phone ?: '-'],
                                        ['label' => 'DOB', 'value' => $patient->dob?->format('F d, Y') ?: '-'],
                                        ['label' => 'Status', 'value' => $statusLabel],
                                        ['label' => 'Gender', 'value' => $genderLabel],
                                    ] as $field)
                                        <div>
                                            <div style="margin-bottom: 6px; font-size: 12px; font-weight: 800; color: #0f172a;">{{ $field['label'] }}</div>
                                            <div style="min-height: 52px; border: 1px solid #dbe4ee; border-radius: 10px; background: #f8fafc; display: flex; align-items: center; padding: 0 14px; font-size: 14px; color: #475569;">
                                                {{ $field['value'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                <div style="font-size: 18px; font-weight: 900; color: #0f172a;">Other Information</div>
                                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 20px;">
                                    @foreach ([
                                        ['label' => 'Address', 'value' => $patient->address ?: '-'],
                                        ['label' => 'Insurance Provider', 'value' => $patient->insurance_provider ?: '-'],
                                        ['label' => 'Insurance Number', 'value' => $patient->insurance_number ?: '-'],
                                        ['label' => 'Guarantor', 'value' => $patient->guarantor_name ?: '-'],
                                    ] as $field)
                                        <div>
                                            <div style="margin-bottom: 6px; font-size: 12px; font-weight: 800; color: #0f172a;">{{ $field['label'] }}</div>
                                            <div style="min-height: 52px; border: 1px solid #dbe4ee; border-radius: 10px; background: #f8fafc; display: flex; align-items: center; padding: 0 14px; font-size: 14px; color: #475569;">
                                                {{ $field['value'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="reports" style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Reports</div>
                        <h3 style="margin: 8px 0 0; font-size: 24px; line-height: 1.15; font-weight: 900; color: #0f172a;">Financial Snapshot</h3>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px;">
                        <div style="padding: 18px; border: 1px solid #dbe4ee; border-radius: 14px; background: #f8fafc;">
                            <div style="font-size: 12px; font-weight: 800; color: #64748b;">Open Balance</div>
                            <div style="margin-top: 8px; font-size: 24px; font-weight: 900; color: #0f172a;">{{ $this->getOpenBalanceLabel() }}</div>
                        </div>
                        <div style="padding: 18px; border: 1px solid #dbe4ee; border-radius: 14px; background: #f8fafc;">
                            <div style="font-size: 12px; font-weight: 800; color: #64748b;">Insurance Policies</div>
                            <div style="margin-top: 8px; font-size: 24px; font-weight: 900; color: #0f172a;">{{ (int) ($patient->insurance_policies_count ?? 0) }}</div>
                        </div>
                        <div style="padding: 18px; border: 1px solid #dbe4ee; border-radius: 14px; background: #f8fafc;">
                            <div style="font-size: 12px; font-weight: 800; color: #64748b;">Created By</div>
                            <div style="margin-top: 8px; font-size: 18px; font-weight: 900; color: #0f172a;">{{ $patient->creator?->name ?: '-' }}</div>
                        </div>
                    </div>
                </div>

                <div id="appointments" style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Appointments</div>
                            <h3 style="margin: 8px 0 0; font-size: 24px; line-height: 1.15; font-weight: 900; color: #0f172a;">Recent Visits & Scheduling</h3>
                        </div>
                        <a href="{{ $appointmentIndexUrl }}" style="display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 16px; border-radius: 12px; border: 1px solid #cbd5e1; background: #ffffff; color: #1e293b; font-size: 14px; font-weight: 700; text-decoration: none;">
                            Open Appointments
                        </a>
                    </div>

                    <div style="border: 1px solid #e6ebf2; border-radius: 14px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; background: #ffffff;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 12px 14px; text-align: left; font-size: 12px; color: #64748b;">Date</th>
                                    <th style="padding: 12px 14px; text-align: left; font-size: 12px; color: #64748b;">Time</th>
                                    <th style="padding: 12px 14px; text-align: left; font-size: 12px; color: #64748b;">Doctor</th>
                                    <th style="padding: 12px 14px; text-align: left; font-size: 12px; color: #64748b;">Location</th>
                                    <th style="padding: 12px 14px; text-align: left; font-size: 12px; color: #64748b;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($appointments as $appointment)
                                    <tr>
                                        <td style="padding: 12px 14px; border-top: 1px solid #eef2f7; font-size: 14px; color: #0f172a;">{{ $appointment['date'] }}</td>
                                        <td style="padding: 12px 14px; border-top: 1px solid #eef2f7; font-size: 14px; color: #475569;">{{ $appointment['time'] ?: '-' }}</td>
                                        <td style="padding: 12px 14px; border-top: 1px solid #eef2f7; font-size: 14px; color: #475569;">{{ $appointment['provider'] }}</td>
                                        <td style="padding: 12px 14px; border-top: 1px solid #eef2f7; font-size: 14px; color: #475569;">{{ $appointment['location'] }}</td>
                                        <td style="padding: 12px 14px; border-top: 1px solid #eef2f7;">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; min-height: 30px; padding: 0 10px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 800;">
                                                {{ $appointment['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="padding: 18px 14px; border-top: 1px solid #eef2f7; font-size: 14px; color: #64748b;">No appointments recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="follow-up" style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #0f766e;">Treatment &amp; Follow-Up</div>
                            <h3 style="margin: 8px 0 0; font-size: 24px; line-height: 1.15; font-weight: 900; color: #0f172a;">Treatment Plans & Follow-Up</h3>
                        </div>
                        <a href="{{ $treatmentIndexUrl }}" style="display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 16px; border-radius: 12px; border: 1px solid #cbd5e1; background: #ffffff; color: #1e293b; font-size: 14px; font-weight: 700; text-decoration: none;">
                            Open Treatment Plans
                        </a>
                    </div>

                    <div style="display: grid; gap: 14px;">
                        @forelse ($plans as $plan)
                            <div style="padding: 18px; border: 1px solid #dbe4ee; border-radius: 14px; background: #ffffff; display: grid; grid-template-columns: minmax(0, 1.2fr) repeat(4, minmax(0, 0.8fr)); gap: 14px; align-items: center;">
                                <div>
                                    <div style="font-size: 15px; font-weight: 900; color: #0f172a;">{{ $plan['title'] }}</div>
                                    <div style="margin-top: 4px; font-size: 13px; color: #64748b;">{{ $plan['provider'] }}</div>
                                </div>
                                <div style="font-size: 13px; color: #475569;">{{ $plan['date'] }}</div>
                                <div style="font-size: 13px; color: #475569;">{{ $plan['status'] }}</div>
                                <div style="font-size: 13px; color: #475569;">{{ $plan['priority'] }}</div>
                                <div style="font-size: 13px; font-weight: 800; color: #0f172a;">{{ $plan['estimate'] }}</div>
                            </div>
                        @empty
                            <div style="padding: 18px; border: 1px dashed #dbe4ee; border-radius: 14px; background: #f8fafc; font-size: 14px; color: #64748b;">
                                No treatment plans or follow-up items have been added yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
