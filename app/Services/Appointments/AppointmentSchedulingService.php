<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicOperatory;
use App\Models\ClinicService;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentSchedulingService
{
    public function __construct(private readonly AppointmentAvailabilityService $availability) {}

    public function validateAndNormalize(array $data, ?Appointment $appointment = null): array
    {
        $this->validateRelatedRecords($data);
        $data = $this->normalizeService($data);
        $data = $this->normalizeTiming($data);
        $this->validateAvailability($data);
        $this->validateConflicts($data, $appointment);

        return $data;
    }

    protected function validateRelatedRecords(array $data): void
    {
        $organizationId = (int) ($data['organization_id'] ?? 0);
        $clinicId = (int) ($data['clinic_id'] ?? 0);
        $locationId = (int) ($data['location_id'] ?? 0);
        $messages = [];

        if (! Location::query()
            ->whereKey($locationId)
            ->where('clinic_id', $clinicId)
            ->where('status', true)
            ->exists()) {
            $messages['location_id'] = 'Select an active location from this clinic.';
        }

        if (! Provider::query()
            ->whereKey($data['provider_id'] ?? null)
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where(function ($query) use ($locationId): void {
                $query->whereNull('location_id')->orWhere('location_id', $locationId);
            })
            ->where('status', true)
            ->exists()) {
            $messages['provider_id'] = 'Select an active provider available at this location.';
        }

        if (! Patient::query()
            ->whereKey($data['patient_id'] ?? null)
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where('status', true)
            ->exists()) {
            $messages['patient_id'] = 'Select an active patient from this clinic.';
        }

        if (filled($data['clinic_service_id'] ?? null) && ! ClinicService::query()
            ->whereKey($data['clinic_service_id'])
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where(function ($query) use ($locationId): void {
                $query->whereNull('location_id')->orWhere('location_id', $locationId);
            })
            ->where('status', true)
            ->exists()) {
            $messages['clinic_service_id'] = 'Select an active service available at this location.';
        }

        if (filled($data['clinic_operatory_id'] ?? null) && ! ClinicOperatory::query()
            ->whereKey($data['clinic_operatory_id'])
            ->where('clinic_id', $clinicId)
            ->where('location_id', $locationId)
            ->where('status', true)
            ->exists()) {
            $messages['clinic_operatory_id'] = 'Select an active operatory from this location.';
        }

        if (filled($data['patient_insurance_policy_id'] ?? null) && ! PatientInsurancePolicy::query()
            ->whereKey($data['patient_insurance_policy_id'])
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where('patient_id', $data['patient_id'] ?? null)
            ->where('status', true)
            ->exists()) {
            $messages['patient_insurance_policy_id'] = 'Select an active insurance policy belonging to this patient.';
        }

        if (filled($data['parent_appointment_id'] ?? null) && ! Appointment::query()
            ->whereKey($data['parent_appointment_id'])
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where('patient_id', $data['patient_id'] ?? null)
            ->exists()) {
            $messages['parent_appointment_id'] = 'Select a previous appointment belonging to this patient.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    protected function normalizeService(array $data): array
    {
        if (blank($data['clinic_service_id'] ?? null)) {
            return $data;
        }

        $service = ClinicService::query()->find($data['clinic_service_id']);

        if ($service) {
            $data['appointment_type'] = $service->name;
            $data['duration_minutes'] = (int) ($data['duration_minutes'] ?: $service->default_duration_minutes ?: 30);
        }

        return $data;
    }

    protected function normalizeTiming(array $data): array
    {
        if (blank($data['appointment_date'] ?? null) || blank($data['start_time'] ?? null) || blank($data['end_time'] ?? null)) {
            throw ValidationException::withMessages([
                'start_time' => 'Select an appointment date and available time slot.',
            ]);
        }

        $start = Carbon::parse($data['appointment_date'].' '.$data['start_time']);
        $end = Carbon::parse($data['appointment_date'].' '.$data['end_time']);

        if (! $end->gt($start)) {
            throw ValidationException::withMessages([
                'start_time' => 'The appointment end time must be after its start time.',
            ]);
        }

        $data['duration_minutes'] = (int) $start->diffInMinutes($end);

        return $data;
    }

    protected function validateConflicts(array $data, ?Appointment $appointment): void
    {
        $clinic = Clinic::query()->find($data['clinic_id']);
        $provider = Provider::query()->find($data['provider_id']);
        $timezone = $clinic?->timezone ?: config('app.timezone', 'UTC');
        $buffer = max((int) ($provider?->scheduling_buffer_minutes ?? 0), 0);
        $requestedStart = Carbon::parse($data['appointment_date'].' '.$data['start_time'], $timezone);
        $requestedEnd = Carbon::parse($data['appointment_date'].' '.$data['end_time'], $timezone);

        $query = Appointment::query()
            ->where('organization_id', $data['organization_id'])
            ->where('clinic_id', $data['clinic_id'])
            ->whereDate('appointment_date', $data['appointment_date'])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($appointment) {
            $query->whereKeyNot($appointment->getKey());
        }

        $providerConflict = Appointment::query()
            ->where('organization_id', $data['organization_id'])
            ->where('clinic_id', $data['clinic_id'])
            ->where('provider_id', $data['provider_id'])
            ->whereDate('appointment_date', $data['appointment_date'])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($appointment, fn ($providerQuery) => $providerQuery->whereKeyNot($appointment->getKey()))
            ->get(['start_time', 'end_time'])
            ->contains(function (Appointment $existing) use ($requestedStart, $requestedEnd, $data, $timezone, $buffer): bool {
                $existingStart = Carbon::parse($data['appointment_date'].' '.$existing->start_time, $timezone)->subMinutes($buffer);
                $existingEnd = Carbon::parse($data['appointment_date'].' '.$existing->end_time, $timezone)->addMinutes($buffer);

                return $requestedStart->lt($existingEnd) && $requestedEnd->gt($existingStart);
            });

        if ($providerConflict) {
            throw ValidationException::withMessages([
                'start_time' => 'This provider already has an appointment during the selected time.',
            ]);
        }

        if (filled($data['clinic_operatory_id'] ?? null)
            && (clone $query)->where('clinic_operatory_id', $data['clinic_operatory_id'])->exists()) {
            throw ValidationException::withMessages([
                'clinic_operatory_id' => 'This operatory is already booked during the selected time.',
            ]);
        }
    }

    protected function validateAvailability(array $data): void
    {
        $clinic = Clinic::query()->find($data['clinic_id']);
        $location = Location::query()->find($data['location_id']);
        $provider = Provider::query()->find($data['provider_id']);
        $timezone = $clinic?->timezone ?: config('app.timezone', 'UTC');
        $operatory = filled($data['clinic_operatory_id'] ?? null)
            ? ClinicOperatory::query()->find($data['clinic_operatory_id'])
            : null;
        $context = $this->availability->context($clinic, $location, $provider, $operatory);
        $window = $this->availability->operatingWindow($data['appointment_date'], $timezone, $context);
        $start = Carbon::parse($data['appointment_date'].' '.$data['start_time'], $timezone);
        $end = Carbon::parse($data['appointment_date'].' '.$data['end_time'], $timezone);

        if (! $window || $start->lt($window[0]) || $end->gt($window[1])) {
            throw ValidationException::withMessages([
                'start_time' => 'Select a time within this provider and location schedule.',
            ]);
        }

        if ($this->availability->overlapsException($start, $end, $context)) {
            throw ValidationException::withMessages([
                'start_time' => 'This time is unavailable because of a closure or provider leave.',
            ]);
        }

        if ($this->availability->overlapsBreak($start, $end, $context)) {
            throw ValidationException::withMessages([
                'start_time' => 'This time overlaps a configured break.',
            ]);
        }
    }
}
