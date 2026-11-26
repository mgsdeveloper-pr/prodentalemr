<x-filament-panels::page>
    @php
        $submitMethod = $this->getSubmitMethodName();
    @endphp

    <div
        x-on:appointment-validation-error.window="window.scrollTo({ top: 0, behavior: 'smooth' })"
        style="display: flex; flex-direction: column; gap: 16px;"
    >
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: var(--pd-text);">Appointment Details</h2>
                <div style="font-size: 13px; line-height: 1.6; color: var(--pd-muted);">
                    Showing times in <strong>{{ $this->getDisplayTimezone() }}</strong> for <strong>{{ $this->getSelectedClinicName() }}</strong>
                    @if (filled($this->data['location_id'] ?? null))
                        at <strong>{{ $this->getCurrentLocationLabel() }}</strong>
                    @endif
                    .
                </div>
            </div>

            <a
                href="{{ $this->getBackUrl() }}"
                style="display: inline-flex; align-items: center; justify-content: center; min-width: 96px; padding: 9px 14px; border-radius: 6px; border: 1px solid var(--pwdl-border-strong); background: var(--pd-card); color: var(--pwdl-text-secondary); font-size: 13px; font-weight: 700; text-decoration: none;"
            >
                Back
            </a>
        </div>

        <div class="appointment-editor-grid" style="display: grid; grid-template-columns: minmax(0, 2.1fr) minmax(300px, 1fr); gap: 18px; align-items: start;">
            <form wire:submit="{{ $submitMethod }}" novalidate style="display: flex; flex-direction: column; gap: 18px;">
                <section style="border: 1px solid var(--pd-border); border-radius: 8px; background: var(--pd-card); overflow: hidden;">
                    <div style="padding: 18px;">
                        {{ $this->form }}
                    </div>
                </section>

                @if ($this->getMissingAppointmentRequirements() !== [])
                    <div style="padding: 11px 14px; border: 1px solid var(--pwdl-status-warning); border-radius: 6px; background: var(--pwdl-status-warning-soft); color: var(--pwdl-status-warning-text); font-size: 13px; font-weight: 700; line-height: 1.5;">
                        {{ $this->getMissingAppointmentRequirementsLabel() }}
                    </div>
                @endif

                <div style="display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap;">
                    <a
                        href="{{ $this->getCancelUrl() }}"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 96px; padding: 10px 14px; border-radius: 6px; border: 1px solid var(--pwdl-border-strong); background: var(--pd-card); color: var(--pwdl-text-secondary); font-size: 13px; font-weight: 700; text-decoration: none;"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="{{ $submitMethod }}"
                        style="display: inline-flex; align-items: center; justify-content: center; min-width: 140px; padding: 10px 14px; border: 1px solid #0f766e; border-radius: 6px; background: #0f766e; color: #ffffff; font-size: 13px; font-weight: 700; cursor: pointer;"
                    >
                        <span wire:loading.remove wire:target="{{ $submitMethod }}">{{ $this->getSubmitButtonLabel() }}</span>
                        <span wire:loading wire:target="{{ $submitMethod }}">Saving...</span>
                    </button>
                </div>
            </form>

            <aside style="display: flex; flex-direction: column; gap: 18px;">
                <section style="border: 1px solid var(--pd-border); border-radius: 8px; background: var(--pd-card); overflow: hidden;">
                    <div style="padding: 14px 18px; border-bottom: 1px solid var(--pwdl-border-subtle);">
                        <div style="font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--pd-muted);">Appointment Preview</div>
                    </div>
                    <div style="padding: 18px; display: grid; gap: 12px;">
                        <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid var(--pwdl-border-subtle); background: var(--pwdl-surface-muted);">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--pd-muted);">Patient</div>
                            <div style="margin-top: 6px; font-size: 16px; font-weight: 800; color: var(--pd-text);">{{ $this->getCurrentPatientLabel() }}</div>
                            <div style="margin-top: 4px; font-size: 12px; line-height: 1.6; color: var(--pd-muted);">{{ $this->getCurrentPatientSupportLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 8px; border: 1px solid var(--pwdl-border-subtle); background: var(--pwdl-surface-muted);">
                            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--pd-muted);">Service</div>
                            <div style="margin-top: 6px; font-size: 15px; font-weight: 800; color: var(--pd-text);">{{ $this->getCurrentVisitTypeLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 8px; border: 1px solid var(--pwdl-border-subtle); background: var(--pwdl-surface-muted);">
                            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--pd-muted);">Insurance</div>
                            <div style="margin-top: 6px; font-size: 14px; font-weight: 800; color: var(--pd-text);">{{ $this->getCurrentInsuranceLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 8px; border: 1px solid {{ $this->verificationReviewIsReady() ? 'var(--pwdl-status-success)' : 'var(--pwdl-status-warning)' }}; background: {{ $this->verificationReviewIsReady() ? 'var(--pwdl-status-success-soft)' : 'var(--pwdl-status-warning-soft)' }};">
                            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: {{ $this->verificationReviewIsReady() ? 'var(--pwdl-status-success-text)' : 'var(--pwdl-status-warning-text)' }};">Verification</div>
                            <div style="margin-top: 6px; font-size: 14px; font-weight: 800; color: {{ $this->verificationReviewIsReady() ? 'var(--pwdl-status-success-text)' : 'var(--pwdl-status-warning-text)' }};">{{ $this->getVerificationRouteLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid var(--pwdl-border-subtle); background: var(--pwdl-surface-muted);">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--pd-muted);">Doctor</div>
                            <div style="margin-top: 6px; font-size: 16px; font-weight: 800; color: var(--pd-text);">{{ $this->getCurrentProviderLabel() }}</div>
                            <div style="margin-top: 4px; font-size: 12px; line-height: 1.6; color: var(--pd-muted);">{{ $this->getCurrentLocationLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid var(--pwdl-border-subtle); background: var(--pwdl-surface-muted);">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--pd-muted);">Date & Time</div>
                            <div style="margin-top: 6px; font-size: 16px; font-weight: 800; line-height: 1.5; color: var(--pd-text);">{{ $this->getCurrentDateTimeLabel() }}</div>
                            <div style="margin-top: 4px; font-size: 12px; line-height: 1.6; color: var(--pd-muted);">{{ $this->getCurrentDurationLabel() }}</div>
                        </div>

                        <div style="padding: 14px 16px; border-radius: 12px; border: 1px solid var(--pwdl-border-subtle); background: var(--pwdl-surface-muted);">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--pd-muted);">Status</div>
                            <div style="margin-top: 6px; font-size: 16px; font-weight: 800; color: var(--pd-text);">{{ $this->getCurrentStatusLabel() }}</div>
                        </div>
                    </div>
                </section>

                <section style="border: 1px solid var(--pd-border); border-radius: 8px; background: var(--pd-card); overflow: hidden;">
                    <div style="padding: 14px 18px; border-bottom: 1px solid var(--pwdl-border-subtle);">
                        <div style="font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--pd-muted);">Provider Context</div>
                    </div>
                    <div style="padding: 18px; font-size: 13px; line-height: 1.75; color: var(--pd-muted);">
                        The calendar and available slot area will keep updating as you select the doctor and appointment date.
                        Existing bookings for that doctor are automatically excluded from the slot list.
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <style>
        @media (max-width: 900px) {
            .appointment-editor-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            .appointment-editor-grid > form,
            .appointment-editor-grid > aside {
                min-width: 0;
                width: 100%;
            }
        }
    </style>
</x-filament-panels::page>
