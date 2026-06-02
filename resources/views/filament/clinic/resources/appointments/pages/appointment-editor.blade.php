<x-filament-panels::page>
    @php
        $submitMethod = $this->getSubmitMethodName();
        $hasService = filled($this->data['appointment_type'] ?? null);
    @endphp

    <div style="display: flex; flex-direction: column; gap: 18px; background: #f5f7fb; margin: -8px; padding: 8px; border-radius: 18px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <h2 style="margin: 0; font-size: 28px; font-weight: 800; color: #0f172a;">{{ $this->getSubmitMethodName() === 'create' ? 'Add Appointment' : 'Edit Appointment' }}</h2>
                <div style="font-size: 13px; line-height: 1.6; color: #64748b;">
                    Showing times in <strong>{{ $this->getDisplayTimezone() }}</strong> for <strong>{{ $this->getSelectedClinicName() }}</strong>.
                </div>
            </div>

            <a
                href="{{ $this->getBackUrl() }}"
                style="display: inline-flex; align-items: center; justify-content: center; min-width: 108px; padding: 10px 16px; border-radius: 10px; background: #5b6fd8; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none;"
            >
                Back
            </a>
        </div>

        <div style="display: grid; grid-template-columns: minmax(0, 2.1fr) minmax(300px, 1fr); gap: 18px; align-items: start;">
            <form wire:submit="{{ $submitMethod }}" style="display: flex; flex-direction: column; gap: 18px;">
                <section style="border: 1px solid #e6ebf2; border-radius: 16px; background: #ffffff; overflow: hidden;">
                    <div style="padding: 18px;">
                        {{ $this->form }}
                    </div>
                </section>

                <div style="display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                    <a
                        href="{{ $this->getCancelUrl() }}"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 96px; padding: 11px 16px; border-radius: 8px; background: #f88181; color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none;"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 140px; padding: 11px 16px; border: 0; border-radius: 8px; background: #5b6fd8; color: #ffffff; font-size: 13px; font-weight: 700; cursor: pointer;"
                    >
                        {{ $this->getSubmitButtonLabel() }}
                    </button>
                </div>
            </form>

            <aside style="display: flex; flex-direction: column; gap: 18px;">
                <section style="border: 1px solid #e6ebf2; border-radius: 16px; background: #ffffff; overflow: hidden; min-height: 92px;">
                    <div style="padding: 18px 20px; min-height: 92px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                        @if ($hasService)
                            <div style="font-size: 13px; font-weight: 700; color: #64748b;">Selected Service</div>
                            <div style="margin-top: 8px; font-size: 18px; font-weight: 800; line-height: 1.45; color: #0f172a;">{{ $this->getCurrentVisitTypeLabel() }}</div>
                        @else
                            <div style="font-size: 14px; color: #64748b;">No Service Is Selected</div>
                        @endif
                    </div>
                </section>

                <section style="border: 1px solid #e6ebf2; border-radius: 16px; background: #ffffff; overflow: hidden;">
                    <div style="padding: 14px 18px; border-bottom: 1px solid #eef2f7;">
                        <div style="font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Booking Snapshot</div>
                    </div>
                    <div style="padding: 18px; display: grid; gap: 12px;">
                        <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid #edf2f7; background: #fafbfd;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Patient</div>
                            <div style="margin-top: 6px; font-size: 16px; font-weight: 800; color: #0f172a;">{{ $this->getCurrentPatientLabel() }}</div>
                            <div style="margin-top: 4px; font-size: 12px; line-height: 1.6; color: #64748b;">{{ $this->getCurrentPatientSupportLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid #edf2f7; background: #fafbfd;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Doctor</div>
                            <div style="margin-top: 6px; font-size: 16px; font-weight: 800; color: #0f172a;">{{ $this->getCurrentProviderLabel() }}</div>
                            <div style="margin-top: 4px; font-size: 12px; line-height: 1.6; color: #64748b;">{{ $this->getCurrentLocationLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid #edf2f7; background: #fafbfd;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Date & Time</div>
                            <div style="margin-top: 6px; font-size: 16px; font-weight: 800; line-height: 1.5; color: #0f172a;">{{ $this->getCurrentDateTimeLabel() }}</div>
                            <div style="margin-top: 4px; font-size: 12px; line-height: 1.6; color: #64748b;">{{ $this->getCurrentDurationLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid #edf2f7; background: #fafbfd;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Status</div>
                            <div style="margin-top: 6px; font-size: 16px; font-weight: 800; color: #0f172a;">{{ $this->getCurrentStatusLabel() }}</div>
                        </div>
                    </div>
                </section>

                <section style="border: 1px solid #e6ebf2; border-radius: 16px; background: #ffffff; overflow: hidden;">
                    <div style="padding: 14px 18px; border-bottom: 1px solid #eef2f7;">
                        <div style="font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b;">Provider Context</div>
                    </div>
                    <div style="padding: 18px; font-size: 13px; line-height: 1.75; color: #64748b;">
                        The calendar and available slot area will keep updating as you select the doctor and appointment date.
                        Existing bookings for that doctor are automatically excluded from the slot list.
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
