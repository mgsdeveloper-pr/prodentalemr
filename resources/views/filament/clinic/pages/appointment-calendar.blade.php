<x-filament-panels::page>
    @php
        $viewMode = $this->viewMode;
        $monthWeeks = $this->getMonthWeeks();
        $weekDays = $this->getWeekDays();
        $dayAgenda = $this->getDayAgenda();
    @endphp

    <div style="display:flex;flex-direction:column;gap:24px;background:#f5f7fb;margin:-10px;padding:10px;border-radius:22px;">
        <section style="border:1px solid #e6ebf2;border-radius:18px;background:#ffffff;box-shadow:0 14px 30px rgba(15,23,42,0.04);padding:18px 20px;">
            <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:18px;">
                <div style="display:flex;align-items:center;gap:8px;justify-self:start;">
                    <button type="button" wire:click="previousPeriod" style="width:42px;height:38px;border-radius:10px;border:1px solid #d7dfeb;background:#ffffff;color:#475569;font-size:20px;line-height:1;cursor:pointer;">&#8249;</button>
                    <button type="button" wire:click="nextPeriod" style="width:42px;height:38px;border-radius:10px;border:1px solid #d7dfeb;background:#ffffff;color:#475569;font-size:20px;line-height:1;cursor:pointer;">&#8250;</button>
                    <button type="button" wire:click="goToToday" style="min-height:38px;padding:0 16px;border-radius:10px;border:1px solid #d7dfeb;background:#ffffff;color:#334155;font-size:14px;font-weight:800;cursor:pointer;">Today</button>
                </div>

                <div style="justify-self:center;text-align:center;">
                    <div style="font-size:18px;font-weight:900;color:#0f172a;">{{ $this->getDisplayLabel() }}</div>
                    <div style="margin-top:4px;font-size:13px;color:#64748b;">({{ $this->getDisplayTimezone() }})</div>
                </div>

                <div style="display:flex;align-items:center;justify-content:flex-end;">
                    <div style="display:inline-grid;grid-template-columns:repeat(3,minmax(0,1fr));border:1px solid #d7dfeb;border-radius:10px;overflow:hidden;background:#ffffff;">
                        @foreach (['month' => 'Month', 'week' => 'Week', 'day' => 'Day'] as $mode => $label)
                            <button
                                type="button"
                                wire:click="setViewMode('{{ $mode }}')"
                                style="min-width:92px;min-height:38px;border:none;background:{{ $viewMode === $mode ? '#5b6fd8' : '#ffffff' }};color:{{ $viewMode === $mode ? '#ffffff' : '#334155' }};font-size:14px;font-weight:800;cursor:pointer;"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section style="border:1px solid #e6ebf2;border-radius:18px;background:#ffffff;box-shadow:0 14px 30px rgba(15,23,42,0.04);overflow:hidden;">
            @if ($viewMode === 'month')
                <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border-bottom:1px solid #dbe4ee;background:#ffffff;">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                        <div style="padding:18px 26px;font-size:16px;font-weight:900;color:#1e293b;border-right:1px solid #dbe4ee;">{{ $dayName }}</div>
                    @endforeach
                </div>

                <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));">
                    @foreach ($monthWeeks as $week)
                        @foreach ($week as $day)
                            <div
                                style="
                                    min-height:126px;
                                    padding:14px 10px 10px;
                                    border-right:1px solid #dbe4ee;
                                    border-bottom:1px solid #dbe4ee;
                                    background:{{ $day['is_current_month'] ? ($day['is_selected'] ? '#eef2ff' : '#ffffff') : '#f4f7fb' }};
                                "
                            >
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;">
                                    <div style="font-size:14px;font-weight:800;color:{{ $day['is_current_month'] ? ($day['is_selected'] ? '#4f46e5' : '#64748b') : '#cbd5e1' }};">
                                        {{ $day['day_number'] }}
                                    </div>
                                </div>

                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    @forelse (array_slice($day['events'], 0, 3) as $event)
                                        <a
                                            href="{{ $event['url'] }}"
                                            style="
                                                display:block;
                                                padding:5px 8px;
                                                border-radius:4px;
                                                background:{{ $event['color']['bg'] }};
                                                color:{{ $event['color']['text'] }};
                                                font-size:12px;
                                                font-weight:700;
                                                line-height:1.35;
                                                text-decoration:none;
                                                white-space:nowrap;
                                                overflow:hidden;
                                                text-overflow:ellipsis;
                                            "
                                            title="{{ $event['title'] }}"
                                        >
                                            {{ $event['title'] }}
                                        </a>
                                    @empty
                                        <div style="height:22px;"></div>
                                    @endforelse

                                    @if (count($day['events']) > 3)
                                        <div style="font-size:12px;font-weight:700;color:#64748b;padding:0 2px;">
                                            +{{ count($day['events']) - 3 }} more
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @elseif ($viewMode === 'week')
                <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border-bottom:1px solid #dbe4ee;background:#ffffff;">
                    @foreach ($weekDays as $day)
                        <div style="padding:18px 18px 16px;border-right:1px solid #dbe4ee;background:{{ $day['is_selected'] ? '#eef2ff' : '#ffffff' }};">
                            <div style="font-size:14px;font-weight:800;color:#64748b;">{{ $day['label'] }}</div>
                            <div style="margin-top:6px;font-size:24px;font-weight:900;color:{{ $day['is_selected'] ? '#4f46e5' : '#0f172a' }};">{{ $day['day_number'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));align-items:start;">
                    @foreach ($weekDays as $day)
                        <div style="min-height:540px;padding:12px;border-right:1px solid #dbe4ee;border-bottom:1px solid #dbe4ee;background:#ffffff;display:flex;flex-direction:column;gap:8px;">
                            @forelse ($day['events'] as $event)
                                <a href="{{ $event['url'] }}" style="display:flex;flex-direction:column;gap:4px;padding:10px;border-radius:10px;background:{{ $event['color']['soft'] }};border:1px solid {{ $event['color']['border'] }};text-decoration:none;">
                                    <div style="font-size:11px;font-weight:800;color:#64748b;">{{ $event['time'] ?: 'Time pending' }}</div>
                                    <div style="font-size:13px;font-weight:800;color:#0f172a;line-height:1.4;">{{ $event['title'] }}</div>
                                    <div style="font-size:11px;font-weight:700;color:#64748b;">{{ $event['status'] }}</div>
                                </a>
                            @empty
                                <div style="padding:14px 10px;border:1px dashed #dbe4ee;border-radius:10px;background:#f8fafc;font-size:12px;color:#94a3b8;text-align:center;">
                                    No appointments
                                </div>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding:22px;display:flex;flex-direction:column;gap:16px;">
                    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.14em;text-transform:uppercase;color:#0f766e;">Day View</div>
                            <div style="margin-top:8px;font-size:26px;font-weight:900;color:#0f172a;">{{ $this->getDisplayLabel() }}</div>
                        </div>
                        <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#f8fafc;border:1px solid #dbe4ee;font-size:13px;font-weight:700;color:#334155;">
                            {{ $this->getSelectedClinicName() ?: 'Clinic scope not selected' }}
                        </div>
                    </div>

                    <div style="display:grid;gap:12px;">
                        @forelse ($dayAgenda as $event)
                            <a href="{{ $event['url'] }}" style="display:grid;grid-template-columns:180px minmax(0,1fr) 130px;align-items:center;gap:18px;padding:16px 18px;border:1px solid {{ $event['color']['border'] }};border-radius:14px;background:{{ $event['color']['soft'] }};text-decoration:none;">
                                <div style="font-size:15px;font-weight:900;color:#0f172a;">{{ $event['time'] ?: 'Time pending' }}</div>
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <div style="font-size:15px;font-weight:900;color:#0f172a;">{{ $event['title'] }}</div>
                                    <div style="font-size:13px;color:#64748b;">{{ $event['type'] }}</div>
                                </div>
                                <div style="display:flex;justify-content:flex-end;">
                                    <span style="display:inline-flex;align-items:center;justify-content:center;min-height:32px;padding:0 12px;border-radius:999px;background:#ffffff;color:#334155;font-size:12px;font-weight:800;border:1px solid #dbe4ee;">
                                        {{ $event['status'] }}
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div style="padding:24px;border:1px dashed #dbe4ee;border-radius:14px;background:#f8fafc;font-size:14px;color:#64748b;text-align:center;">
                                No appointments scheduled for this day.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
