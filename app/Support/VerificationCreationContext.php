<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class VerificationCreationContext
{
    public static function clinicId(bool $clinicPanel = false): ?int
    {
        return $clinicPanel ? ClinicPanelScope::selectedClinicId() : AdminClinicScope::selectedClinicId();
    }

    public static function scope(Builder $query, bool $clinicPanel = false): Builder
    {
        if (! auth()->user()) {
            return $query->whereRaw('1 = 0');
        }
        $clinicId = static::clinicId($clinicPanel);
        if ($clinicPanel) {
            return $query->where('clinic_id', $clinicId ?? 0);
        }

        $query = AdminClinicScope::apply($query);

        return $clinicId ? $query->where('clinic_id', $clinicId) : $query;
    }

    public static function locations(): array
    {
        return static::scope(Location::query())->with('clinic')->orderBy('location_name')->get()
            ->mapWithKeys(fn (Location $location): array => [$location->id => static::clinicId()
                ? $location->location_name
                : $location->clinic?->clinic_name . ' / ' . $location->location_name])->all();
    }

    public static function appointments(?int $clinicId, ?int $locationId, bool $includePast = false, bool $clinicPanel = false, ?int $patientId = null): array
    {
        $clinicId = static::clinicId($clinicPanel) ?? $clinicId;
        if (! $clinicId) {
            return [];
        }

        $timezone = Clinic::find($clinicId)?->timezone ?: config('app.timezone');
        $today = now($timezone)->toDateString();
        $appointments = static::scope(Appointment::query(), $clinicPanel)
            ->with(['patient', 'provider.user', 'location'])
            ->where('clinic_id', $clinicId)
            ->when($patientId, fn (Builder $query) => $query->where('patient_id', $patientId))
            ->when($locationId, fn (Builder $query) => $query->where('location_id', $locationId))
            ->when(! $includePast, fn (Builder $query) => $query->whereDate('appointment_date', '>=', $today))
            ->orderByRaw('case when appointment_date >= ? then 0 else 1 end', [$today])
            ->orderBy('appointment_date')->orderBy('start_time')->limit(100)->get();
        $duplicates = $appointments->countBy(fn (Appointment $a) => $a->patient_id . ':' . $a->appointment_date?->toDateString());
        $linked = BillingWorkItem::query()->whereIn('appointment_id', $appointments->modelKeys())
            ->where('status', '!=', 'cancelled')->pluck('appointment_id')->all();

        return $appointments->mapWithKeys(function (Appointment $a) use ($duplicates, $linked, $today): array {
            $key = $a->patient_id . ':' . $a->appointment_date?->toDateString();
            $time = $duplicates[$key] > 1 && $a->start_time
                ? \Carbon\Carbon::parse($a->start_time)->format('g:i A') : null;

            return [$a->id => collect([
                $a->appointment_date?->format('M d, Y'), $a->patient?->full_name,
                $a->provider?->display_name, $a->location?->location_name, $time,
                $a->appointment_date?->toDateString() < $today ? 'Past' : null,
                $a->cancelled_at || in_array($a->status, ['cancelled', 'canceled']) ? 'Cancelled' : null,
                $a->verification_work_item_id || in_array($a->id, $linked) ? 'Verification exists' : null,
            ])->filter()->implode(' | ')];
        })->all();
    }

    public static function clearImportedDetails(Set $set): void
    {
        $set('edit_imported_details', false);
        $set('selected_patient_policy', null);
        foreach (['appointment_id', 'import_appointment_id', 'import_patient_id', 'patient_id',
            'patient_insurance_policy_id', 'provider_id', 'assigned_to', 'vf_appointment_time',
            'vf_appointment_date', 'vf_patient_full_name', 'vf_patient_dob', 'vf_patient_identifier',
            'vf_patient_zip', 'vf_pms_id', 'vf_insured_relation'] as $field) {
            $set($field, null);
        }
        $set('vf_subscriber_same_as_patient', false);
        $set('verification_plan_snapshots', [['plan_priority' => 'primary']]);
    }

    public static function clearAppointmentDetails(Set $set): void
    {
        foreach (['appointment_id', 'import_appointment_id', 'provider_id', 'vf_appointment_date', 'vf_appointment_time'] as $field) {
            $set($field, null);
        }
        $set('edit_imported_details', false);
    }

    public static function patientOptions(?int $clinicId, bool $clinicPanel = false, ?string $search = null, ?int $selectedPolicyId = null): array
    {
        $clinicId = static::clinicId($clinicPanel) ?? $clinicId;
        if (! $clinicId) {
            return [];
        }

        return static::scope(Patient::query(), $clinicPanel)->where('clinic_id', $clinicId)
            ->with(['insurancePolicies' => fn ($query) => $query->where('clinic_id', $clinicId)
                ->orderByRaw("case when coverage_priority = 'primary' then 0 when coverage_priority = 'secondary' then 1 else 2 end")->orderBy('id')])
            ->when(filled($search), fn ($query) => $query->where(function ($query) use ($search, $clinicId): void {
                $query->where(function ($names) use ($search): void {
                    foreach (preg_split('/\s+/', trim($search)) as $part) {
                        $names->where(fn ($name) => $name->where('first_name', 'like', '%' . $part . '%')->orWhere('last_name', 'like', '%' . $part . '%'));
                    }
                })
                    ->orWhere('dob', 'like', '%' . $search . '%')
                    ->orWhereHas('insurancePolicies', fn ($policy) => $policy->where('clinic_id', $clinicId)->where('member_id', 'like', '%' . $search . '%'));
            }))
            ->orderBy('last_name')->orderBy('first_name')->orderBy('id')->limit(50)->get()
            ->mapWithKeys(fn (Patient $patient): array => [$patient->id => static::patientLabel($patient, $selectedPolicyId)])->all();
    }

    public static function patientLabel(Patient $patient, ?int $selectedPolicyId = null): string
    {
        $memberId = ($patient->insurancePolicies->firstWhere('id', $selectedPolicyId) ?? $patient->insurancePolicies->first())?->member_id;

        return $patient->full_name . ' (DOB: ' . ($patient->dob?->format('M d, Y') ?? 'Unavailable')
            . ' | ' . (filled($memberId) ? 'Member ID: ' . $memberId : 'Member ID unavailable') . ')';
    }

    public static function patientPolicies(?int $patientId, bool $clinicPanel = false)
    {
        return static::scope(PatientInsurancePolicy::query(), $clinicPanel)->where('patient_id', $patientId ?? 0)
            ->orderByRaw("case when coverage_priority = 'primary' then 0 when coverage_priority = 'secondary' then 1 else 2 end")->orderBy('id')->get();
    }

    public static function applyPatientPolicy(?int $policyId, ?int $patientId, Set $set, bool $clinicPanel = false): void
    {
        $policy = static::patientPolicies($patientId, $clinicPanel)->firstWhere('id', $policyId);
        if (! $policy) {
            throw ValidationException::withMessages(['data.patient_insurance_policy_id' => 'Select a policy belonging to this patient.']);
        }
        $set('patient_insurance_policy_id', $policy->id);
        $set('vf_patient_identifier', $policy->member_id);
        $relationship = $policy->subscriber_relationship;
        $set('vf_insured_relation', $clinicPanel && $relationship === 'child' ? 'dependent' : $relationship);
        $set('vf_subscriber_same_as_patient', $relationship === 'self');
        $set('verification_plan_snapshots', [[
            'plan_priority' => $policy->coverage_priority ?: 'primary', 'payer_name' => $policy->insurance_company,
            'member_id' => $policy->member_id, 'group_number' => $policy->group_number,
            'subscriber_name' => $policy->subscriber_name, 'subscriber_dob' => $policy->subscriber_dob?->format('Y-m-d'),
        ]]);
    }

    public static function existingAppointmentRequest(int $appointmentId): ?BillingWorkItem
    {
        $appointment = Appointment::find($appointmentId);
        return BillingWorkItem::query()->where('status', '!=', 'cancelled')
            ->whereHas('managedBillingService', fn (Builder $q) => $q->where('category', 'verification'))
            ->where(function (Builder $q) use ($appointmentId, $appointment): void {
                $q->where('appointment_id', $appointmentId);
                if ($appointment?->verification_work_item_id) {
                    $q->orWhereKey($appointment->verification_work_item_id);
                }
            })->first();
    }

    public static function appointmentNotice(?int $appointmentId, bool $clinicPanel = false): ?HtmlString
    {
        if (! $appointmentId || ! static::scope(Appointment::query(), $clinicPanel)->whereKey($appointmentId)->exists()) {
            return null;
        }
        $existing = static::existingAppointmentRequest($appointmentId);
        if (! $existing) {
            return null;
        }
        if (! auth()->user()?->can('view', $existing)) {
            return new HtmlString('A verification already exists for this appointment. Contact your manager.');
        }
        $details = VerificationRequestDuplicateGuard::duplicatePayload($existing, [], $clinicPanel ? 'clinic' : 'verification');

        return new HtmlString('Verification already exists: ' . e($details['reference']) . '. <a class="underline" href="'
            . e($details['url']) . '">Open existing request</a>');
    }

    public static function validate(array $data, bool $clinicPanel = false): string
    {
        // Serialize intake in a location so concurrent submissions cannot bypass duplicate checks.
        $location = static::scope(Location::query(), $clinicPanel)->lockForUpdate()->find($data['location_id'] ?? null);
        if (! $location) {
            throw ValidationException::withMessages(['location_id' => 'Select a location in the active clinic scope.']);
        }
        if ((int) $location->clinic_id !== (int) ($data['clinic_id'] ?? 0)) {
            throw ValidationException::withMessages(['location_id' => 'The location does not match the selected clinic.']);
        }
        Validator::make($data, [
            'priority' => ['required', 'in:normal,urgent'],
            'urgency_reason' => ['required_if:priority,urgent', 'nullable', 'string', 'max:1000'],
        ])->validate();

        if (filled($data['appointment_id'] ?? null)) {
            $appointment = static::scope(Appointment::query(), $clinicPanel)->find($data['appointment_id']);
            if (! $appointment || $appointment->cancelled_at || in_array($appointment->status, ['cancelled', 'canceled'])) {
                throw ValidationException::withMessages(['import_appointment_id' => 'Select an available, non-cancelled appointment.']);
            }
            if (filled($data['vf_appointment_date'] ?? null)
                && \Carbon\Carbon::parse($data['vf_appointment_date'])->toDateString() !== $appointment->appointment_date?->toDateString()) {
                throw ValidationException::withMessages(['vf_appointment_date' => 'The date must match the imported appointment. Clear the appointment import to enter a different date.']);
            }
            if (static::existingAppointmentRequest($appointment->id)) {
                throw ValidationException::withMessages(['import_appointment_id' => 'A verification already exists for this appointment. Open the existing request instead.']);
            }
        }

        return $data['priority'] === 'urgent' ? trim($data['urgency_reason']) : '';
    }
}
