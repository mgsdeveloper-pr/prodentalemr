<x-filament-panels::page>
    <style>
        :root {
            --uel-brand: #0b6b4f;
            --uel-brand-dark: #063f30;
            --uel-brand-soft: #eaf6f1;
            --uel-bg: #f3f7f5;
            --uel-card: #ffffff;
            --uel-text: #142e25;
            --uel-muted: #6d7d77;
            --uel-line: #dce8e3;
            --uel-danger: #d92d20;
            --uel-danger-soft: #fff1f0;
            --uel-warning: #f79009;
            --uel-warning-soft: #fff7e6;
            --uel-shadow: 0 18px 45px rgba(13, 58, 41, .10);
        }

        .uel-page {
            margin: -20px -8px 0;
            background: linear-gradient(180deg, #f8fbfa 0%, var(--uel-bg) 100%);
            color: var(--uel-text);
            font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .uel-page * {
            box-sizing: border-box;
        }

        .uel-topbar {
            height: 68px;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(12px);
            border: 1px solid var(--uel-line);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 30;
            box-shadow: 0 12px 34px rgba(13, 58, 41, .07);
        }

        .uel-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 900;
            color: var(--uel-brand-dark);
            font-size: 20px;
        }

        .uel-logo-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--uel-brand-dark), var(--uel-brand));
            color: #fff;
        }

        .uel-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .uel-button {
            border: 0;
            border-radius: 12px;
            padding: 10px 16px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            font-size: 13px;
        }

        .uel-button-primary {
            background: var(--uel-brand);
            color: #fff;
            box-shadow: 0 8px 20px rgba(11, 107, 79, .22);
        }

        .uel-button-light {
            background: #eef6f2;
            color: var(--uel-brand);
            border: 1px solid var(--uel-line);
        }

        .uel-button-danger {
            background: var(--uel-danger-soft);
            color: var(--uel-danger);
        }

        .uel-layout {
            max-width: 1440px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 20px;
        }

        .uel-sidebar {
            background: var(--uel-card);
            border: 1px solid var(--uel-line);
            border-radius: 22px;
            box-shadow: var(--uel-shadow);
            padding: 18px;
            height: calc(100vh - 108px);
            position: sticky;
            top: 88px;
            overflow: auto;
        }

        .uel-sidebar-title {
            font-weight: 900;
            margin-bottom: 12px;
        }

        .uel-progress-bar {
            height: 10px;
            background: #e3ece8;
            border-radius: 40px;
            overflow: hidden;
            margin-bottom: 18px;
        }

        .uel-progress-bar span {
            display: block;
            height: 100%;
            width: 78%;
            background: linear-gradient(90deg, var(--uel-brand), #2fab80);
        }

        .uel-nav a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #263d35;
            text-decoration: none;
            padding: 11px 10px;
            border-radius: 12px;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .uel-nav a:hover {
            background: var(--uel-brand-soft);
        }

        .uel-badge {
            font-size: 12px;
            background: var(--uel-brand-soft);
            color: var(--uel-brand);
            padding: 4px 8px;
            border-radius: 999px;
            font-weight: 900;
        }

        .uel-quick-card {
            margin-top: 18px;
            padding: 14px;
            border: 1px solid var(--uel-line);
            border-radius: 16px;
            background: #fbfdfc;
        }

        .uel-quick-card h4 {
            margin: 0 0 12px;
            color: var(--uel-brand);
        }

        .uel-quick-row {
            font-size: 13px;
            margin: 8px 0;
            color: var(--uel-muted);
        }

        .uel-quick-row b {
            display: block;
            color: var(--uel-text);
            margin-top: 2px;
        }

        .uel-main {
            min-width: 0;
        }

        .uel-section {
            background: var(--uel-card);
            border: 1px solid var(--uel-line);
            border-radius: 22px;
            box-shadow: var(--uel-shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .uel-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 18px 22px;
            border-bottom: 1px solid var(--uel-line);
            background: linear-gradient(180deg, #fff, #f8fbfa);
        }

        .uel-section-header h2 {
            margin: 0;
            font-size: 19px;
            color: var(--uel-brand-dark);
            font-weight: 900;
        }

        .uel-section-subtitle {
            margin: 4px 0 0;
            color: var(--uel-muted);
            font-size: 13px;
        }

        .uel-status-pill {
            background: var(--uel-brand-soft);
            color: var(--uel-brand);
            padding: 7px 11px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 12px;
            white-space: nowrap;
        }

        .uel-section-body {
            padding: 20px;
        }

        .uel-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
        }

        .uel-field label {
            font-size: 11px;
            font-weight: 900;
            color: var(--uel-muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            display: block;
            margin-bottom: 7px;
        }

        .uel-field input,
        .uel-field select,
        .uel-field textarea,
        .uel-modern-table input,
        .uel-modern-table select,
        .uel-mini-table input,
        .uel-mini-table select {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--uel-line);
            border-radius: 12px;
            background: #fff;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            color: var(--uel-text);
        }

        .uel-field input:focus,
        .uel-field select:focus,
        .uel-field textarea:focus,
        .uel-modern-table input:focus,
        .uel-modern-table select:focus,
        .uel-mini-table input:focus,
        .uel-mini-table select:focus {
            border-color: var(--uel-brand);
            box-shadow: 0 0 0 4px rgba(11, 107, 79, .10);
        }

        .uel-field textarea {
            min-height: 96px;
            resize: vertical;
        }

        .uel-full {
            grid-column: 1 / -1;
        }

        .uel-half {
            grid-column: span 2;
        }

        .uel-notice {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            background: var(--uel-warning-soft);
            border: 1px solid #ffdca8;
            color: #7a4a00;
            padding: 13px 14px;
            border-radius: 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .uel-table-wrap {
            overflow: visible;
            width: 100%;
        }

        .uel-modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 0;
            table-layout: fixed;
            overflow: hidden;
            border: 1px solid var(--uel-line);
            border-radius: 16px;
        }

        .uel-modern-table th {
            background: #f6faf8;
            color: #50655d;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .07em;
            padding: 13px 14px;
            border-bottom: 1px solid var(--uel-line);
            white-space: nowrap;
        }

        .uel-modern-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #edf3f0;
            vertical-align: middle;
            font-size: 14px;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        .uel-modern-table tr:last-child td {
            border-bottom: 0;
        }

        .uel-modern-table tbody tr:hover td {
            background: #fbfdfc;
        }

        .uel-modern-table input,
        .uel-modern-table select {
            min-height: 36px;
            border-radius: 10px;
            padding: 8px 10px;
        }

        .uel-table-section td {
            background: var(--uel-brand-soft) !important;
            color: var(--uel-brand-dark);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .uel-inline-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 12px;
            font-weight: 900;
            background: var(--uel-brand-soft);
            color: var(--uel-brand);
        }

        .uel-validation {
            color: #5c6f68;
            font-size: 13px;
        }

        .uel-footer-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 18px 20px;
            border-top: 1px solid var(--uel-line);
            background: #fbfdfc;
        }

        .uel-conditional-box {
            display: none;
            margin: 14px 0 8px;
            padding: 16px;
            border: 1px solid #bfe3d5;
            background: #f7fcfa;
            border-radius: 16px;
        }

        .uel-conditional-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-weight: 900;
            color: var(--uel-brand-dark);
        }

        .uel-mini-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--uel-line);
            border-radius: 14px;
            overflow: hidden;
        }

        .uel-mini-table th {
            background: #eef7f3;
            color: #426157;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 11px;
        }

        .uel-mini-table td {
            padding: 9px 11px;
            border-top: 1px solid #edf3f0;
        }

        .uel-months-input {
            max-width: 140px;
        }

        .uel-subsection {
            border: 1px solid var(--uel-line);
            border-radius: 18px;
            background: #fbfdfc;
            padding: 16px;
            margin-top: 16px;
        }

        .uel-subsection-title {
            margin: 0 0 14px;
            color: var(--uel-brand-dark);
            font-size: 15px;
            font-weight: 950;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        @media (max-width: 1100px) {
            .uel-layout {
                grid-template-columns: 1fr;
            }

            .uel-sidebar {
                position: relative;
                top: auto;
                height: auto;
            }

            .uel-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .uel-actions {
                display: none;
            }

            .uel-grid {
                grid-template-columns: 1fr;
            }

            .uel-half {
                grid-column: 1 / -1;
            }

            .uel-modern-table,
            .uel-mini-table,
            .uel-modern-table thead,
            .uel-mini-table thead,
            .uel-modern-table tbody,
            .uel-mini-table tbody,
            .uel-modern-table tr,
            .uel-mini-table tr,
            .uel-modern-table th,
            .uel-mini-table th,
            .uel-modern-table td {
                display: block;
                width: 100%;
            }

            .uel-mini-table td {
                display: block;
                width: 100%;
            }

            .uel-modern-table thead,
            .uel-mini-table thead {
                display: none;
            }

            .uel-modern-table tr,
            .uel-mini-table tr {
                border-bottom: 1px solid var(--uel-line);
                padding: 10px;
            }

            .uel-modern-table td,
            .uel-mini-table td {
                border-bottom: 0;
                padding: 8px 0;
            }

            .uel-modern-table td::before,
            .uel-mini-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 5px;
                color: var(--uel-muted);
                font-size: 10px;
                font-weight: 900;
                letter-spacing: .07em;
                text-transform: uppercase;
            }
        }
    </style>

    <div class="uel-page">
        <div class="uel-layout">
            <main class="uel-main">
                <section class="uel-section" id="uel-patient">
                    <div class="uel-section-header">
                        <div><h2>Patient & Subscriber Information</h2><p class="uel-section-subtitle">Core eligibility identifiers</p></div>
                        <span class="uel-status-pill">Complete</span>
                    </div>
                    <div class="uel-section-body uel-grid">
                        <div class="uel-field"><label>Patient Name</label><input value="Kooper Schiltz"></div>
                        <div class="uel-field"><label>Date of Birth</label><input type="date" value="2010-09-29"></div>
                        <div class="uel-field"><label>Member ID</label><input value="7052146660"></div>
                        <div class="uel-field"><label>Relationship</label><select><option>Dependent</option><option>Self</option><option>Spouse</option></select></div>
                        <div class="uel-field"><label>Subscriber Name</label><input value="Danny Schiltz"></div>
                        <div class="uel-field"><label>Subscriber DOB</label><input type="date" value="1977-08-12"></div>
                        <div class="uel-field"><label>Subscriber ID</label><input value="7052146660"></div>
                        <div class="uel-field"><label>COB</label><select><option>No COB</option><option>Primary</option><option>Secondary</option></select></div>
                    </div>
                </section>

                <section class="uel-section" id="uel-insurance">
                    <div class="uel-section-header">
                        <div><h2>Insurance Information</h2><p class="uel-section-subtitle">Carrier, plan, network and payer details</p></div>
                        <span class="uel-status-pill">Verified</span>
                    </div>
                    <div class="uel-section-body uel-grid">
                        <div class="uel-field"><label>Insurance Provider</label><input value="Colonial Life Dental Plan"></div>
                        <div class="uel-field"><label>Plan Type</label><select><option>PPO</option><option>DHMO</option><option>Indemnity</option></select></div>
                        <div class="uel-field"><label>Payer ID</label><input value="STR01"></div>
                        <div class="uel-field"><label>Effective Date</label><input type="date" value="2024-08-01"></div>
                        <div class="uel-field uel-half"><label>Claims Address</label><input value="P.O BOX 80139, Baton Rouge, LA 70898"></div>
                        <div class="uel-field"><label>Phone Number</label><input value="+1 888-400-9304"></div>
                        <div class="uel-field"><label>Network Status</label><select><option>In Network</option><option>Out of Network</option></select></div>
                        <div class="uel-field"><label>Fee Schedule</label><input value="Connection Dental"></div>
                        <div class="uel-field"><label>Plan Renewal Month</label><input inputmode="numeric" placeholder="MM/YYYY" pattern="^(0[1-9]|1[0-2])\/[0-9]{4}$"></div>
                        <div class="uel-field"><label>Future Termination Date</label><input type="date"></div>
                        <div class="uel-field"><label>Employer / Group Name</label><input placeholder="Employer or group name"></div>
                        <div class="uel-field"><label>Group Number</label><input placeholder="Group number"></div>
                    </div>
                </section>

                <section class="uel-section" id="uel-maximums">
                    <div class="uel-section-header">
                        <div><h2>Maximums & Deductibles</h2><p class="uel-section-subtitle">Annual max, remaining max, and deductible status</p></div>
                        <span class="uel-status-pill">Individual / Family</span>
                    </div>
                    <div class="uel-section-body">
                        <div class="uel-grid" style="margin-bottom: 18px;">
                            <div class="uel-field"><label>Annual Maximum on the Plan?</label><input value="$2000"></div>
                            <div class="uel-field"><label>Annual Maximum Used?</label><input value="$280"></div>
                            <div class="uel-field"><label>Annual Maximum Remaining?</label><input value="$1720"></div>
                        </div>

                        <div class="uel-subsection">
                            <h3 class="uel-subsection-title">Individual Deductible</h3>
                            <div class="uel-grid">
                                <div class="uel-field"><label>Annual Deductible - Individual</label><input value="$50"></div>
                                <div class="uel-field"><label>Deductible Met - Individual</label><input value="$50"></div>
                                <div class="uel-field"><label>Individual Deductible Remaining</label><input value="$0"></div>
                            </div>
                        </div>

                        <div class="uel-subsection">
                            <h3 class="uel-subsection-title">Family Deductible</h3>
                            <div class="uel-grid">
                                <div class="uel-field"><label>Annual Deductible - Family</label><input value="$150"></div>
                                <div class="uel-field"><label>Deductible Met - Family</label><input placeholder="$0"></div>
                                <div class="uel-field"><label>Family Deductible Remaining</label><input placeholder="$0"></div>
                            </div>
                        </div>

                        <div class="uel-subsection">
                            <h3 class="uel-subsection-title">Deductible & Coverage Category</h3>
                            <div class="uel-table-wrap">
                                <table class="uel-modern-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 32%;">Category</th>
                                            <th style="width: 18%;">DED Applied? Yes/No</th>
                                            <th style="width: 18%;">Category %</th>
                                            <th style="width: 32%;">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td data-label="Category"><b>Diagnostic & Preventive %</b></td><td data-label="DED Applied?"><select><option>Yes</option><option selected>No</option></select></td><td data-label="Category %"><input value="100%"></td><td data-label="Note"><input placeholder="Add note"></td></tr>
                                        <tr><td data-label="Category"><b>Basic Restorative %</b></td><td data-label="DED Applied?"><select><option selected>Yes</option><option>No</option></select></td><td data-label="Category %"><input value="80%"></td><td data-label="Note"><input placeholder="Add note"></td></tr>
                                        <tr><td data-label="Category"><b>Endodontics %</b></td><td data-label="DED Applied?"><select><option selected>Yes</option><option>No</option></select></td><td data-label="Category %"><input value="80%"></td><td data-label="Note"><input placeholder="Add note"></td></tr>
                                        <tr><td data-label="Category"><b>Periodontics %</b></td><td data-label="DED Applied?"><select><option selected>Yes</option><option>No</option></select></td><td data-label="Category %"><input value="80%"></td><td data-label="Note"><input placeholder="Add note"></td></tr>
                                        <tr><td data-label="Category"><b>Oral Surgery %</b></td><td data-label="DED Applied?"><select><option selected>Yes</option><option>No</option></select></td><td data-label="Category %"><input value="80%"></td><td data-label="Note"><input placeholder="Add note"></td></tr>
                                        <tr><td data-label="Category"><b>Major Restorative %</b></td><td data-label="DED Applied?"><select><option selected>Yes</option><option>No</option></select></td><td data-label="Category %"><input value="50%"></td><td data-label="Note"><input placeholder="Add note"></td></tr>
                                        <tr><td data-label="Category"><b>Orthodontics %</b></td><td data-label="DED Applied?"><select><option>Yes</option><option selected>No</option><option>Option</option></select></td><td data-label="Category %"><input placeholder="0% / 50% / Not Covered"></td><td data-label="Note"><input placeholder="Add note"></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="uel-subsection">
                            <h3 class="uel-subsection-title">Plan Provisions</h3>
                            <div class="uel-table-wrap">
                                <table class="uel-modern-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40%;">Question</th>
                                            <th style="width: 20%;" aria-label="Response"></th>
                                            <th style="width: 40%;">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td data-label="Question"><b>Is there any Waiting Period on this plan?</b><br><span class="uel-validation">If Yes, waiting period details will appear below.</span></td>
                                            <td data-label="Answer"><select id="uel-waiting-period-select" onchange="toggleUelWaitingPeriods()"><option value="no" selected>No</option><option value="yes">Yes</option></select></td>
                                            <td data-label="Details"><input placeholder="Add waiting period note"></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">
                                                <div class="uel-conditional-box" id="uel-waiting-period-details">
                                                    <div class="uel-conditional-title"><span>Waiting Period Details</span><span class="uel-inline-tag">Shown only when answer is Yes</span></div>
                                                    <table class="uel-mini-table">
                                                        <thead><tr><th>Service Category</th><th>Waiting Period</th><th>Unit</th><th>Notes</th></tr></thead>
                                                        <tbody>
                                                            @foreach (['Basic Restorative', 'Endodontics', 'Periodontics', 'Oral Surgery', 'Major Restorative', 'Orthodontics'] as $category)
                                                                <tr>
                                                                    <td data-label="Service Category"><b>{{ $category }}</b></td>
                                                                    <td data-label="Waiting Period"><input class="uel-months-input" type="number" min="0" placeholder="0"></td>
                                                                    <td data-label="Unit"><select><option>Months</option><option>Years</option><option>None</option></select></td>
                                                                    <td data-label="Notes"><input placeholder="Details"></td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr><td data-label="Question"><b>Missing Tooth Clause</b></td><td data-label="Answer"><select><option>Yes</option><option selected>No</option></select></td><td data-label="Details"><input placeholder="Add missing tooth clause note"></td></tr>
                                        <tr><td data-label="Question"><b>Crowns are paid on Prep Date or Seat Date?</b></td><td data-label="Answer"><select><option>Prep</option><option selected>Seat</option><option>Either-Or</option></select></td><td data-label="Details"><input placeholder="Add crown payment guideline note"></td></tr>
                                        <tr><td data-label="Question"><b>Prosthetic Replacement Year / Month</b></td><td data-label="Answer"><input placeholder="MM/YYYY"></td><td data-label="Details"><input placeholder="Add replacement period note"></td></tr>
                                        <tr>
                                            <td data-label="Question"><b>Coordination of Benefits</b></td>
                                            <td data-label="Answer"><select><option>Standard</option><option>Non-Dup</option><option>Birthday Rule</option><option>No COB</option><option>Other</option></select></td>
                                            <td data-label="Details"><input placeholder="Add coordination of benefits note"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="uel-section" id="uel-history">
                    <div class="uel-section-header">
                        <div><h2>Service History</h2><p class="uel-section-subtitle">Last service dates and next eligibility</p></div>
                        <span class="uel-status-pill">Eligibility History</span>
                    </div>
                    <div class="uel-section-body">
                        <div class="uel-table-wrap">
                            <table class="uel-modern-table">
                                <thead><tr><th>Service</th><th>Specific Code / Service History / Date</th><th>Notes</th></tr></thead>
                                <tbody>
                                    <tr><td data-label="Service"><b>Exams</b></td><td data-label="Specific Code / Service History / Date"><input placeholder="e.g., D0120 - 01/15/2026"></td><td data-label="Notes"><input placeholder="Add note"></td></tr>
                                    <tr><td data-label="Service"><b>Prophylaxis</b></td><td data-label="Specific Code / Service History / Date"><input placeholder="e.g., D1110 - 01/15/2026"></td><td data-label="Notes"><input placeholder="Add note"></td></tr>
                                    <tr><td data-label="Service"><b>Bitewings</b></td><td data-label="Specific Code / Service History / Date"><input placeholder="e.g., D0274 - 01/15/2026"></td><td data-label="Notes"><input placeholder="Add note"></td></tr>
                                    <tr><td data-label="Service"><b>Full Mouth X-Ray / Panoramic X-Ray</b></td><td data-label="Specific Code / Service History / Date"><input placeholder="e.g., D0210 or D0330 - 01/15/2026"></td><td data-label="Notes"><input placeholder="Add note"></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="uel-field" style="margin-top: 16px;">
                            <label>Other Major History Affecting Eligibility</label>
                            <textarea placeholder="Add any major history that may affect eligibility, frequency, downgrade, replacement, or waiting-period decisions."></textarea>
                        </div>
                    </div>
                </section>

                <section class="uel-section" id="uel-codes">
                    <div class="uel-section-header">
                        <div><h2>Frequency and Percentage</h2><p class="uel-section-subtitle">General, Basic, Major, and Orthodontics benefit frequency and coverage.</p></div>
                        <span class="uel-status-pill">Benefit Groups</span>
                    </div>
                    <div class="uel-section-body">
                        <div class="uel-subsection">
                            <h3 class="uel-subsection-title">General Benefit</h3>
                            <div class="uel-table-wrap">
                                <table class="uel-modern-table">
                                    <thead><tr><th>Code</th><th>Description</th><th>%</th><th>Frequency</th><th>Pre-Auth</th><th>Notes</th></tr></thead>
                                    <tbody>
                                        <tr><td data-label="Code">D0120</td><td data-label="Description">Regular Checkup</td><td data-label="%"><input value="100%"></td><td data-label="Frequency"><input value="2 / 12 MTS"></td><td data-label="Pre-Auth"><select><option>No</option><option>Yes</option></select></td><td data-label="Notes"><input></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="uel-subsection">
                            <h3 class="uel-subsection-title">Basic Benefit</h3>
                            <div class="uel-table-wrap">
                                <table class="uel-modern-table">
                                    <thead><tr><th>Code</th><th>Description</th><th>%</th><th>Frequency</th><th>Pre-Auth</th><th>Notes</th></tr></thead>
                                    <tbody>
                                        <tr><td data-label="Code">D2391</td><td data-label="Description">One Surface Filling</td><td data-label="%"><input value="80%"></td><td data-label="Frequency"><input placeholder="Frequency"></td><td data-label="Pre-Auth"><select><option>No</option><option>Yes</option></select></td><td data-label="Notes"><input></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="uel-subsection">
                            <h3 class="uel-subsection-title">Major Benefit</h3>
                            <div class="uel-table-wrap">
                                <table class="uel-modern-table">
                                    <thead><tr><th>Code</th><th>Description</th><th>%</th><th>Frequency</th><th>Pre-Auth</th><th>Notes</th></tr></thead>
                                    <tbody>
                                        <tr><td data-label="Code">D2740</td><td data-label="Description">Crown</td><td data-label="%"><input value="50%"></td><td data-label="Frequency"><input value="1 / 5 YRS"></td><td data-label="Pre-Auth"><select><option>Yes</option><option>No</option></select></td><td data-label="Notes"><input></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="uel-subsection">
                            <h3 class="uel-subsection-title">Orthodontics Benefit</h3>
                            <div class="uel-table-wrap">
                                <table class="uel-modern-table">
                                    <thead><tr><th>Code</th><th>Description</th><th>%</th><th>Frequency</th><th>Pre-Auth</th><th>Notes</th></tr></thead>
                                    <tbody>
                                        <tr><td data-label="Code">D8090</td><td data-label="Description">Comprehensive Orthodontic Treatment</td><td data-label="%"><input placeholder="0% / 50% / Not Covered"></td><td data-label="Frequency"><input placeholder="Lifetime / Age limit"></td><td data-label="Pre-Auth"><select><option>No</option><option>Yes</option></select></td><td data-label="Notes"><input></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="uel-section" id="uel-verify">
                    <div class="uel-section-header">
                        <div><h2>Verification Information</h2><p class="uel-section-subtitle">Representative, reference number and final notes</p></div>
                        <span class="uel-status-pill">Ready</span>
                    </div>
                    <div class="uel-section-body uel-grid">
                        <div class="uel-field uel-full"><label>Additional Information</label><textarea>ACTIVE, RM $1720, DED MET.</textarea></div>
                        <div class="uel-field"><label>Insurance Representative</label><input></div>
                        <div class="uel-field"><label>Call Reference Number</label><input></div>
                        <div class="uel-field"><label>Verification Method</label><select><option>Phone</option><option>Portal</option><option>API</option></select></div>
                    </div>
                    <div class="uel-footer-actions">
                        <button type="button" class="uel-button uel-button-light">Save</button>
                        <button type="button" class="uel-button uel-button-danger">Send Back</button>
                        <button type="button" class="uel-button uel-button-primary">Approve & Deliver</button>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        function toggleUelWaitingPeriods() {
            const select = document.getElementById('uel-waiting-period-select');
            const box = document.getElementById('uel-waiting-period-details');
            if (!select || !box) return;
            box.style.display = select.value === 'yes' ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', toggleUelWaitingPeriods);
    </script>
</x-filament-panels::page>
