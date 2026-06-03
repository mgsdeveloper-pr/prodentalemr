<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $cards = $analytics['cards'] ?? [];
            $snapshot = $analytics['snapshot'] ?? [];
            $bars = $analytics['bars'] ?? [];
        @endphp

        <style>
            .verification-sla {
                display: flex;
                flex-direction: column;
                gap: 1.25rem;
            }

            .verification-sla__heading {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
                flex-wrap: wrap;
            }

            .verification-sla__title {
                margin: 0;
                font-size: 1.08rem;
                font-weight: 800;
                color: #0f172a;
            }

            .verification-sla__subtitle {
                margin: 0.35rem 0 0;
                font-size: 0.92rem;
                color: #6b7280;
                line-height: 1.5;
            }

            .verification-sla__scope {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                border: 1px solid #dbe4ee;
                background: #f8fafc;
                padding: 0.45rem 0.85rem;
                font-size: 0.78rem;
                font-weight: 800;
                color: #475569;
            }

            .verification-sla__grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: minmax(0, 2.2fr) minmax(280px, 1fr);
            }

            .verification-sla__cards {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .verification-sla__card,
            .verification-sla__panel {
                border: 1px solid #e5e7eb;
                border-radius: 1rem;
                background: #ffffff;
                padding: 1rem;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
            }

            .verification-sla__eyebrow {
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #64748b;
            }

            .verification-sla__value {
                margin-top: 0.45rem;
                font-size: 1.7rem;
                line-height: 1;
                font-weight: 800;
                color: #0f172a;
            }

            .verification-sla__copy {
                margin-top: 0.65rem;
                font-size: 0.82rem;
                line-height: 1.55;
                color: #6b7280;
            }

            .verification-sla__pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 0.2rem 0.55rem;
                margin-top: 0.65rem;
                font-size: 0.7rem;
                font-weight: 800;
            }

            .verification-sla__panel-title {
                margin: 0;
                font-size: 0.95rem;
                font-weight: 800;
                color: #0f172a;
            }

            .verification-sla__snapshot {
                display: grid;
                gap: 0.75rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-top: 1rem;
            }

            .verification-sla__snapshot-box {
                border-radius: 0.9rem;
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                padding: 0.85rem;
            }

            .verification-sla__snapshot-label {
                font-size: 0.72rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
            }

            .verification-sla__snapshot-value {
                margin-top: 0.35rem;
                font-size: 1.1rem;
                font-weight: 800;
                color: #0f172a;
            }

            .verification-sla__bars {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .verification-sla__bar-meta {
                display: flex;
                justify-content: space-between;
                gap: 0.75rem;
                font-size: 0.78rem;
                color: #475569;
                margin-bottom: 0.3rem;
            }

            .verification-sla__bar-track {
                height: 8px;
                border-radius: 999px;
                background: #eef2f7;
                overflow: hidden;
            }

            .verification-sla__bar-fill {
                height: 100%;
                border-radius: 999px;
            }

            @media (max-width: 1200px) {
                .verification-sla__cards {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 1024px) {
                .verification-sla__grid {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            @media (max-width: 640px) {
                .verification-sla__cards,
                .verification-sla__snapshot {
                    grid-template-columns: minmax(0, 1fr);
                }
            }
        </style>

        <div class="verification-sla">
            <div class="verification-sla__heading">
                <div>
                    <h3 class="verification-sla__title">SLA Analytics</h3>
                    <p class="verification-sla__subtitle">Track active turnaround, clinic-response delay, and operational backlog aging from the live verification queue.</p>
                </div>
                <span class="verification-sla__scope">{{ $scopeLabel }}</span>
            </div>

            <div class="verification-sla__grid">
                <div class="verification-sla__cards">
                    @foreach ($cards as $card)
                        @php
                            $accent = match ($card['accent'] ?? 'slate') {
                                'emerald' => ['bg' => '#ecfdf5', 'border' => '#bbf7d0', 'text' => '#15803d'],
                                'sky' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#2563eb'],
                                'amber' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#d97706'],
                                'indigo' => ['bg' => '#eef2ff', 'border' => '#c7d2fe', 'text' => '#4338ca'],
                                default => ['bg' => '#f8fafc', 'border' => '#dbe4ee', 'text' => '#475569'],
                            };
                        @endphp

                        <div class="verification-sla__card">
                            <div class="verification-sla__eyebrow">{{ $card['label'] }}</div>
                            <div class="verification-sla__value">{{ $card['value'] }}</div>
                            <span class="verification-sla__pill" style="background: {{ $accent['bg'] }}; border: 1px solid {{ $accent['border'] }}; color: {{ $accent['text'] }};">
                                {{ $card['label'] }}
                            </span>
                            <div class="verification-sla__copy">{{ $card['description'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="verification-sla__panel">
                    <h4 class="verification-sla__panel-title">Operational Snapshot</h4>

                    <div class="verification-sla__snapshot">
                        <div class="verification-sla__snapshot-box">
                            <div class="verification-sla__snapshot-label">Due Today</div>
                            <div class="verification-sla__snapshot-value">{{ number_format($snapshot['due_today'] ?? 0) }}</div>
                        </div>
                        <div class="verification-sla__snapshot-box">
                            <div class="verification-sla__snapshot-label">Overdue</div>
                            <div class="verification-sla__snapshot-value">{{ number_format($snapshot['overdue'] ?? 0) }}</div>
                        </div>
                        <div class="verification-sla__snapshot-box">
                            <div class="verification-sla__snapshot-label">Waiting on Clinic</div>
                            <div class="verification-sla__snapshot-value">{{ number_format($snapshot['waiting_on_clinic'] ?? 0) }}</div>
                        </div>
                        <div class="verification-sla__snapshot-box">
                            <div class="verification-sla__snapshot-label">Returned for Rework</div>
                            <div class="verification-sla__snapshot-value">{{ number_format($snapshot['returned_for_rework'] ?? 0) }}</div>
                        </div>
                    </div>

                    <div class="verification-sla__bars">
                        @foreach ($bars as $bar)
                            <div>
                                <div class="verification-sla__bar-meta">
                                    <span>{{ $bar['label'] }}</span>
                                    <span>{{ number_format($bar['value']) }}</span>
                                </div>
                                <div class="verification-sla__bar-track">
                                    <div
                                        class="verification-sla__bar-fill"
                                        style="width: {{ $bar['width'] }}%; background: {{ match ($bar['key'] ?? null) {
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
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
