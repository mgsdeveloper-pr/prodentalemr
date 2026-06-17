<?php

namespace App\Filament\Clinic\Resources\Appointments\Pages\Concerns;

use App\Models\Appointment;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Provider;
use App\Support\AppointmentWorkspaceScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait InteractsWithAppointmentEditor
{
    public ?string $calendarMonth = null;

    public function getSelectedClinicName(): string
    {
        return AppointmentWorkspaceScope::selectedClinic()?->clinic_name ?? 'Select clinic scope';
    }

    public function getDisplayTimezone(): string
    {
        return AppointmentWorkspaceScope::selectedClinic()?->timezone ?: config('app.timezone', 'UTC');
    }

    public function getCurrentPatientLabel(): string
    {
        $patient = $this->getSelectedPatient();

        return $patient?->full_name ?: 'Patient not selected';
    }

    public function getCurrentPatientSupportLabel(): string
    {
        $patient = $this->getSelectedPatient();

        if (! $patient) {
            return 'Choose the patient for this visit.';
        }

        $bits = array_filter([
            $patient->age_label,
            $patient->phone,
            $patient->insurance_provider,
        ]);

        return filled($bits) ? implode(' • ', $bits) : 'Patient details ready for scheduling.';
    }

    public function getCurrentProviderLabel(): string
    {
        return $this->getSelectedProvider()?->display_name ?: 'Provider not selected';
    }

    public function getCurrentLocationLabel(): string
    {
        $locationId = $this->data['location_id'] ?? null;

        if (! filled($locationId)) {
            return 'Location not selected';
        }

        return Location::query()->whereKey($locationId)->value('location_name') ?: 'Location not selected';
    }

    public function getCurrentVisitTypeLabel(): string
    {
        return filled($this->data['appointment_type'] ?? null)
            ? (string) $this->data['appointment_type']
            : 'General Appointment';
    }

    public function getCurrentStatusLabel(): string
    {
        $status = $this->data['status'] ?? 'scheduled';

        return str($status)->replace('_', ' ')->title()->toString();
    }

    public function getCurrentDateTimeLabel(): string
    {
        $date = $this->data['appointment_date'] ?? null;
        $start = $this->data['start_time'] ?? null;
        $end = $this->data['end_time'] ?? null;

        if (! $date) {
            return 'Choose appointment date and time';
        }

        $formattedDate = Carbon::parse($date)->format('M d, Y');
        $formattedStart = $start ? Carbon::parse($start)->format('g:i A') : null;
        $formattedEnd = $end ? Carbon::parse($end)->format('g:i A') : null;

        if ($formattedStart && $formattedEnd) {
            return $formattedDate . ' • ' . $formattedStart . ' - ' . $formattedEnd;
        }

        if ($formattedStart) {
            return $formattedDate . ' • ' . $formattedStart;
        }

        return $formattedDate;
    }

    public function getCurrentDurationLabel(): string
    {
        $minutes = $this->data['duration_minutes'] ?? null;

        return filled($minutes) ? $minutes . ' minutes planned' : 'Duration not set yet';
    }

    public function getProviderDaySnapshot(): array
    {
        $providerId = $this->data['provider_id'] ?? null;
        $date = $this->data['appointment_date'] ?? null;

        if (! filled($providerId) || ! filled($date)) {
            return [];
        }

        $query = Appointment::query()
            ->with(['patient'])
            ->where('organization_id', AppointmentWorkspaceScope::selectedOrganizationId())
            ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
            ->where('provider_id', $providerId)
            ->whereDate('appointment_date', $date)
            ->orderBy('start_time');

        $recordId = $this->getEditingRecordId();

        if (filled($recordId)) {
            $query->whereKeyNot($recordId);
        }

        return $query
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'patient' => $appointment->patient?->full_name ?: 'Unknown patient',
                'time' => collect([
                    $appointment->start_time ? Carbon::parse($appointment->start_time)->format('g:i A') : null,
                    $appointment->end_time ? Carbon::parse($appointment->end_time)->format('g:i A') : null,
                ])->filter()->implode(' - '),
                'type' => $appointment->appointment_type ?: 'General Appointment',
                'status' => str($appointment->status)->replace('_', ' ')->title()->toString(),
            ])
            ->all();
    }

    public function getAvailableSlots(): array
    {
        $providerId = $this->data['provider_id'] ?? null;
        $date = $this->data['appointment_date'] ?? null;

        if (! filled($providerId) || ! filled($date)) {
            return [];
        }

        $duration = max((int) ($this->data['duration_minutes'] ?? 30), 15);

        $existing = Appointment::query()
            ->where('organization_id', AppointmentWorkspaceScope::selectedOrganizationId())
            ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
            ->where('provider_id', $providerId)
            ->whereDate('appointment_date', $date)
            ->when(filled($this->getEditingRecordId()), fn ($query) => $query->whereKeyNot($this->getEditingRecordId()))
            ->get(['start_time', 'end_time']);

        return $this->buildAvailableSlotsForDate($date, $duration, $existing);
    }

    public function getProviderDaySnapshotCount(): int
    {
        return count($this->getProviderDaySnapshot());
    }

    public function getCalendarMonthLabel(): string
    {
        return $this->resolveCalendarMonth()->format('F');
    }

    public function getCalendarYearLabel(): string
    {
        return $this->resolveCalendarMonth()->format('Y');
    }

    public function getCalendarWeeks(): array
    {
        $month = $this->resolveCalendarMonth();
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $selectedDate = $this->data['appointment_date'] ?? null;
        $today = now()->toDateString();
        $providerId = $this->data['provider_id'] ?? null;
        $duration = max((int) ($this->data['duration_minutes'] ?? 30), 15);
        $appointmentsByDate = $this->getProviderAppointmentsByDateForRange($providerId, $month->copy()->startOfMonth(), $month->copy()->endOfMonth());

        $weeks = [];
        $week = [];

        while ($start->lte($end)) {
            $date = $start->copy();
            $availability = $this->getCalendarDayAvailability(
                $date,
                $providerId,
                $duration,
                $appointmentsByDate,
            );

            $week[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('j'),
                'is_current_month' => $date->month === $month->month,
                'is_today' => $date->toDateString() === $today,
                'is_selected' => $date->toDateString() === $selectedDate,
                'availability_tone' => $availability['tone'],
                'availability_label' => $availability['label'],
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $start->addDay();
        }

        return $weeks;
    }

    public function getSelectedSlotLabel(): string
    {
        $start = $this->data['start_time'] ?? null;
        $end = $this->data['end_time'] ?? null;

        if (! filled($start) || ! filled($end)) {
            return 'No slot selected';
        }

        return Carbon::parse($start)->format('g:i A') . ' - ' . Carbon::parse($end)->format('g:i A');
    }

    public function previousCalendarMonth(): void
    {
        $this->calendarMonth = $this->resolveCalendarMonth()->subMonth()->startOfMonth()->toDateString();
    }

    public function nextCalendarMonth(): void
    {
        $this->calendarMonth = $this->resolveCalendarMonth()->addMonth()->startOfMonth()->toDateString();
    }

    public function selectCalendarDate(string $date): void
    {
        $this->data['appointment_date'] = $date;
        $this->calendarMonth = Carbon::parse($date)->startOfMonth()->toDateString();
        $this->data['start_time'] = null;
        $this->data['end_time'] = null;
    }

    public function selectAppointmentSlot(string $start, string $end): void
    {
        $this->data['start_time'] = $start;
        $this->data['end_time'] = $end;
        $this->data['duration_minutes'] = Carbon::parse($start)->diffInMinutes(Carbon::parse($end));
    }

    public function getSubmitMethodName(): string
    {
        return method_exists($this, 'create') ? 'create' : 'save';
    }

    public function getSubmitButtonLabel(): string
    {
        return method_exists($this, 'create') ? 'Save Appointment' : 'Save Changes';
    }

    public function getCancelUrl(): string
    {
        $resource = static::getResource();

        return $this->previousUrl ?: $resource::getUrl();
    }

    public function getBackUrl(): string
    {
        $resource = static::getResource();

        return $resource::getUrl();
    }

    protected function getSelectedPatient(): ?Patient
    {
        $patientId = $this->data['patient_id'] ?? null;

        if (! filled($patientId)) {
            return null;
        }

        return Patient::query()->find($patientId);
    }

    protected function getSelectedProvider(): ?Provider
    {
        $providerId = $this->data['provider_id'] ?? null;

        if (! filled($providerId)) {
            return null;
        }

        return Provider::query()->with('user')->find($providerId);
    }

    protected function getEditingRecordId(): ?int
    {
        if (! isset($this->record)) {
            return null;
        }

        return $this->record?->getKey();
    }

    protected function resolveCalendarMonth(): Carbon
    {
        if (filled($this->calendarMonth)) {
            return Carbon::parse($this->calendarMonth)->startOfMonth();
        }

        $baseDate = $this->data['appointment_date'] ?? now()->toDateString();
        $this->calendarMonth = Carbon::parse($baseDate)->startOfMonth()->toDateString();

        return Carbon::parse($this->calendarMonth)->startOfMonth();
    }

    protected function getProviderAppointmentsByDateForRange(?int $providerId, Carbon $from, Carbon $to): Collection
    {
        if (! filled($providerId)) {
            return collect();
        }

        return Appointment::query()
            ->where('organization_id', AppointmentWorkspaceScope::selectedOrganizationId())
            ->where('clinic_id', AppointmentWorkspaceScope::selectedClinicId())
            ->where('provider_id', $providerId)
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->when(filled($this->getEditingRecordId()), fn ($query) => $query->whereKeyNot($this->getEditingRecordId()))
            ->get(['appointment_date', 'start_time', 'end_time'])
            ->groupBy(fn (Appointment $appointment): string => (string) $appointment->appointment_date);
    }

    protected function getCalendarDayAvailability(Carbon $date, ?int $providerId, int $duration, Collection $appointmentsByDate): array
    {
        if (! filled($providerId)) {
            return ['tone' => 'idle', 'label' => null];
        }

        if ($date->isWeekend()) {
            return ['tone' => 'blocked', 'label' => null];
        }

        if ($date->isPast()) {
            return ['tone' => 'muted', 'label' => null];
        }

        $slots = $this->buildAvailableSlotsForDate(
            $date->toDateString(),
            $duration,
            $appointmentsByDate->get($date->toDateString(), collect()),
        );

        $count = count($slots);

        if ($count === 0) {
            return ['tone' => 'full', 'label' => 'Full'];
        }

        return [
            'tone' => 'open',
            'label' => $count . ' Slots',
        ];
    }

    protected function buildAvailableSlotsForDate(string $date, int $duration, Collection $existingAppointments): array
    {
        $dayStart = Carbon::parse($date . ' 09:00:00');
        $dayEnd = Carbon::parse($date . ' 17:00:00');
        $slots = [];
        $cursor = $dayStart->copy();
        $selectedStart = $this->data['start_time'] ?? null;
        $selectedEnd = $this->data['end_time'] ?? null;

        while ($cursor->copy()->addMinutes($duration)->lte($dayEnd)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($duration);

            $overlaps = $existingAppointments->contains(function (Appointment $appointment) use ($slotStart, $slotEnd, $date): bool {
                if (! filled($appointment->start_time) || ! filled($appointment->end_time)) {
                    return false;
                }

                $existingStart = Carbon::parse($date . ' ' . $appointment->start_time);
                $existingEnd = Carbon::parse($date . ' ' . $appointment->end_time);

                return $slotStart->lt($existingEnd) && $slotEnd->gt($existingStart);
            });

            if (! $overlaps) {
                $startValue = $slotStart->format('H:i:s');
                $endValue = $slotEnd->format('H:i:s');

                $slots[] = [
                    'start' => $startValue,
                    'end' => $endValue,
                    'label' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                    'is_selected' => $selectedStart === $startValue && $selectedEnd === $endValue,
                ];
            }

            $cursor->addMinutes(30);
        }

        return $slots;
    }
}
