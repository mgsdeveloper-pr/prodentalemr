<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .ms-console {
                display: flex;
                flex-direction: column;
                gap: 1.25rem;
            }

            .ms-console__heading {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
                padding-bottom: 0.15rem;
            }

            .ms-console__title {
                margin: 0;
                font-size: 1.08rem;
                font-weight: 800;
                color: #0f172a;
            }

            .ms-console__subtitle {
                margin: 0;
                font-size: 0.92rem;
                color: #6b7280;
                line-height: 1.5;
            }

            .ms-console__grid {
                display: grid;
                grid-template-columns: repeat(5, minmax(180px, 1fr));
                gap: 1rem;
            }

            .ms-console__card {
                display: block;
                text-decoration: none;
                border: 1px solid #e5e7eb;
                border-radius: 1rem;
                background: #ffffff;
                padding: 1rem;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
                transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            }

            .ms-console__card:hover {
                transform: translateY(-1px);
                border-color: #f5c76c;
                box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
            }

            .ms-console__card--active {
                border-color: #f5c76c;
                background: linear-gradient(180deg, #fffdf7 0%, #ffffff 100%);
                box-shadow: 0 18px 34px rgba(245, 158, 11, 0.12);
            }

            .ms-console__card-top {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .ms-console__card-title {
                margin: 0;
                font-size: 0.98rem;
                font-weight: 800;
                color: #0f172a;
            }

            .ms-console__card-copy {
                margin: 0.45rem 0 0;
                font-size: 0.8rem;
                line-height: 1.55;
                color: #6b7280;
            }

            .ms-console__metric {
                flex-shrink: 0;
                min-width: 2.4rem;
                padding: 0.3rem 0.65rem;
                border-radius: 999px;
                background: #fff7e6;
                border: 1px solid #fde2a7;
                color: #b45309;
                font-size: 0.76rem;
                font-weight: 800;
                text-align: center;
            }

            html.dark .ms-console__title,
            html.dark .ms-console__card-title {
                color: #f9fafb;
            }

            html.dark .ms-console__subtitle,
            html.dark .ms-console__card-copy {
                color: #9ca3af;
            }

            html.dark .ms-console__card {
                background: rgba(17, 24, 39, 0.75);
                border-color: rgba(255, 255, 255, 0.08);
            }

            html.dark .ms-console__card:hover {
                border-color: rgba(245, 199, 108, 0.45);
            }

            html.dark .ms-console__card--active {
                background: rgba(17, 24, 39, 0.92);
                border-color: rgba(245, 199, 108, 0.45);
            }

            html.dark .ms-console__metric {
                background: rgba(180, 83, 9, 0.12);
                border-color: rgba(245, 199, 108, 0.2);
                color: #fcd34d;
            }

            @media (max-width: 1440px) {
                .ms-console__grid {
                    grid-template-columns: repeat(5, minmax(160px, 1fr));
                }
            }

            @media (max-width: 1280px) {
                .ms-console__grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (max-width: 900px) {
                .ms-console__grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 768px) {
                .ms-console__grid {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

        </style>

        <div class="ms-console">
            <div class="ms-console__heading">
                <h3 class="ms-console__title">Verification Operations Console</h3>
                <p class="ms-console__subtitle">Jump straight into the queues and tools your insurance verification team uses most.</p>
            </div>

            <div class="ms-console__grid">
                @foreach ($links as $link)
                    @php
                        $isActive = filled($link['filter']) && $activeFilter === $link['filter'];
                    @endphp

                    <button
                        type="button"
                        wire:click="applyFilter('{{ $link['filter'] }}')"
                        class="ms-console__card {{ $isActive ? 'ms-console__card--active' : '' }}"
                        style="text-align: left; width: 100%;"
                    >
                        <div class="ms-console__card-top">
                            <div>
                                <h4 class="ms-console__card-title">{{ $link['title'] }}</h4>
                                <p class="ms-console__card-copy">{{ $link['description'] }}</p>
                            </div>

                            @if (! is_null($link['metric']))
                                <span class="ms-console__metric">{{ number_format($link['metric']) }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
