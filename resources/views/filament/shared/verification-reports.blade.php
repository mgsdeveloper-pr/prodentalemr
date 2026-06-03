<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @php
        $summaryCards = $this->getSummaryCards();
        $trendChart = $this->getTrendChart();
        $statusVisualization = $this->getStatusVisualization();
        $outcomeVisualization = $this->getOutcomeVisualization();
        $sourceVisualization = $this->getSourceVisualization();
        $assigneeVisualization = $this->getAssigneeVisualization();
        $slaAnalytics = $this->getSlaAnalytics();
        $recentRows = $this->getRecentRows();
        $activityFocusChips = $this->getActivityFocusChips();
    @endphp

    <style>
        .verification-reports-grid {
            display: grid;
            gap: 20px;
        }

        .verification-reports-summary {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .verification-reports-main {
            display: grid;
            gap: 20px;
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
        }

        .verification-reports-sla {
            display: grid;
            gap: 20px;
            grid-template-columns: minmax(0, 2.2fr) minmax(300px, 1fr);
        }

        .verification-reports-sla-cards {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .verification-reports-snapshot {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 18px;
        }

        .verification-reports-snapshot-box {
            border-radius: 16px;
            border: 1px solid #e8eef5;
            background: #f8fafc;
            padding: 14px;
        }

        .verification-reports-snapshot-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .verification-reports-snapshot-value {
            margin-top: 6px;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .verification-reports-breakdowns {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .verification-reports-card {
            border: 1px solid #dbe4ee;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .verification-reports-card__body {
            padding: 20px;
        }

        .verification-reports-kpi {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .verification-reports-kpi__value {
            font-size: 32px;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
            margin-top: 8px;
        }

        .verification-reports-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #dbe4ee;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 800;
        }

        .verification-reports-bars {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 18px;
        }

        .verification-reports-bar__meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
            color: #334155;
            margin-bottom: 6px;
        }

        .verification-reports-bar__track {
            height: 8px;
            border-radius: 999px;
            background: #eef2f7;
            overflow: hidden;
        }

        .verification-reports-bar__fill {
            height: 100%;
            border-radius: 999px;
        }

        .verification-reports-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);
        }

        .verification-reports-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 920px;
        }

        .verification-reports-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .verification-reports-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid #dbe4ee;
            background: #ffffff;
            color: #475569;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
        }

        .verification-reports-chip:hover {
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .verification-reports-chip--clear {
            background: #f8fafc;
            color: #0f172a;
        }

        .verification-reports-chip--active {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1d4ed8;
        }

        .verification-reports-table th,
        .verification-reports-table td {
            padding: 13px 14px;
            border-top: 1px solid #e8eef5;
            text-align: left;
            font-size: 13px;
        }

        .verification-reports-table th {
            color: #64748b;
            font-weight: 800;
            letter-spacing: 0.03em;
            background: #f8fafc;
            border-top: none;
            white-space: nowrap;
        }

        .verification-reports-table tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        .verification-reports-table tbody tr:hover {
            background: #f8fbff;
        }

        .verification-reports-table tbody td:first-child {
            font-weight: 800;
            color: #0f172a;
        }

        @media (max-width: 1200px) {
            .verification-reports-summary,
            .verification-reports-breakdowns {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .verification-reports-main,
            .verification-reports-sla {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 768px) {
            .verification-reports-summary,
            .verification-reports-breakdowns,
            .verification-reports-sla-cards,
            .verification-reports-snapshot {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="verification-reports-grid">
        <section class="verification-reports-summary">
            @foreach ($summaryCards as $card)
                @php
                    $accent = match ($card['accent'] ?? 'slate') {
                        'emerald' => ['bg' => '#ecfdf5', 'border' => '#bbf7d0', 'text' => '#15803d'],
                        'sky' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#2563eb'],
                        'rose' => ['bg' => '#fff1f2', 'border' => '#fecdd3', 'text' => '#e11d48'],
                        'amber' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#d97706'],
                        'indigo' => ['bg' => '#eef2ff', 'border' => '#c7d2fe', 'text' => '#4338ca'],
                        default => ['bg' => '#f8fafc', 'border' => '#dbe4ee', 'text' => '#475569'],
                    };
                @endphp
                <div class="verification-reports-card">
                    <div class="verification-reports-card__body">
                        <div class="verification-reports-kpi">
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: #64748b;">{{ $card['label'] }}</div>
                                <div class="verification-reports-kpi__value">{{ $card['value'] }}</div>
                            </div>
                            <span
                                class="verification-reports-pill"
                                style="background: {{ $accent['bg'] }}; border-color: {{ $accent['border'] }}; color: {{ $accent['text'] }};"
                            >
                                {{ $card['label'] }}
                            </span>
                        </div>
                        <div style="margin-top: 14px; font-size: 13px; line-height: 1.6; color: #64748b;">
                            {{ $card['description'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="verification-reports-sla">
            <div class="verification-reports-sla-cards">
                @foreach ($slaAnalytics['cards'] ?? [] as $card)
                    @php
                        $accent = match ($card['accent'] ?? 'slate') {
                            'emerald' => ['bg' => '#ecfdf5', 'border' => '#bbf7d0', 'text' => '#15803d'],
                            'sky' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#2563eb'],
                            'amber' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#d97706'],
                            'indigo' => ['bg' => '#eef2ff', 'border' => '#c7d2fe', 'text' => '#4338ca'],
                            default => ['bg' => '#f8fafc', 'border' => '#dbe4ee', 'text' => '#475569'],
                        };
                    @endphp
                    <div class="verification-reports-card">
                        <div class="verification-reports-card__body">
                            <div class="verification-reports-kpi">
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #64748b;">{{ $card['label'] }}</div>
                                    <div class="verification-reports-kpi__value">{{ $card['value'] }}</div>
                                </div>
                                <span
                                    class="verification-reports-pill"
                                    style="background: {{ $accent['bg'] }}; border-color: {{ $accent['border'] }}; color: {{ $accent['text'] }};"
                                >
                                    {{ $card['label'] }}
                                </span>
                            </div>
                            <div style="margin-top: 14px; font-size: 13px; line-height: 1.6; color: #64748b;">
                                {{ $card['description'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="verification-reports-card">
                <div class="verification-reports-card__body">
                    <div style="display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 18px; font-weight: 800; color: #0f172a;">Operational Snapshot</div>
                            <div style="margin-top: 6px; font-size: 13px; line-height: 1.6; color: #64748b;">
                                Current queue pressure across SLA-sensitive exception states.
                            </div>
                        </div>
                        <div style="text-align: right; font-size: 13px; color: #64748b;">
                            <div>Scope</div>
                            <div style="margin-top: 4px; font-weight: 700; color: #0f172a;">{{ $this->getAppliedScopeLabel() }}</div>
                        </div>
                    </div>

                    <div class="verification-reports-snapshot">
                        <div class="verification-reports-snapshot-box">
                            <div class="verification-reports-snapshot-label">Due Today</div>
                            <div class="verification-reports-snapshot-value">{{ number_format($slaAnalytics['snapshot']['due_today'] ?? 0) }}</div>
                        </div>
                        <div class="verification-reports-snapshot-box">
                            <div class="verification-reports-snapshot-label">Overdue</div>
                            <div class="verification-reports-snapshot-value">{{ number_format($slaAnalytics['snapshot']['overdue'] ?? 0) }}</div>
                        </div>
                        <div class="verification-reports-snapshot-box">
                            <div class="verification-reports-snapshot-label">Waiting on Clinic</div>
                            <div class="verification-reports-snapshot-value">{{ number_format($slaAnalytics['snapshot']['waiting_on_clinic'] ?? 0) }}</div>
                        </div>
                        <div class="verification-reports-snapshot-box">
                            <div class="verification-reports-snapshot-label">Review Queue</div>
                            <div class="verification-reports-snapshot-value">{{ number_format($slaAnalytics['snapshot']['review'] ?? 0) }}</div>
                        </div>
                    </div>

                    <div class="verification-reports-bars">
                        @foreach ($slaAnalytics['bars'] ?? [] as $row)
                            <div>
                                <div class="verification-reports-bar__meta">
                                    <span>{{ $row['label'] }}</span>
                                    <span>{{ number_format($row['value']) }}</span>
                                </div>
                                <div class="verification-reports-bar__track">
                                    <div
                                        class="verification-reports-bar__fill"
                                        style="width: {{ $row['width'] }}%; background: {{ match ($row['key'] ?? null) {
                                            \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => '#4f46e5',
                                            \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK => '#f59e0b',
                                            \App\Models\BillingWorkItem::STATUS_REVIEW => '#0ea5e9',
                                            default => '#e11d48',
                                        } }};"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Reporting dashboard visualizations are intentionally hidden for now.
             Keep this block in place so we can restore the trend/workload/breakdown
             dashboard later without rebuilding it from scratch.

        <section class="verification-reports-main">
            <div class="verification-reports-card">
                <div class="verification-reports-card__body">
                    <div style="display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 18px; font-weight: 800; color: #0f172a;">Verification Trend</div>
                            <div style="margin-top: 6px; font-size: 13px; line-height: 1.6; color: #64748b;">
                                Track created versus completed verification requests across the selected reporting window.
                            </div>
                        </div>
                        <div style="text-align: right; font-size: 13px; color: #64748b;">
                            <div>Scope</div>
                            <div style="margin-top: 4px; font-weight: 700; color: #0f172a;">{{ $this->getAppliedScopeLabel() }}</div>
                        </div>
                    </div>

                    <div style="margin-top: 20px; overflow-x: auto;">
                        <svg viewBox="0 0 640 220" style="width: 100%; min-width: 640px; height: 260px;">
                            <line x1="20" y1="200" x2="620" y2="200" stroke="#e5e7eb" stroke-width="1" />
                            <line x1="20" y1="20" x2="20" y2="200" stroke="#e5e7eb" stroke-width="1" />
                            <polyline fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="{{ $trendChart['created_points'] }}" />
                            <polyline fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="{{ $trendChart['completed_points'] }}" />
                        </svg>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 18px; font-size: 13px; color: #64748b;">
                        <span style="display: inline-flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; border-radius: 999px; background: #3b82f6;"></span>
                            Created
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; border-radius: 999px; background: #16a34a;"></span>
                            Completed
                        </span>
                        <span>Peak day: {{ $trendChart['max'] }} requests</span>
                    </div>
                </div>
            </div>

            <div class="verification-reports-card">
                <div class="verification-reports-card__body">
                    <div style="font-size: 18px; font-weight: 800; color: #0f172a;">Workload Snapshot</div>
                    <div style="margin-top: 6px; font-size: 13px; line-height: 1.6; color: #64748b;">
                        Top assignees in the current verification reporting view.
                    </div>

                    <div class="verification-reports-bars">
                        @forelse ($assigneeVisualization as $row)
                            <div>
                                <div class="verification-reports-bar__meta">
                                    <span>{{ $row['label'] }}</span>
                                    <span>{{ number_format($row['value']) }}</span>
                                </div>
                                <div class="verification-reports-bar__track">
                                    <div class="verification-reports-bar__fill" style="width: {{ $row['width'] }}%; background: #4f46e5;"></div>
                                </div>
                            </div>
                        @empty
                            <div style="font-size: 13px; color: #64748b;">No assignee activity is available for the current filters.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="verification-reports-breakdowns">
            <div class="verification-reports-card">
                <div class="verification-reports-card__body">
                    <div style="font-size: 18px; font-weight: 800; color: #0f172a;">Status Breakdown</div>
                    <div class="verification-reports-bars">
                        @forelse ($statusVisualization as $row)
                            <div>
                                <div class="verification-reports-bar__meta">
                                    <span>{{ $row['label'] }}</span>
                                    <span>{{ number_format($row['value']) }}</span>
                                </div>
                                <div class="verification-reports-bar__track">
                                    <div class="verification-reports-bar__fill" style="width: {{ $row['width'] }}%; background: #f59e0b;"></div>
                                </div>
                            </div>
                        @empty
                            <div style="font-size: 13px; color: #64748b;">No status data matches the current filters.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="verification-reports-card">
                <div class="verification-reports-card__body">
                    <div style="font-size: 18px; font-weight: 800; color: #0f172a;">Outcome Breakdown</div>
                    <div class="verification-reports-bars">
                        @forelse ($outcomeVisualization as $row)
                            <div>
                                <div class="verification-reports-bar__meta">
                                    <span>{{ $row['label'] }}</span>
                                    <span>{{ number_format($row['value']) }}</span>
                                </div>
                                <div class="verification-reports-bar__track">
                                    <div class="verification-reports-bar__fill" style="width: {{ $row['width'] }}%; background: #16a34a;"></div>
                                </div>
                            </div>
                        @empty
                            <div style="font-size: 13px; color: #64748b;">No outcome data matches the current filters.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="verification-reports-card">
                <div class="verification-reports-card__body">
                    <div style="font-size: 18px; font-weight: 800; color: #0f172a;">Ownership Mix</div>
                    <div class="verification-reports-bars">
                        @forelse ($sourceVisualization as $row)
                            <div>
                                <div class="verification-reports-bar__meta">
                                    <span>{{ $row['label'] }}</span>
                                    <span>{{ number_format($row['value']) }}</span>
                                </div>
                                <div class="verification-reports-bar__track">
                                    <div class="verification-reports-bar__fill" style="width: {{ $row['width'] }}%; background: #0ea5e9;"></div>
                                </div>
                            </div>
                        @empty
                            <div style="font-size: 13px; color: #64748b;">No ownership data matches the current filters.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
        --}}

        <section class="verification-reports-card">
            <div class="verification-reports-card__body">
                <div style="display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                    <div>
                        <div style="font-size: 18px; font-weight: 800; color: #0f172a;">Recent Verification Activity</div>
                        <div style="margin-top: 6px; font-size: 13px; line-height: 1.6; color: #64748b;">
                            Latest requests based on the active report filters.
                        </div>
                        <div class="verification-reports-chips">
                            @foreach ($activityFocusChips as $chip)
                                <button
                                    type="button"
                                    wire:click="applyActivityFocus('{{ $chip['key'] }}')"
                                    class="verification-reports-chip {{ $chip['active'] ? 'verification-reports-chip--active' : '' }}"
                                >
                                    {{ $chip['label'] }}
                                </button>
                            @endforeach

                            @if (collect($activityFocusChips)->contains(fn ($chip) => $chip['active']))
                                <button
                                    type="button"
                                    wire:click="clearActivityFocus"
                                    class="verification-reports-chip verification-reports-chip--clear"
                                >
                                    Clear
                                </button>
                            @endif
                        </div>
                    </div>
                    <div style="display: inline-flex; align-items: center; justify-content: center; border: 1px solid #dbe4ee; border-radius: 999px; background: #f8fafc; padding: 8px 14px; font-size: 12px; font-weight: 700; color: #475569;">
                        {{ count($recentRows) }} rows
                    </div>
                </div>

                <div class="verification-reports-table-wrap" style="margin-top: 18px;">
                    <table class="verification-reports-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Patient</th>
                                <th>Clinic</th>
                                <th>Status</th>
                                <th>Outcome</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentRows as $row)
                                <tr>
                                    <td>{{ $row['Reference'] }}</td>
                                    <td>{{ $row['Patient'] }}</td>
                                    <td>{{ $row['Clinic'] }}</td>
                                    <td>{{ $row['Status'] }}</td>
                                    <td>{{ $row['Outcome'] }}</td>
                                    <td>{{ $row['Priority'] }}</td>
                                    <td>{{ $row['Assigned To'] }}</td>
                                    <td>{{ $row['Created'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="color: #64748b;">No verification activity matches the current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
