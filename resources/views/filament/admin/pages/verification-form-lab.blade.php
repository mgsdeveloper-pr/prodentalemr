@include('filament.admin.pages.verification-form-lab-ultraeligex-modern')
{{--
<x-filament-panels::page>
    <style>
        .verification-form-lab {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }

        .verification-lab-card {
            border: 1px solid #dbe4ee;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .verification-lab-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .verification-lab-field--wide {
            grid-column: 1 / -1;
        }

        .verification-lab-label {
            display: block;
            margin-bottom: 7px;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: #64748b;
        }

        .verification-lab-input {
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            border: 1px solid #d6dde8;
            border-radius: 12px;
            background: #ffffff;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.45;
        }

        .verification-lab-readonly {
            min-height: 43px;
            padding: 11px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.45;
        }

        @media (max-width: 1100px) {
            .verification-form-lab {
                grid-template-columns: minmax(0, 1fr);
            }

            .verification-lab-field-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 22px;">
        <section class="verification-lab-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);">
            <div style="padding: 24px 28px; display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; flex-wrap: wrap;">
                <div>
                    <span style="display: inline-flex; width: max-content; padding: 7px 12px; border-radius: 999px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase;">
                        Testing Page
                    </span>
                    <h1 style="margin: 14px 0 0; font-size: 34px; line-height: 1.08; font-weight: 900; color: #0f172a;">
                        Verification Form Lab
                    </h1>
                    <p style="margin: 10px 0 0; max-width: 880px; font-size: 15px; line-height: 1.75; color: #64748b;">
                        This is a safe sandbox for designing the next verification form. The current production verification form is untouched.
                    </p>
                </div>

                <a href="{{ url('/verification/verifications') }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 154px; padding: 11px 16px; border-radius: 14px; border: 1px solid #dbe4ee; background: #ffffff; color: #334155; font-size: 13px; font-weight: 800; text-decoration: none;">
                    Back to List
                </a>
            </div>
        </section>

        <div class="verification-form-lab">
            <aside class="verification-lab-card">
                <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 12px; font-weight: 900; letter-spacing: 0.13em; text-transform: uppercase; color: #0f766e;">Form Map</div>
                    <p style="margin: 8px 0 0; font-size: 13px; line-height: 1.65; color: #64748b;">We will tune these sections together before connecting final save logic.</p>
                </div>
                <div style="padding: 16px; display: grid; gap: 10px;">
                    @foreach ([
                        ['Clinic Information', 'Clinic, provider, network participation'],
                        ['Patient Information', 'Patient, subscriber, member details'],
                        ['Appointment / Service', 'Date, time, service being verified'],
                        ['Coverage Details', 'Benefits, deductibles, coverage'],
                        ['Codes & Frequency', 'ADA/CPT matrix and conditions'],
                        ['System Verification', 'Generated reference, status, notes'],
                    ] as [$title, $subtitle])
                        <div style="padding: 12px 14px; border-radius: 16px; border: 1px solid #e2e8f0; background: #f8fafc;">
                            <div style="font-size: 13px; font-weight: 900; color: #0f172a;">{{ $title }}</div>
                            <div style="margin-top: 3px; font-size: 12px; line-height: 1.5; color: #64748b;">{{ $subtitle }}</div>
                        </div>
                    @endforeach
                </div>
            </aside>

            <main style="display: flex; flex-direction: column; gap: 18px;">
                @foreach ([
                    [
                        'title' => 'Clinic Information',
                        'description' => 'Collect clinic context and insurance participation details.',
                        'accent' => '#0f766e',
                        'fields' => [
                            ['Clinic Name', 'Meditya Global Services LLC', 'readonly'],
                            ['Provider / Doctor', ''],
                            ['Practice NPI', ''],
                            ['Provider NPI', ''],
                            ['Insurance Provider', ''],
                            ['Insurance Phone', ''],
                            ['Provider Participating?', '', 'select'],
                            ['Fee Schedule', ''],
                            ['Claim Mailing Address', '', 'textarea'],
                        ],
                    ],
                    [
                        'title' => 'Patient Information',
                        'description' => 'Patient and subscriber details needed before calling or checking portal benefits.',
                        'accent' => '#2563eb',
                        'fields' => [
                            ['Patient Name', ''],
                            ['Date of Birth', '', 'date'],
                            ['Member ID', ''],
                            ['Subscriber Name', ''],
                            ['Subscriber DOB', '', 'date'],
                            ['Subscriber ID', ''],
                            ['Relationship', ''],
                            ['COB', '', 'select'],
                        ],
                    ],
                    [
                        'title' => 'Appointment / Date of Service',
                        'description' => 'Tie the verification to the appointment and service requested.',
                        'accent' => '#f59e0b',
                        'fields' => [
                            ['Appointment Date', '', 'date'],
                            ['Appointment Time', ''],
                            ['Service / Procedure', ''],
                            ['Priority', '', 'select'],
                            ['Appointment Notes', '', 'textarea'],
                        ],
                    ],
                    [
                        'title' => 'System Verification Information',
                        'description' => 'Generated by the system. Only the user comment will remain open.',
                        'accent' => '#64748b',
                        'fields' => [
                            ['Reference Number', 'Auto generated', 'readonly'],
                            ['Verification Status', 'Auto generated', 'readonly'],
                            ['Verified By', 'Current user', 'readonly'],
                            ['Verified Date', 'Current date', 'readonly'],
                            ['User Comment / Notes', '', 'textarea'],
                        ],
                    ],
                ] as $section)
                    <section class="verification-lab-card">
                        <div style="padding: 18px 20px; border-bottom: 1px solid #edf2f7; display: flex; gap: 12px; align-items: flex-start;">
                            <span style="margin-top: 4px; width: 11px; height: 11px; border-radius: 999px; background: {{ $section['accent'] }};"></span>
                            <div>
                                <h2 style="margin: 0; font-size: 22px; line-height: 1.2; font-weight: 900; color: #0f172a;">{{ $section['title'] }}</h2>
                                <p style="margin: 6px 0 0; font-size: 13px; line-height: 1.65; color: #64748b;">{{ $section['description'] }}</p>
                            </div>
                        </div>

                        <div style="padding: 20px;" class="verification-lab-field-grid">
                            @foreach ($section['fields'] as $field)
                                @php
                                    $label = $field[0];
                                    $value = $field[1] ?? '';
                                    $type = $field[2] ?? 'text';
                                    $wide = $type === 'textarea';
                                @endphp
                                <div class="{{ $wide ? 'verification-lab-field--wide' : '' }}">
                                    <label class="verification-lab-label">{{ $label }}</label>
                                    @if ($type === 'readonly')
                                        <div class="verification-lab-readonly">{{ $value ?: '-' }}</div>
                                    @elseif ($type === 'textarea')
                                        <textarea class="verification-lab-input" style="min-height: 96px; resize: vertical;" placeholder="{{ $label }}"></textarea>
                                    @elseif ($type === 'select')
                                        <select class="verification-lab-input">
                                            <option value="">Select</option>
                                            <option>Yes</option>
                                            <option>No</option>
                                            <option>Unknown</option>
                                        </select>
                                    @elseif ($type === 'date')
                                        <input class="verification-lab-input" type="date">
                                    @else
                                        <input class="verification-lab-input" type="text" value="{{ $value }}" placeholder="{{ $label }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </main>
        </div>
    </div>
</x-filament-panels::page>
--}}
