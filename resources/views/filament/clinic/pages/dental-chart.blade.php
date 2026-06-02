<x-filament-panels::page>
    <style>
        .pd-chart-page {
            display: grid;
            gap: 1.5rem;
        }

        .pd-chart-shell {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
        }

        .pd-chart-summary {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .pd-chart-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.5rem;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .pd-chart-card-body {
            padding: 1.4rem 1.5rem;
        }

        .pd-chart-stat {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .pd-chart-stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .pd-chart-stat-label {
            margin: 0;
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .pd-chart-stat-value {
            margin: 0.25rem 0 0;
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }

        .pd-chart-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .pd-tone-slate { background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; }
        .pd-tone-sky { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .pd-tone-amber { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .pd-tone-emerald { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .pd-tone-rose { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }

        .pd-chart-stat-copy {
            margin: 0;
            font-size: 0.92rem;
            line-height: 1.6;
            color: #64748b;
        }

        .pd-chart-main {
            display: grid;
            gap: 1.5rem;
        }

        .pd-chart-panel {
            border: 1px solid #e2e8f0;
            border-radius: 2rem;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .pd-chart-panel-body {
            padding: 1.5rem;
        }

        .pd-chart-panel-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            padding-bottom: 1.35rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .pd-chart-overline {
            margin: 0 0 0.55rem;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: #d97706;
        }

        .pd-chart-title {
            margin: 0;
            font-size: 1.8rem;
            line-height: 1.2;
            font-weight: 800;
            color: #0f172a;
        }

        .pd-chart-subtitle {
            margin: 0.85rem 0 0;
            max-width: 46rem;
            font-size: 0.95rem;
            line-height: 1.7;
            color: #64748b;
        }

        .pd-chart-patient-pill {
            min-width: 220px;
            border-radius: 1.25rem;
            border: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f8fafc, #fff7ed);
            padding: 1rem 1.1rem;
        }

        .pd-chart-patient-pill strong {
            display: block;
            color: #0f172a;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .pd-chart-patient-pill span {
            color: #64748b;
            font-size: 0.88rem;
        }

        .pd-chart-help-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 1.5rem;
        }

        .pd-chart-help-item {
            border-radius: 1.25rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
        }

        .pd-chart-help-item strong {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.95rem;
        }

        .pd-chart-help-item p {
            margin: 0;
            font-size: 0.86rem;
            line-height: 1.6;
        }

        .pd-chart-arch {
            margin-top: 1.5rem;
            border-radius: 1.8rem;
            border: 1px solid #e2e8f0;
            padding: 1.35rem;
            background: linear-gradient(145deg, #ffffff, #fffaf0);
        }

        .pd-chart-arch + .pd-chart-arch {
            margin-top: 1rem;
            background: linear-gradient(145deg, #ffffff, #f8fbff);
        }

        .pd-chart-arch-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pd-chart-arch-head p {
            margin: 0;
            color: #64748b;
            font-size: 0.85rem;
        }

        .pd-chart-arch-head strong {
            display: block;
            color: #334155;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            margin-bottom: 0.25rem;
        }

        .pd-chart-tooth-grid {
            display: grid;
            grid-template-columns: repeat(8, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .pd-chart-tooth {
            appearance: none;
            width: 100%;
            border-radius: 1.25rem;
            border: 1px solid #dbe4ee;
            background: #ffffff;
            padding: 0.95rem;
            text-align: left;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            min-height: 145px;
        }

        .pd-chart-tooth:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        .pd-chart-tooth.is-selected {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.24);
        }

        .pd-chart-tooth-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .pd-chart-tooth-num {
            font-size: 1.1rem;
            font-weight: 800;
            color: inherit;
        }

        .pd-chart-tooth-mini {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.18rem 0.55rem;
            background: rgba(255, 255, 255, 0.85);
            color: #334155;
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.14em;
        }

        .pd-chart-tooth.is-selected .pd-chart-tooth-mini {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .pd-chart-tooth-headline {
            margin: 0.75rem 0 0;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.35;
            color: inherit;
        }

        .pd-chart-tooth-copy {
            margin: 0.35rem 0 0;
            font-size: 0.76rem;
            line-height: 1.55;
            color: inherit;
            opacity: 0.84;
        }

        .pd-chart-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.8rem;
        }

        .pd-chart-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.26rem 0.5rem;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .pd-chart-chip-amber { background: #fef3c7; color: #92400e; }
        .pd-chart-chip-emerald { background: #d1fae5; color: #065f46; }
        .pd-chart-chip-rose { background: #ffe4e6; color: #9f1239; }

        .pd-chart-tooth.is-selected .pd-chart-chip {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .pd-chart-side {
            display: grid;
            gap: 1.5rem;
            align-content: start;
        }

        .pd-chart-selected-actions {
            display: grid;
            gap: 0.75rem;
            margin-top: 1.2rem;
        }

        .pd-chart-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            padding: 0.9rem 1rem;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.92rem;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .pd-chart-action:hover {
            transform: translateY(-1px);
        }

        .pd-chart-action-primary {
            background: #0f172a;
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        }

        .pd-chart-action-warning {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .pd-chart-action-success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .pd-chart-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 1.25rem;
            background: #f8fafc;
            padding: 1rem;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.7;
        }

        .pd-chart-timeline {
            display: grid;
            gap: 1rem;
            margin-top: 1.25rem;
        }

        .pd-chart-timeline-item {
            border-radius: 1.3rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 1rem;
        }

        .pd-chart-timeline-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .pd-chart-pill-dark {
            border-radius: 999px;
            background: #0f172a;
            color: #ffffff;
            padding: 0.3rem 0.65rem;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .pd-chart-pill-soft {
            border-radius: 999px;
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 0.28rem 0.6rem;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .pd-chart-timeline-date {
            font-size: 0.76rem;
            color: #64748b;
            font-weight: 600;
        }

        .pd-chart-timeline-item strong {
            display: block;
            margin-top: 0.85rem;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .pd-chart-timeline-desc {
            margin-top: 0.35rem;
            color: #475569;
            font-size: 0.9rem;
            line-height: 1.65;
        }

        .pd-chart-timeline-meta {
            margin-top: 0.45rem;
            font-size: 0.76rem;
            color: #64748b;
        }

        .pd-chart-timeline-note {
            margin-top: 0.8rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 0.8rem 0.9rem;
            color: #475569;
            font-size: 0.87rem;
            line-height: 1.65;
        }

        .pd-chart-timeline-links {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .pd-chart-timeline-links a {
            color: #0f172a;
            font-size: 0.86rem;
            font-weight: 700;
            text-decoration: none;
        }

        .pd-chart-timeline-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 1280px) {
            .pd-chart-shell {
                grid-template-columns: 1fr;
            }

            .pd-chart-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .pd-chart-help-grid,
            .pd-chart-summary {
                grid-template-columns: 1fr;
            }

            .pd-chart-tooth-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .pd-chart-panel-hero,
            .pd-chart-arch-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .pd-chart-patient-pill {
                width: 100%;
                min-width: 0;
            }
        }

        @media (max-width: 640px) {
            .pd-chart-card-body,
            .pd-chart-panel-body {
                padding: 1.1rem;
            }

            .pd-chart-title {
                font-size: 1.5rem;
            }

            .pd-chart-tooth {
                min-height: 132px;
                padding: 0.8rem;
            }
        }
    </style>

    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @php
        $summaryCards = $this->getSummaryCards();
        $upperArch = $this->getUpperArch();
        $lowerArch = $this->getLowerArch();
        $selectedTooth = $this->getSelectedToothData();
        $patient = $this->getActivePatient();
    @endphp

    <div class="pd-chart-page">
        <div class="pd-chart-summary">
            @foreach ($summaryCards as $card)
                <div class="pd-chart-card">
                    <div class="pd-chart-card-body pd-chart-stat">
                        <div class="pd-chart-stat-top">
                            <div>
                                <p class="pd-chart-stat-label">{{ $card['label'] }}</p>
                                <p class="pd-chart-stat-value">{{ $card['value'] }}</p>
                            </div>
                            <span class="pd-chart-badge pd-tone-{{ $card['tone'] }}">{{ str($card['tone'])->title() }}</span>
                        </div>
                        <p class="pd-chart-stat-copy">{{ $card['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pd-chart-shell">
            <div class="pd-chart-main">
                <div class="pd-chart-panel">
                    <div class="pd-chart-panel-body">
                        <div class="pd-chart-panel-hero">
                            <div>
                                <p class="pd-chart-overline">Visual Chart</p>
                                <h2 class="pd-chart-title">
                                    {{ $patient ? $patient->full_name . '\'s dental chart' : 'Select a patient to begin charting' }}
                                </h2>
                                <p class="pd-chart-subtitle">
                                    Review existing findings, planned treatment, and completed work in a tooth-first layout. Click any tooth to inspect history or add a new chart action.
                                </p>
                            </div>
                            <div class="pd-chart-patient-pill">
                                <strong>{{ $patient?->full_name ?? 'No patient selected' }}</strong>
                                <span>{{ $patient?->phone ?: 'Patient contact not available' }}</span>
                            </div>
                        </div>

                        <div class="pd-chart-help-grid">
                            <div class="pd-chart-help-item pd-tone-rose">
                                <strong>Watch / concern</strong>
                                <p>Caries, fracture, perio concern, or anything needing closer attention.</p>
                            </div>
                            <div class="pd-chart-help-item pd-tone-amber">
                                <strong>Planned treatment</strong>
                                <p>Accepted or proposed treatment items waiting to be scheduled or completed.</p>
                            </div>
                            <div class="pd-chart-help-item pd-tone-emerald">
                                <strong>Completed work</strong>
                                <p>Completed procedures and closed chart actions already documented.</p>
                            </div>
                            <div class="pd-chart-help-item pd-tone-sky">
                                <strong>Existing work</strong>
                                <p>Existing restorations or prior work carried forward into the chart.</p>
                            </div>
                        </div>

                        <div class="pd-chart-arch">
                            <div class="pd-chart-arch-head">
                                <div>
                                    <strong>Upper Arch</strong>
                                    <p>Permanent teeth 1 through 16</p>
                                </div>
                                <p>Click a tooth to inspect its chart history.</p>
                            </div>

                            <div class="pd-chart-tooth-grid">
                                @foreach ($upperArch as $tooth)
                                    <button type="button" wire:click="selectTooth('{{ $tooth['number'] }}')" class="pd-chart-tooth {{ $tooth['selected'] ? 'is-selected' : '' }}">
                                        <div class="pd-chart-tooth-top">
                                            <span class="pd-chart-tooth-num">{{ $tooth['number'] }}</span>
                                            @if ($tooth['has_entries'])
                                                <span class="pd-chart-tooth-mini">Charted</span>
                                            @endif
                                        </div>
                                        <p class="pd-chart-tooth-headline">{{ $tooth['headline'] }}</p>
                                        <p class="pd-chart-tooth-copy">{{ $tooth['subline'] }}</p>
                                        <div class="pd-chart-chip-row">
                                            @if ($tooth['counts']['planned'] > 0)
                                                <span class="pd-chart-chip pd-chart-chip-amber">Planned {{ $tooth['counts']['planned'] }}</span>
                                            @endif
                                            @if ($tooth['counts']['completed'] > 0)
                                                <span class="pd-chart-chip pd-chart-chip-emerald">Done {{ $tooth['counts']['completed'] }}</span>
                                            @endif
                                            @if ($tooth['counts']['watch'] > 0)
                                                <span class="pd-chart-chip pd-chart-chip-rose">Watch {{ $tooth['counts']['watch'] }}</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="pd-chart-arch">
                            <div class="pd-chart-arch-head">
                                <div>
                                    <strong>Lower Arch</strong>
                                    <p>Permanent teeth 32 through 17</p>
                                </div>
                                <p>Use the same workflow for treatment planning and completed work.</p>
                            </div>

                            <div class="pd-chart-tooth-grid">
                                @foreach ($lowerArch as $tooth)
                                    <button type="button" wire:click="selectTooth('{{ $tooth['number'] }}')" class="pd-chart-tooth {{ $tooth['selected'] ? 'is-selected' : '' }}">
                                        <div class="pd-chart-tooth-top">
                                            <span class="pd-chart-tooth-num">{{ $tooth['number'] }}</span>
                                            @if ($tooth['has_entries'])
                                                <span class="pd-chart-tooth-mini">Charted</span>
                                            @endif
                                        </div>
                                        <p class="pd-chart-tooth-headline">{{ $tooth['headline'] }}</p>
                                        <p class="pd-chart-tooth-copy">{{ $tooth['subline'] }}</p>
                                        <div class="pd-chart-chip-row">
                                            @if ($tooth['counts']['planned'] > 0)
                                                <span class="pd-chart-chip pd-chart-chip-amber">Planned {{ $tooth['counts']['planned'] }}</span>
                                            @endif
                                            @if ($tooth['counts']['completed'] > 0)
                                                <span class="pd-chart-chip pd-chart-chip-emerald">Done {{ $tooth['counts']['completed'] }}</span>
                                            @endif
                                            @if ($tooth['counts']['watch'] > 0)
                                                <span class="pd-chart-chip pd-chart-chip-rose">Watch {{ $tooth['counts']['watch'] }}</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pd-chart-side">
                <div class="pd-chart-panel">
                    <div class="pd-chart-panel-body">
                        <div class="pd-chart-panel-hero" style="padding-bottom: 0; border-bottom: 0;">
                            <div>
                                <p class="pd-chart-overline" style="color:#64748b;">Selected Tooth</p>
                                <h3 class="pd-chart-title" style="font-size:1.55rem;">
                                    {{ $selectedTooth ? 'Tooth ' . $selectedTooth['tooth'] : 'Pick a tooth' }}
                                </h3>
                            </div>
                            @if ($selectedTooth && $selectedTooth['latest'])
                                <span class="pd-chart-badge pd-tone-slate">{{ $selectedTooth['latest']['chart_type'] }}</span>
                            @endif
                        </div>

                        @if ($selectedTooth && $selectedTooth['latest'])
                            <div class="pd-chart-empty" style="margin-top:1rem; border-style:solid; background:#f8fafc;">
                                <strong style="display:block; color:#0f172a; margin-bottom:0.3rem;">{{ $selectedTooth['latest']['condition'] }}</strong>
                                <span style="font-size:0.85rem; color:#64748b;">{{ $selectedTooth['latest']['status'] }}</span>
                            </div>
                        @endif

                        <div class="pd-chart-selected-actions">
                            @if ($selectedTooth)
                                <a href="{{ $selectedTooth['create_urls']['condition'] }}" class="pd-chart-action pd-chart-action-primary">Add condition or existing finding</a>
                                <a href="{{ $selectedTooth['create_urls']['planned'] }}" class="pd-chart-action pd-chart-action-warning">Add planned treatment</a>
                                <a href="{{ $selectedTooth['create_urls']['completed'] }}" class="pd-chart-action pd-chart-action-success">Add completed work</a>
                            @else
                                <div class="pd-chart-empty">Choose a patient and click any tooth to start charting visually.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pd-chart-panel">
                    <div class="pd-chart-panel-body">
                        <div class="pd-chart-panel-hero" style="padding-bottom: 0; border-bottom: 0;">
                            <div>
                                <p class="pd-chart-overline" style="color:#64748b;">History</p>
                                <h3 class="pd-chart-title" style="font-size:1.35rem;">Tooth Timeline</h3>
                            </div>
                            <span class="pd-chart-badge pd-tone-slate">
                                {{ $selectedTooth ? count($selectedTooth['entries']) . ' entries' : 'No tooth selected' }}
                            </span>
                        </div>

                        <div class="pd-chart-timeline">
                            @forelse (($selectedTooth['entries'] ?? []) as $entry)
                                <div class="pd-chart-timeline-item">
                                    <div class="pd-chart-timeline-head">
                                        <span class="pd-chart-pill-dark">{{ $entry['chart_type'] }}</span>
                                        <span class="pd-chart-pill-soft">{{ $entry['status'] }}</span>
                                        <span class="pd-chart-timeline-date">{{ $entry['recorded_on'] }}</span>
                                    </div>
                                    <strong>{{ $entry['condition'] }}</strong>
                                    <div class="pd-chart-timeline-desc">{{ $entry['description'] }}</div>
                                    <div class="pd-chart-timeline-meta">Surface: {{ $entry['surface'] }} @if($entry['provider']) - {{ $entry['provider'] }} @endif</div>
                                    @if (filled($entry['notes']))
                                        <div class="pd-chart-timeline-note">{{ $entry['notes'] }}</div>
                                    @endif
                                    <div class="pd-chart-timeline-links">
                                        <a href="{{ $entry['view_url'] }}">View record</a>
                                        <a href="{{ $entry['edit_url'] }}">Edit entry</a>
                                    </div>
                                </div>
                            @empty
                                <div class="pd-chart-empty">No chart entries are recorded for this tooth in the current filter view yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
