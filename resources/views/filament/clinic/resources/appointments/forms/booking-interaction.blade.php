<style>
    .pd-booking-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:16px; align-items:start; }
    @media (max-width: 900px) { .pd-booking-grid { grid-template-columns:1fr; } }
</style>
<div class="pd-booking-grid">
    <div>
        <div style="margin-bottom: 8px; font-size: 12px; font-weight: 800; color: var(--pd-text);">Appointment Date<span style="color: var(--pwdl-status-danger-text);">*</span></div>
        <div style="border: 1px solid var(--pd-border); border-radius: 10px; background: var(--pd-card); overflow: hidden;">
            <div style="padding: 12px 14px; border-bottom: 1px solid var(--pwdl-border-subtle); display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <button type="button" wire:click="previousCalendarMonth" aria-label="Previous month" style="width: 28px; height: 28px; border-radius: 999px; border: 1px solid var(--pd-border); background: var(--pd-card); color: var(--pd-muted); cursor: pointer;">&lsaquo;</button>
                <div style="display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 700; color: var(--pd-muted);">
                    <span>{{ $calendarMonthLabel }}</span>
                    <span>{{ $calendarYearLabel }}</span>
                </div>
                <button type="button" wire:click="nextCalendarMonth" aria-label="Next month" style="width: 28px; height: 28px; border-radius: 999px; border: 1px solid var(--pd-border); background: var(--pd-card); color: var(--pd-muted); cursor: pointer;">&rsaquo;</button>
            </div>

            <div style="padding: 12px;">
                <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; margin-bottom: 8px;">
                    @foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $dayLabel)
                        <div style="text-align: center; font-size: 11px; font-weight: 700; color: var(--pd-muted);">{{ $dayLabel }}</div>
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
                                            'border' => 'var(--pwdl-status-success)',
                                            'background' => 'var(--pwdl-status-success-soft)',
                                            'color' => 'var(--pwdl-status-success-text)',
                                            'meta' => 'var(--pwdl-status-success-text)',
                                        ],
                                        'full' => [
                                            'border' => 'var(--pwdl-status-warning)',
                                            'background' => 'var(--pwdl-status-warning-soft)',
                                            'color' => 'var(--pwdl-status-warning-text)',
                                            'meta' => 'var(--pwdl-status-warning-text)',
                                        ],
                                        'blocked' => [
                                            'border' => 'var(--pwdl-status-danger)',
                                            'background' => 'var(--pwdl-status-danger-soft)',
                                            'color' => 'var(--pwdl-status-danger-text)',
                                            'meta' => 'var(--pwdl-status-danger-text)',
                                        ],
                                        'muted' => [
                                            'border' => 'var(--pwdl-border-subtle)',
                                            'background' => 'var(--pwdl-surface-muted)',
                                            'color' => 'var(--pwdl-border-strong)',
                                            'meta' => 'var(--pwdl-border-strong)',
                                        ],
                                        default => [
                                            'border' => 'var(--pwdl-border-subtle)',
                                            'background' => 'var(--pd-card)',
                                            'color' => 'var(--pd-muted)',
                                            'meta' => 'var(--pwdl-text-placeholder)',
                                        ],
                                    };

                                    if ($day['is_selected']) {
                                        $styles = [
                                            'border' => '#0f766e',
                                            'background' => '#0f766e',
                                            'color' => '#ffffff',
                                            'meta' => '#dbe7ff',
                                        ];
                                    }
                                @endphp
                                <button
                                    type="button"
                                    wire:click="selectCalendarDate('{{ $day['date'] }}')"
                                    @disabled($day['is_disabled'])
                                    aria-disabled="{{ $day['is_disabled'] ? 'true' : 'false' }}"
                                    style="
                                        min-height: 74px;
                                        border-radius: 8px;
                                        border: 1px solid {{ $styles['border'] }};
                                        background: {{ $styles['background'] }};
                                        color: {{ $styles['color'] }};
                                        font-size: 14px;
                                        font-weight: {{ $day['is_selected'] ? '800' : '700' }};
                                        cursor: {{ $day['is_disabled'] ? 'not-allowed' : 'pointer' }};
                                        opacity: {{ $day['is_disabled'] ? '0.68' : '1' }};
                                        box-shadow: {{ $day['is_selected'] ? 'inset 0 0 0 1px #0f766e' : 'none' }};
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
            <div style="font-size: 12px; font-weight: 800; color: var(--pd-text);">Available Slot<span style="color: var(--pwdl-status-danger-text);">*</span></div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div style="font-size: 11px; font-weight: 700; color: var(--pd-muted);">{{ $selectedDuration }} min slots</div>
                <div style="font-size: 11px; color: var(--pd-muted);">Showing times in <strong>{{ $displayTimezone }}</strong></div>
            </div>
        </div>
        <div style="min-height: 320px; border: 1px solid var(--pwdl-border-subtle); border-radius: 10px; background: var(--pwdl-surface-muted); overflow: hidden;">
            @if (count($availableSlots))
                <div style="padding: 14px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                    @foreach ($availableSlots as $slot)
                        <button
                            type="button"
                            wire:click="selectAppointmentSlot('{{ $slot['start'] }}', '{{ $slot['end'] }}')"
                            style="
                                padding: 14px 12px;
                                border-radius: 10px;
                                border: 1px solid {{ $slot['is_selected'] ? '#0f766e' : 'var(--pd-border)' }};
                                background: {{ $slot['is_selected'] ? 'var(--pwdl-status-success-soft)' : 'var(--pd-card)' }};
                                color: {{ $slot['is_selected'] ? 'var(--pwdl-status-success-text)' : 'var(--pwdl-text-secondary)' }};
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
                <div style="min-height: 320px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 18px; font-size: 14px; line-height: 1.7; color: var(--pd-muted);">
                    {{ $availabilityMessage }}
                </div>
            @endif
        </div>
        <div style="margin-top: 10px; font-size: 12px; line-height: 1.6; color: var(--pd-muted);">
            Selected slot: <strong>{{ $selectedSlotLabel }}</strong>
        </div>
        @error('data.start_time')
            <div role="alert" style="margin-top: 8px; padding: 10px 12px; border: 1px solid var(--pwdl-status-danger); border-radius: 6px; background: var(--pwdl-status-danger-soft); color: var(--pwdl-status-danger-text); font-size: 12px; font-weight: 700; line-height: 1.5;">
                {{ $message }} Please choose an available slot and save again.
            </div>
        @enderror
    </div>
</div>
