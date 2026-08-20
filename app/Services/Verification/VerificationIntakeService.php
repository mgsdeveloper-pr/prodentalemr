<?php

namespace App\Services\Verification;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerificationIntakeService
{
    public function normalizeAndValidate(array $data, array $profile = [], array $plans = []): array
    {
        $data = $this->normalizeFromAppointment($data);

        $this->validateClinicScope($data);
        $this->validateRelatedRecords($data);
        $this->validateEnrollment($data);
        $this->validateAssignee($data);
        $this->validateRequestDetails($profile, $plans);

        return $data;
    }

    protected function normalizeFromAppointment(array $data): array
    {
        if (blank($data['appointment_id'] ?? null)) {
            return $data;
        }

        $appointment = Appointment::query()->find($data['appointment_id']);

        if (! $appointment) {
            throw ValidationException::withMessages([
                'import_appointment_id' => 'The selected appointment is no longer available.',
            ]);
        }

        foreach ([
            'organization_id',
            'clinic_id',
            'location_id',
            'patient_id',
            'provider_id',
        ] as $field) {
            if (filled($data[$field] ?? null) && (int) $data[$field] !== (int) $appointment->{$field}) {
                throw ValidationException::withMessages([
                    $field => 'The selected appointment does not match the current request details.',
                ]);
            }

            $data[$field] ??= $appointment->{$field};
        }

        return $data;
    }

    protected function validateClinicScope(array $data): void
    {
        if (blank($data['organization_id'] ?? null) || blank($data['clinic_id'] ?? null)) {
            throw ValidationException::withMessages([
                'location_id' => 'Select a clinic location before creating the request.',
            ]);
        }

        $clinicMatches = Clinic::query()
            ->whereKey($data['clinic_id'])
            ->where('organization_id', $data['organization_id'])
            ->exists();

        if (! $clinicMatches) {
            throw ValidationException::withMessages([
                'clinic_id' => 'The selected clinic does not belong to this organization.',
            ]);
        }
    }

    protected function validateRelatedRecords(array $data): void
    {
        $organizationId = (int) $data['organization_id'];
        $clinicId = (int) $data['clinic_id'];
        $locationId = filled($data['location_id'] ?? null) ? (int) $data['location_id'] : null;
        $patientId = filled($data['patient_id'] ?? null) ? (int) $data['patient_id'] : null;

        if ($locationId && ! Location::query()->whereKey($locationId)->where('clinic_id', $clinicId)->exists()) {
            throw ValidationException::withMessages([
                'location_id' => 'The selected location does not belong to this clinic.',
            ]);
        }

        if (filled($data['provider_id'] ?? null)) {
            $providerMatches = Provider::query()
                ->whereKey($data['provider_id'])
                ->where('organization_id', $organizationId)
                ->where('clinic_id', $clinicId)
                ->when($locationId, fn ($query) => $query->where(function ($inner) use ($locationId): void {
                    $inner->whereNull('location_id')->orWhere('location_id', $locationId);
                }))
                ->exists();

            if (! $providerMatches) {
                throw ValidationException::withMessages([
                    'provider_id' => 'The selected provider does not belong to this clinic/location.',
                ]);
            }
        }

        if ($patientId) {
            $patientMatches = Patient::query()
                ->whereKey($patientId)
                ->where('organization_id', $organizationId)
                ->where('clinic_id', $clinicId)
                ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
                ->exists();

            if (! $patientMatches) {
                throw ValidationException::withMessages([
                    'patient_id' => 'The selected patient does not belong to this clinic/location.',
                ]);
            }
        }

        if (filled($data['patient_insurance_policy_id'] ?? null)) {
            $policyMatches = PatientInsurancePolicy::query()
                ->whereKey($data['patient_insurance_policy_id'])
                ->where('organization_id', $organizationId)
                ->where('clinic_id', $clinicId)
                ->when($patientId, fn ($query) => $query->where('patient_id', $patientId))
                ->when($locationId, fn ($query) => $query->where(function ($inner) use ($locationId): void {
                    $inner->whereNull('location_id')->orWhere('location_id', $locationId);
                }))
                ->exists();

            if (! $policyMatches) {
                throw ValidationException::withMessages([
                    'patient_insurance_policy_id' => 'The selected insurance policy does not match this patient and clinic.',
                ]);
            }
        }
    }

    protected function validateEnrollment(array $data): void
    {
        if (filled($data['client_service_enrollment_id'] ?? null)) {
            $enrollmentMatches = ClientServiceEnrollment::query()
                ->whereKey($data['client_service_enrollment_id'])
                ->where('organization_id', $data['organization_id'])
                ->where('clinic_id', $data['clinic_id'])
                ->where('status', 'active')
                ->when(filled($data['location_id'] ?? null), fn ($query) => $query->where(function ($inner) use ($data): void {
                    $inner->whereNull('location_id')->orWhere('location_id', $data['location_id']);
                }))
                ->whereHas('managedBillingService', fn ($query) => $query->where('category', 'verification')->where('status', true))
                ->exists();

            if (! $enrollmentMatches) {
                throw ValidationException::withMessages([
                    'location_id' => 'The verification service setup for this clinic/location is not active.',
                ]);
            }
        }

        $serviceMatches = ManagedBillingService::query()
            ->whereKey($data['managed_billing_service_id'] ?? null)
            ->where('category', 'verification')
            ->where('status', true)
            ->exists();

        if (! $serviceMatches) {
            throw ValidationException::withMessages([
                'location_id' => 'No active verification service is configured for this request.',
            ]);
        }
    }

    protected function validateAssignee(array $data): void
    {
        if (blank($data['assigned_to'] ?? null)) {
            return;
        }

        $assignee = User::query()->whereKey($data['assigned_to'])->where('status', true)->first();

        if (! $assignee || ! $assignee->canAccessVerificationWorkspace()) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Select an active user who can access the Verification workspace.',
            ]);
        }
    }

    protected function validateRequestDetails(array $profile, array $plans): void
    {
        if ($profile === [] && $plans === []) {
            return;
        }

        $messages = [];

        foreach ([
            'patient_full_name' => 'Patient full name is required.',
            'patient_dob' => 'Patient date of birth is required.',
            'appointment_date' => 'Appointment date is required.',
            'form_type' => 'Select Full Form or Short Form.',
        ] as $field => $message) {
            if (blank($profile[$field] ?? null)) {
                $messages['vf_' . $field] = $message;
            }
        }

        if (! in_array($profile['form_type'] ?? null, ['full_form', 'short_form'], true)) {
            $messages['vf_form_type'] = 'Select a valid verification form type.';
        }

        if ($plans === [] || collect($plans)->every(fn ($plan): bool => blank($plan['payer_name'] ?? null))) {
            $messages['verification_plan_snapshots'] = 'Add at least one insurance plan with an insurance provider.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }
}
