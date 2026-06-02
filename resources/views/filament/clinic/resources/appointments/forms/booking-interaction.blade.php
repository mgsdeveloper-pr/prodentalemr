<div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start;">
    <div>
        <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; color: #0f172a;">Appointment Date<span style="color: #dc2626;">*</span></div>
        <div style="border: 1px solid #dbe4ee; border-radius: 10px; background: #ffffff; overflow: hidden;">
            <div style="padding: 12px 14px; border-bottom: 1px solid #e8edf3; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <button type="button" wire:click="previousCalendarMonth" style="width: 28px; height: 28px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #64748b; cursor: pointer;">&lsaquo;</button>
                <div style="display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 700; color: #64748b;">
                    <span>{{ $calendarMonthLabel }}</span>
                    <span>{{ $calendarYearLabel }}</span>
                </div>
                <button type="button" wire:click="nextCalendarMonth" style="width: 28px; height: 28px; border-radius: 999px; border: 1px solid #dbe4ee; background: #ffffff; color: #64748b; cursor: pointer;">&rsaquo;</button>
            </div>

            <div style="padding: 12px;">
                <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; margin-bottom: 8px;">
                    @foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $dayLabel)
                        <div style="text-align: center; font-size: 11px; font-weight: 700; color: #64748b;">{{ $dayLabel }}</div>
                    @endforeach
                </div>

                <div style="display: grid; gap: 6px;">
                    @foreach ($calendarWeeks as $week)
                        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px;">
                            @foreach ($week as $day)
                                @php
                                    $tone = $day['availability_tone'] ?? 'idle';
                                    $styles = match ($tone) {
                                        'open' => [
                                            'border' => '#bfded0',
                                            'background' => '#dcefe6',
                                            'color' => '#166534',
                                            'meta' => '#15803d',
                                        ],
                                        'full' => [
                                            'border' => '#fcd7a4',
                                            'background' => '#fff1d6',
                                            'color' => '#c2410c',
                                            'meta' => '#ea580c',
                                        ],
                                        'blocked' => [
                                            'border' => '#f4bfd5',
                                            'background' => '#fce7f3',
                                            'color' => '#be185d',
                                            'meta' => '#db2777',
                                        ],
                                        'muted' => [
                                            'border' => '#e6ebf2',
                                            'background' => '#f4f7fb',
                                            'color' => '#cbd5e1',
                                            'meta' => '#cbd5e1',
                                        ],
                                        default => [
                                            'border' => '#e6ebf2',
                                            'background' => '#ffffff',
                                            'color' => '#64748b',
                                            'meta' => '#94a3b8',
                                        ],
                                    };

                                    if ($day['is_selected']) {
                                        $styles = [
                                            'border' => '#4f6edb',
                                            'background' => '#5b6fd8',
                                            'color' => '#ffffff',
                                            'meta' => '#dbe7ff',
                                        ];
                                    }
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectCalendarDate('{{ $day['date'] }}')"
                                    style="
                                        min-height: 74px;
                                        border-radius: 8px;
                                        border: 1px solid {{ $styles['border'] }};
                                        background: {{ $styles['background'] }};
                                        color: {{ $styles['color'] }};
                                        font-size: 14px;
                                        font-weight: {{ $day['is_selected'] ? '800' : '700' }};
                                        cursor: pointer;
                                        box-shadow: {{ $day['is_selected'] ? 'inset 0 0 0 1px #4f6edb' : 'none' }};
                                        display: flex;
                                        flex-direction: column;
                                        align-items: center;
                                        justify-content: center;
                                        gap: 4px;
                                    "
                                >
                                    <span>{{ $day['label'] }}</span>
                                    @if (filled($day['availability_label'] ?? null))
                                        <span style="font-size: 10px; font-weight: 700; color: {{ $styles['meta'] }};">
                                            {{ $day['availability_label'] }}
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div>
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px;">
            <div style="font-size: 12px; font-weight: 800; color: #0f172a;">Available Slot<span style="color: #dc2626;">*</span></div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div style="font-size: 11px; font-weight: 700; color: #64748b;">{{ $selectedDuration }} min slots</div>
                <div style="font-size: 11px; color: #64748b;">@Showing times in: <strong>{{ $displayTimezone }}</strong></div>
            </div>
        </div>
        <div style="min-height: 320px; border: 1px solid #e6ebf2; border-radius: 10px; background: #f7f9fc; overflow: hidden;">
            @if (count($availableSlots))
                <div style="padding: 14px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                    @foreach ($availableSlots as $slot)
                        <button
                            type="button"
                            wire:click="selectAppointmentSlot('{{ $slot['start'] }}', '{{ $slot['end'] }}')"
                            style="
                                padding: 14px 12px;
                                border-radius: 10px;
                                border: 1px solid {{ $slot['is_selected'] ? '#4f6edb' : '#dbe4ee' }};
                                background: {{ $slot['is_selected'] ? '#eef2ff' : '#ffffff' }};
                                color: {{ $slot['is_selected'] ? '#3559c7' : '#334155' }};
                                font-size: 13px;
                                font-weight: 700;
                                text-align: center;
                                cursor: pointer;
                            "
                        >
                            {{ $slot['label'] }}
                        </button>
                    @endforeach
                </div>
            @else
                <div style="min-height: 320px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 18px; font-size: 14px; line-height: 1.7; color: #64748b;">
                    No available slots for the selected date and doctor.
                </div>
            @endif
        </div>
        <div style="margin-top: 10px; font-size: 12px; line-height: 1.6; color: #64748b;">
            Selected slot: <strong>{{ $selectedSlotLabel }}</strong>
        </div>
    </div>
</div>
