<x-filament-panels::page>
    @php
        $stats = $this->getAppointmentStats();
        $isVerificationWorkspace = array_key_exists('not_sent', $stats);
        $canCreateAppointments = method_exists($this, 'canCreateAppointments')
            ? $this->canCreateAppointments()
            : (auth()->user()?->canCreateClinicAppointments() ?? false);
        $canImportAppointments = method_exists($this, 'canImportAppointments') && $this->canImportAppointments();
        $importUrl = method_exists($this, 'getImportUrl') ? $this->getImportUrl() : null;
        $calendarUrl = method_exists($this, 'getCalendarUrl') ? $this->getCalendarUrl() : null;
        $dateOptions = method_exists($this, 'getDashboardDatePresetOptions') ? $this->getDashboardDatePresetOptions() : [];
        $dateRangeLabel = method_exists($this, 'getDashboardDateRangeLabel') ? $this->getDashboardDateRangeLabel() : null;
        $pageTitle = method_exists($this, 'getAppointmentPageTitle')
            ? $this->getAppointmentPageTitle()
            : 'Schedule overview';
        $pageDescription = method_exists($this, 'getAppointmentPageDescription')
            ? $this->getAppointmentPageDescription()
            : 'Review appointments, patient details, provider assignments, and verification progress for the selected period.';
        $statItems = $isVerificationWorkspace
            ? [
                ['label' => 'Not sent', 'value' => $stats['not_sent'], 'tone' => 'neutral'],
                ['label' => 'Sent', 'value' => $stats['sent'], 'tone' => 'blue'],
                ['label' => 'In progress', 'value' => $stats['in_progress'], 'tone' => 'amber'],
                ['label' => 'Completed', 'value' => $stats['completed'], 'tone' => 'green'],
                ['label' => 'Cancelled', 'value' => $stats['cancelled'], 'tone' => 'red'],
            ]
            : [
                ['label' => 'Upcoming', 'value' => $stats['upcoming'], 'tone' => 'neutral'],
                ['label' => 'Today', 'value' => $stats['today'], 'tone' => 'blue'],
                ['label' => 'Completed', 'value' => $stats['completed'], 'tone' => 'green'],
                ['label' => 'Scheduled', 'value' => $stats['pending'], 'tone' => 'amber'],
                ['label' => 'Cancelled', 'value' => $stats['cancelled'], 'tone' => 'red'],
            ];
    @endphp

    <style>
        .appointment-workspace { display: flex; flex-direction: column; gap: 16px; }
        .appointment-panel { overflow: hidden; border: 1px solid #dbe4ee; border-radius: 8px; background: #fff; }
        .appointment-overview { padding: 22px 24px; }
        .appointment-overview__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; }
        .appointment-overview__copy { min-width: 0; }
        .appointment-overview__title { margin: 0; color: #0f172a; font-size: 20px; line-height: 1.25; font-weight: 800; }
        .appointment-overview__description { margin: 6px 0 0; max-width: 760px; color: #64748b; font-size: 14px; line-height: 1.6; }
        .appointment-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
        .appointment-button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; gap: 7px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none; }
        .appointment-button:hover { border-color: #94a3b8; background: #f8fafc; }
        .appointment-button--primary { border-color: #0f766e; background: #0f766e; color: #fff; }
        .appointment-button--primary:hover { border-color: #115e59; background: #115e59; }
        .appointment-button svg { width: 17px; height: 17px; }
        .appointment-toolbar { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-top: 20px; padding-top: 18px; border-top: 1px solid #e5e7eb; }
        .appointment-range { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
        .appointment-field { display: flex; min-width: 170px; flex-direction: column; gap: 6px; }
        .appointment-field label { color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .appointment-field select, .appointment-field input { min-height: 40px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; padding: 8px 10px; color: #0f172a; font-size: 13px; font-weight: 600; outline: none; }
        .appointment-range__summary { padding-bottom: 10px; color: #475569; font-size: 13px; font-weight: 600; }
        .appointment-scope { align-self: center; color: #64748b; font-size: 12px; }
        .appointment-scope strong { color: #334155; font-weight: 700; }
        .appointment-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); margin-top: 18px; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
        .appointment-stat { min-width: 0; padding: 13px 16px; border-right: 1px solid #e2e8f0; }
        .appointment-stat:last-child { border-right: 0; }
        .appointment-stat__label { color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .appointment-stat__value { margin-top: 3px; color: #0f172a; font-size: 22px; line-height: 1.2; font-weight: 800; }
        .appointment-stat--blue .appointment-stat__value { color: #1d4ed8; }
        .appointment-stat--green .appointment-stat__value { color: #047857; }
        .appointment-stat--amber .appointment-stat__value { color: #b45309; }
        .appointment-stat--red .appointment-stat__value { color: #b91c1c; }
        .appointment-list__header { padding: 18px 20px 0; }
        .appointment-list__title { margin: 0; color: #0f172a; font-size: 16px; font-weight: 800; }
        .appointment-list__description { margin: 4px 0 0; color: #64748b; font-size: 13px; line-height: 1.5; }
        .appointment-list__table { padding: 12px 20px 20px; }

        @media (max-width: 900px) {
            .appointment-overview__header, .appointment-toolbar { align-items: stretch; flex-direction: column; }
            .appointment-actions { justify-content: flex-start; }
            .appointment-scope { align-self: flex-start; }
            .appointment-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .appointment-stat { border-bottom: 1px solid #e2e8f0; }
            .appointment-stat:nth-child(2n) { border-right: 0; }
            .appointment-stat:last-child { grid-column: 1 / -1; border-bottom: 0; }
        }

        @media (max-width: 560px) {
            .appointment-overview { padding: 18px; }
            .appointment-actions, .appointment-range { display: grid; grid-template-columns: 1fr; }
            .appointment-button, .appointment-field { width: 100%; }
            .appointment-stats { grid-template-columns: 1fr; }
            .appointment-stat, .appointment-stat:nth-child(2n) { border-right: 0; border-bottom: 1px solid #e2e8f0; }
            .appointment-stat:last-child { grid-column: auto; }
            .appointment-list__header { padding: 18px 16px 0; }
            .appointment-list__table { padding: 10px 12px 16px; }
        }
    </style>

    <div class="appointment-workspace">
        <section class="appointment-panel" wire:poll.visible.30s>
            <div class="appointment-overview">
                <div class="appointment-overview__header">
                    <div class="appointment-overview__copy">
                        <h2 class="appointment-overview__title">{{ $pageTitle }}</h2>
                        <p class="appointment-overview__description">{{ $pageDescription }}</p>
                    </div>

                    <div class="appointment-actions">
                        @if (filled($calendarUrl))
                            <a class="appointment-button" href="{{ $calendarUrl }}">
                                <x-filament::icon icon="heroicon-o-calendar-days" />
                                Calendar
                            </a>
                        @endif

                        @if ($canImportAppointments && filled($importUrl))
                            <a class="appointment-button" href="{{ $importUrl }}">
                                <x-filament::icon icon="heroicon-o-arrow-up-tray" />
                                Import
                            </a>
                        @endif

                        @if ($canCreateAppointments)
                            <a class="appointment-button appointment-button--primary" href="{{ $this->getCreateUrl() }}">
                                <x-filament::icon icon="heroicon-o-plus" />
                                Add Appointment
                            </a>
                        @endif
                    </div>
                </div>

                <div class="appointment-toolbar">
                    <div class="appointment-range">
                        <div class="appointment-field">
                            <label for="appointment-date-preset">Date range</label>
                            <select id="appointment-date-preset" wire:model.live="appointmentDatePreset">
                                @foreach ($dateOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($this->appointmentDatePreset === 'custom')
                            <div class="appointment-field">
                                <label for="appointment-date-from">From</label>
                                <input id="appointment-date-from" type="date" wire:model.live="customDateFrom">
                            </div>
                            <div class="appointment-field">
                                <label for="appointment-date-to">To</label>
                                <input id="appointment-date-to" type="date" wire:model.live="customDateTo">
                            </div>
                        @endif

                        <div class="appointment-range__summary">{{ $dateRangeLabel }}</div>
                    </div>

                    <div class="appointment-scope">
                        Clinic: <strong>{{ $this->getSelectedClinicName() ?: 'No clinic selected' }}</strong>
                    </div>
                </div>

                <div class="appointment-stats" aria-label="Appointment summary">
                    @foreach ($statItems as $stat)
                        <div class="appointment-stat appointment-stat--{{ $stat['tone'] }}">
                            <div class="appointment-stat__label">{{ $stat['label'] }}</div>
                            <div class="appointment-stat__value">{{ $stat['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="appointment-panel">
            <div class="appointment-list__header">
                <h3 class="appointment-list__title">Appointment list</h3>
                <p class="appointment-list__description">Results for {{ $dateRangeLabel }}. Search or filter to narrow the schedule.</p>
            </div>
            <div class="appointment-list__table">
                {{ $this->table }}
            </div>
        </section>
    </div>
</x-filament-panels::page>
