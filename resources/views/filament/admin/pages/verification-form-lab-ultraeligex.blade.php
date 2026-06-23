<x-filament-panels::page>
    <style>
        :root {
            --uel-brand: #0f766e;
            --uel-blue: #1d4ed8;
            --uel-amber: #d97706;
            --uel-bg: #f6f9fb;
            --uel-card: #ffffff;
            --uel-text: #0f172a;
            --uel-muted: #64748b;
            --uel-line: #dbe4ee;
            --uel-soft: #eef7f4;
            --uel-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
        }

        .uel-page {
            margin: -18px -6px 0;
            color: var(--uel-text);
        }

        .uel-topbar,
        .uel-card {
            border: 1px solid var(--uel-line);
            background: var(--uel-card);
            box-shadow: var(--uel-shadow);
        }

        .uel-topbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            border-radius: 22px;
            margin-bottom: 18px;
        }

        .uel-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            width: max-content;
            padding: 7px 12px;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: var(--uel-blue);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .uel-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .uel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 10px 14px;
            border: 1px solid var(--uel-line);
            border-radius: 13px;
            background: #ffffff;
            color: #334155;
            font-size: 13px;
            font-weight: 850;
            text-decoration: none;
            cursor: pointer;
        }

        .uel-btn-primary {
            border-color: #047857;
            background: linear-gradient(135deg, #059669, #0f766e);
            color: #ffffff;
        }

        .uel-btn-warning {
            border-color: #fed7aa;
            background: #fff7ed;
            color: #c2410c;
        }

        .uel-layout {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .uel-sidebar {
            position: sticky;
            top: 92px;
            display: grid;
            gap: 14px;
        }

        .uel-card {
            border-radius: 24px;
            overflow: hidden;
        }

        .uel-card-pad {
            padding: 20px;
        }

        .uel-progress {
            height: 9px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .uel-progress span {
            display: block;
            width: 38%;
            height: 100%;
            background: linear-gradient(90deg, #22c55e, #0f766e);
        }

        .uel-nav {
            display: grid;
            gap: 8px;
            margin-top: 14px;
        }

        .uel-nav a {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            background: #f8fafc;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
        }

        .uel-nav b {
            color: var(--uel-brand);
        }

        .uel-hero {
            padding: 24px 26px;
            background:
                radial-gradient(circle at 5% 5%, rgba(14, 165, 233, .14), transparent 30%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        }

        .uel-hero h1 {
            margin: 14px 0 8px;
            font-size: 34px;
            line-height: 1.08;
            font-weight: 950;
            letter-spacing: -0.04em;
        }

        .uel-muted {
            color: var(--uel-muted);
            line-height: 1.65;
        }

        .uel-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 16px 20px;
            border-bottom: 1px solid #edf2f7;
            background: #fbfdff;
        }

        .uel-section-head h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 950;
            letter-spacing: -0.02em;
        }

        .uel-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .uel-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .uel-field label,
        .uel-table th {
            display: block;
            margin-bottom: 7px;
            color: var(--uel-muted);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .uel-field input,
        .uel-field select,
        .uel-field textarea {
            width: 100%;
            min-height: 42px;
            border: 1px solid #d6dde8;
            border-radius: 12px;
            padding: 10px 12px;
            background: #ffffff;
            color: var(--uel-text);
            font-size: 13px;
        }

        .uel-field textarea {
            min-height: 92px;
            resize: vertical;
        }

        .uel-readonly {
            min-height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 11px 12px;
            background: #f8fafc;
            font-size: 13px;
            font-weight: 850;
        }

        .uel-wide {
            grid-column: 1 / -1;
        }

        .uel-main {
            display: grid;
            gap: 18px;
        }

        .uel-table-wrap {
            overflow: auto;
        }

        .uel-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 780px;
        }

        .uel-table th,
        .uel-table td {
            border-bottom: 1px solid #edf2f7;
            padding: 12px 14px;
            text-align: left;
            vertical-align: top;
        }

        .uel-table th {
            background: #f8fafc;
        }

        .uel-table td {
            font-size: 13px;
        }

        .uel-mini-input {
            width: 100%;
            min-width: 110px;
            border: 1px solid #d6dde8;
            border-radius: 10px;
            padding: 8px 10px;
            background: #fff;
        }

        .uel-tabs {
            display: inline-flex;
            gap: 8px;
            padding: 5px;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: #eff6ff;
        }

        .uel-tab {
            border: 0;
            border-radius: 999px;
            padding: 8px 13px;
            background: transparent;
            color: #1d4ed8;
            font-weight: 900;
            cursor: pointer;
        }

        .uel-tab.is-active {
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .16);
        }

        .uel-benefit-tab {
            display: none;
        }

        .uel-benefit-tab.is-active {
            display: block;
        }

        .uel-conditional {
            display: none;
            margin-top: 14px;
            border: 1px dashed #f59e0b;
            border-radius: 18px;
            background: #fffbeb;
            padding: 16px;
        }

        .uel-conditional.is-open {
            display: block;
        }

        @media (max-width: 1200px) {
            .uel-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .uel-sidebar {
                position: static;
            }
        }

        @media (max-width: 760px) {
            .uel-topbar,
            .uel-section-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .uel-grid,
            .uel-grid-3 {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="uel-page">
        <div class="uel-topbar">
            <div>
                <div style="font-size: 13px; font-weight: 950;">Verification Details</div>
                <div class="uel-muted" style="font-size: 12px;">Synced changes • Form design lab</div>
            </div>
            <div class="uel-actions">
                <a class="uel-btn" href="{{ url('/verification/verifications') }}">Back to List</a>
                <button class="uel-btn">Save Draft</button>
                <button class="uel-btn">Download PDF</button>
                <button class="uel-btn uel-btn-warning">Unable to Verify</button>
                <button class="uel-btn uel-btn-primary">Send to Auditor</button>
            </div>
        </div>

        <div class="uel-layout">
            <aside class="uel-sidebar">
                <section class="uel-card">
                    <div class="uel-card-pad">
                        <div style="display: flex; justify-content: space-between; gap: 12px; align-items: center;">
                            <strong style="font-size: 13px;">Form Completion</strong>
                            <span style="font-size: 12px; color: var(--uel-muted);">0/119</span>
                        </div>
                        <div class="uel-progress" style="margin-top: 12px;"><span></span></div>
                        <nav class="uel-nav">
                            <a href="#uel-clinic">Clinic Information <b>0/9</b></a>
                            <a href="#uel-patient">Patient Information <b>0/8</b></a>
                            <a href="#uel-service">Appointment / Service <b>0/5</b></a>
                            <a href="#uel-benefits">Maximums & Deductibles <b>0/10</b></a>
                            <a href="#uel-questions">Questions <b>0/18</b></a>
                            <a href="#uel-history">Service History <b>0/8</b></a>
                            <a href="#uel-codes">Codes <b>0/29</b></a>
                            <a href="#uel-system">Verification Information <b>System</b></a>
                        </nav>
                    </div>
                </section>

                <section class="uel-card">
                    <div class="uel-card-pad">
                        <span class="uel-pill">Quick Reference</span>
                        <div style="display: grid; gap: 12px; margin-top: 14px;">
                            <div>
                                <div class="uel-muted" style="font-size: 11px; font-weight: 950; letter-spacing: .1em; text-transform: uppercase;">Patient</div>
                                <strong>Demo Patient</strong>
                            </div>
                            <div class="uel-grid">
                                <div>
                                    <div class="uel-muted" style="font-size: 11px; font-weight: 950;">DOB</div>
                                    <strong>-</strong>
                                </div>
                                <div>
                                    <div class="uel-muted" style="font-size: 11px; font-weight: 950;">Member ID</div>
                                    <strong>-</strong>
                                </div>
                            </div>
                            <div>
                                <div class="uel-muted" style="font-size: 11px; font-weight: 950;">Clinic</div>
                                <strong>Selected Clinic</strong>
                            </div>
                        </div>
                    </div>
                </section>
            </aside>

            <main class="uel-main">
                <section class="uel-card uel-hero">
                    <span class="uel-pill">Verification Form Lab</span>
                    <h1>Modern Insurance Verification Form</h1>
                    <p class="uel-muted" style="max-width: 980px;">
                        This page is a safe testing version inspired by your shared HTML. It keeps the current verification form untouched while we build the cleaner structure step by step.
                    </p>
                </section>

                <section class="uel-card" id="uel-clinic">
                    <div class="uel-section-head">
                        <div>
                            <h2>Clinic Information</h2>
                            <div class="uel-muted">Clinic, provider, location, and insurance participation details.</div>
                        </div>
                    </div>
                    <div class="uel-card-pad uel-grid">
                        <div class="uel-field"><label>Clinic Name</label><input value="Meditya Global Services LLC"></div>
                        <div class="uel-field"><label>Provider / Doctor</label><input placeholder="Provider name"></div>
                        <div class="uel-field"><label>Practice NPI</label><input placeholder="Practice NPI"></div>
                        <div class="uel-field"><label>Provider NPI</label><input placeholder="Provider NPI"></div>
                        <div class="uel-field"><label>Insurance Provider</label><input placeholder="Insurance provider"></div>
                        <div class="uel-field"><label>Insurance Phone</label><input placeholder="Phone number"></div>
                        <div class="uel-field"><label>Provider Participating?</label><select><option>Select</option><option>Yes</option><option>No</option><option>Unknown</option></select></div>
                        <div class="uel-field"><label>Fee Schedule</label><input placeholder="Fee schedule"></div>
                        <div class="uel-field uel-wide"><label>Claim Mailing Address</label><textarea placeholder="Claim mailing address"></textarea></div>
                    </div>
                </section>

                <section class="uel-card" id="uel-patient">
                    <div class="uel-section-head">
                        <div>
                            <h2>Patient Information</h2>
                            <div class="uel-muted">Patient, subscriber, member, and COB information.</div>
                        </div>
                    </div>
                    <div class="uel-card-pad uel-grid">
                        <div class="uel-field"><label>Patient Name</label><input placeholder="Patient full name"></div>
                        <div class="uel-field"><label>Date of Birth</label><input type="date"></div>
                        <div class="uel-field"><label>Member ID</label><input placeholder="Member ID"></div>
                        <div class="uel-field"><label>Subscriber Name</label><input placeholder="Subscriber name"></div>
                        <div class="uel-field"><label>Subscriber DOB</label><input type="date"></div>
                        <div class="uel-field"><label>Subscriber ID</label><input placeholder="Subscriber ID"></div>
                        <div class="uel-field"><label>Insured Relation</label><select><option>Select</option><option>Self</option><option>Spouse</option><option>Dependent</option></select></div>
                        <div class="uel-field"><label>COB</label><select><option>Select</option><option>No COB</option><option>Primary</option><option>Secondary</option></select></div>
                    </div>
                </section>

                <section class="uel-card" id="uel-service">
                    <div class="uel-section-head">
                        <div>
                            <h2>Appointment / Date of Service</h2>
                            <div class="uel-muted">Tie the eligibility check to the scheduled visit.</div>
                        </div>
                    </div>
                    <div class="uel-card-pad uel-grid">
                        <div class="uel-field"><label>Appointment Date</label><input type="date"></div>
                        <div class="uel-field"><label>Appointment Time</label><input type="time"></div>
                        <div class="uel-field"><label>Service Coming For</label><input placeholder="Example: Implant consult, crown, cleaning"></div>
                        <div class="uel-field"><label>Priority</label><select><option>Normal</option><option>Urgent</option></select></div>
                        <div class="uel-field uel-wide"><label>Appointment Notes</label><textarea placeholder="Notes from clinic or patient"></textarea></div>
                    </div>
                </section>

                <section class="uel-card" id="uel-benefits">
                    <div class="uel-section-head">
                        <div>
                            <h2>Maximums & Deductibles</h2>
                            <div class="uel-muted">Individual and family benefit values in one clean location.</div>
                        </div>
                        <div class="uel-tabs">
                            <button type="button" class="uel-tab is-active" onclick="showUelBenefitTab('individual', this)">Individual</button>
                            <button type="button" class="uel-tab" onclick="showUelBenefitTab('family', this)">Family</button>
                        </div>
                    </div>
                    <div class="uel-card-pad">
                        <div id="uel-tab-individual" class="uel-benefit-tab is-active">
                            <div class="uel-grid uel-grid-3">
                                <div class="uel-field"><label>Annual Maximum</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Maximum Used</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Remaining Maximum</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Annual Deductible</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Deductible Met</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Preventive Deductible</label><input placeholder="$"></div>
                            </div>
                        </div>
                        <div id="uel-tab-family" class="uel-benefit-tab">
                            <div class="uel-grid uel-grid-3">
                                <div class="uel-field"><label>Family Maximum</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Family Max Used</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Family Deductible</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Family Deductible Met</label><input placeholder="$"></div>
                                <div class="uel-field"><label>Diagnostic Deductible</label><input placeholder="$"></div>
                                <div class="uel-field"><label>X-Ray Deductible</label><input placeholder="$"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="uel-card" id="uel-questions">
                    <div class="uel-section-head">
                        <div>
                            <h2>Questions</h2>
                            <div class="uel-muted">Core plan questions with conditional expansion when needed.</div>
                        </div>
                    </div>
                    <div class="uel-card-pad">
                        <div class="uel-table-wrap">
                            <table class="uel-table">
                                <thead>
                                    <tr>
                                        <th>Question</th>
                                        <th>Answer</th>
                                        <th>Source / Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Are there any waiting periods?</td>
                                        <td>
                                            <select class="uel-mini-input" onchange="toggleUelWaitingPeriods(this.value)">
                                                <option>No</option>
                                                <option>Yes</option>
                                            </select>
                                        </td>
                                        <td>Expands additional waiting-period questions when Yes.</td>
                                    </tr>
                                    <tr><td>Missing tooth clause?</td><td><select class="uel-mini-input"><option>No</option><option>Yes</option></select></td><td></td></tr>
                                    <tr><td>Do all exams share frequency?</td><td><select class="uel-mini-input"><option>No</option><option>Yes</option></select></td><td></td></tr>
                                    <tr><td>Which fee schedule shall we use?</td><td><input class="uel-mini-input" placeholder="Fee schedule"></td><td></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="uel-waiting-periods" class="uel-conditional">
                            <strong>Waiting Period Details</strong>
                            <div class="uel-grid uel-grid-3" style="margin-top: 14px;">
                                <div class="uel-field"><label>Restorative Waiting Months</label><input type="number" min="0"></div>
                                <div class="uel-field"><label>Endo Waiting Months</label><input type="number" min="0"></div>
                                <div class="uel-field"><label>Perio Waiting Months</label><input type="number" min="0"></div>
                                <div class="uel-field"><label>Oral Surgery Months</label><input type="number" min="0"></div>
                                <div class="uel-field"><label>Implant Months</label><input type="number" min="0"></div>
                                <div class="uel-field"><label>Ortho Months</label><input type="number" min="0"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="uel-card" id="uel-history">
                    <div class="uel-section-head">
                        <div>
                            <h2>Service History</h2>
                            <div class="uel-muted">Previously completed services and dates.</div>
                        </div>
                    </div>
                    <div class="uel-table-wrap">
                        <table class="uel-table">
                            <thead><tr><th>Code</th><th>Service</th><th>Service Dates / Notes</th><th>Verified</th></tr></thead>
                            <tbody>
                                @foreach ([
                                    ['D0274', 'Four Bitewing X-rays'],
                                    ['D0330', 'Panoramic X-ray'],
                                    ['D0210', 'Full Mouth X-rays'],
                                    ['D1110', 'Adult Cleaning / Prophy'],
                                ] as [$code, $service])
                                    <tr>
                                        <td><strong>{{ $code }}</strong></td>
                                        <td>{{ $service }}</td>
                                        <td><input class="uel-mini-input" placeholder="No history / date"></td>
                                        <td><input type="checkbox"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="uel-card" id="uel-codes">
                    <div class="uel-section-head">
                        <div>
                            <h2>Codes</h2>
                            <div class="uel-muted">ADA/CPT style benefit matrix with percentage, frequency, and additional rules.</div>
                        </div>
                    </div>
                    <div class="uel-table-wrap">
                        <table class="uel-table">
                            <thead><tr><th>Code</th><th>Category</th><th>%</th><th>Frequency</th><th>Additional Info</th></tr></thead>
                            <tbody>
                                @foreach ([
                                    ['D0120', 'Diagnostic', '100%', '2 / 12 MTS'],
                                    ['D1110', 'Preventive', '100%', '2 / 12 MTS'],
                                    ['D2391', 'Restorative', '80%', ''],
                                    ['D2740', 'Major', '50%', '1 / 5 YRS'],
                                    ['D6010', 'Implants', '50%', '1 / LT'],
                                    ['D8090', 'Orthodontics', '0%', ''],
                                ] as [$code, $category, $percent, $freq])
                                    <tr>
                                        <td><strong>{{ $code }}</strong></td>
                                        <td>{{ $category }}</td>
                                        <td><input class="uel-mini-input" value="{{ $percent }}"></td>
                                        <td><input class="uel-mini-input" value="{{ $freq }}"></td>
                                        <td><input class="uel-mini-input" placeholder="Guidelines, downgrades, age limits"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="uel-card" id="uel-system">
                    <div class="uel-section-head">
                        <div>
                            <h2>Verification Information</h2>
                            <div class="uel-muted">System-generated verification details. User comment remains editable.</div>
                        </div>
                    </div>
                    <div class="uel-card-pad uel-grid">
                        <div class="uel-field"><label>Reference Number</label><div class="uel-readonly">Auto generated</div></div>
                        <div class="uel-field"><label>Status</label><div class="uel-readonly">System generated</div></div>
                        <div class="uel-field"><label>Verified By</label><div class="uel-readonly">Current user</div></div>
                        <div class="uel-field"><label>Verified Date</label><div class="uel-readonly">Current date</div></div>
                        <div class="uel-field uel-wide"><label>User Note</label><textarea placeholder="Add comments for auditor or internal verification team"></textarea></div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        function toggleUelWaitingPeriods(value) {
            const panel = document.getElementById('uel-waiting-periods');
            if (!panel) return;
            panel.classList.toggle('is-open', value === 'Yes');
        }

        function showUelBenefitTab(tab, button) {
            document.querySelectorAll('.uel-benefit-tab').forEach((panel) => panel.classList.remove('is-active'));
            document.querySelectorAll('.uel-tab').forEach((item) => item.classList.remove('is-active'));

            const panel = document.getElementById(`uel-tab-${tab}`);
            if (panel) panel.classList.add('is-active');
            if (button) button.classList.add('is-active');
        }
    </script>
</x-filament-panels::page>
