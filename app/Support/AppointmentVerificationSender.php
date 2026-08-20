<?php

namespace App\Support;

use App\Actions\Verification\CreateVerificationRequestAction;
use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\VerificationProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentVerificationSender
{
    public function __construct(
        protected CreateVerificationRequestAction $createVerificationRequest,
    ) {}

    public function send(Appointment $appointment, ?string $processingMode = null): BillingWorkItem
    {
        $appointment->loadMissing(['patient', 'provider.user', 'location', 'clinic', 'insurancePolicy']);
        $policy = $appointment->insurancePolicy
            ?: $appointment->patient?->insurancePolicies()
                ->where('status', true)
                ->orderByRaw("case when coverage_priority = 'primary' then 0 else 1 end")
                ->first();

        if (! $policy) {
            $appointment->forceFill([
                'verification_status' => Appointment::VERIFICATION_STATUS_NEEDS_INSURANCE,
                'verification_work_item_id' => null,
            ])->save();

            throw ValidationException::withMessages([
                'patient_id' => 'Add an active insurance policy in the Patient record before starting verification.',
            ]);
        }

        $allowedModes = VerificationRequestForm::processingModeOptions(
            $appointment->organization_id,
            $appointment->clinic_id,
            $appointment->location_id,
        );
        $processingMode ??= array_key_first($allowedModes);

        if (! array_key_exists((string) $processingMode, $allowedModes)) {
            throw ValidationException::withMessages([
                'processing_mode' => 'The selected verification route is not available for this clinic.',
            ]);
        }

        $enrollment = VerificationRequestForm::resolveVerificationEnrollment(
            $appointment->organization_id,
            $appointment->clinic_id,
            $appointment->location_id,
        );
        $serviceId = $processingMode === BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE
            ? $enrollment?->managed_billing_service_id
            : VerificationRequestForm::resolveDefaultVerificationServiceId();

        if (! $serviceId) {
            throw ValidationException::withMessages([
                'processing_mode' => 'No active verification service is available for this clinic.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $processingMode, $serviceId, $enrollment, $policy): BillingWorkItem {
            $lockedAppointment = Appointment::query()
                ->with(['patient', 'provider.user', 'location', 'insurancePolicy'])
                ->lockForUpdate()
                ->findOrFail($appointment->getKey());

            if (filled($lockedAppointment->verification_work_item_id)) {
                return $lockedAppointment->verificationWorkItem()->firstOrFail();
            }

            $existingRequest = BillingWorkItem::query()
                ->where('appointment_id', $lockedAppointment->getKey())
                ->whereNull('deleted_at')
                ->latest('id')
                ->first();

            if ($existingRequest) {
                $lockedAppointment->forceFill([
                    'verification_work_item_id' => $existingRequest->getKey(),
                ])->save();

                return $existingRequest;
            }

            $isManaged = $processingMode === BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE;
            $workItem = $this->createVerificationRequest->execute([
                'organization_id' => $appointment->organization_id,
                'clinic_id' => $appointment->clinic_id,
                'location_id' => $appointment->location_id,
                'managed_billing_service_id' => $serviceId,
                'client_service_enrollment_id' => $isManaged ? $enrollment?->getKey() : null,
                'appointment_id' => $appointment->getKey(),
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'patient_insurance_policy_id' => $policy?->getKey(),
                'title' => trim('Verification for '.($appointment->patient?->full_name ?: 'Appointment')),
                'status' => BillingWorkItem::STATUS_PENDING,
                'outcome_status' => 'pending',
                'priority' => 'normal',
                'source' => $isManaged ? 'clinic_request' : 'clinic_self_service',
                'processing_mode' => $processingMode,
                'notes' => $appointment->appointment_type,
            ]);

            VerificationProfile::query()->create([
                'billing_work_item_id' => $workItem->getKey(),
                'form_type' => 'full_form',
                'requested_by_name' => auth()->user()?->name,
                'requested_by_role_slug' => auth()->user()?->roles?->pluck('name')->first(),
                'requested_from_panel' => 'verification',
                'patient_full_name' => $appointment->patient?->full_name,
                'patient_dob' => $appointment->patient?->dob,
                'patient_identifier' => $appointment->patient?->pms_patient_id ?: $appointment->patient?->insurance_number,
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->start_time,
                'location_name' => $appointment->location?->location_name,
                'provider_name' => $appointment->provider?->display_name,
                'pms_id' => $appointment->patient?->pms_patient_id,
                'subscriber_name' => $policy?->subscriber_name,
                'subscriber_dob' => $policy?->subscriber_dob,
                'subscriber_id' => $policy?->member_id ?: $appointment->patient?->insurance_number,
                'insured_relation' => $policy?->subscriber_relationship,
                'coverage_role' => $policy?->subscriber_relationship,
                'insurance_provider_name' => $policy?->insurance_company ?: $appointment->patient?->insurance_provider,
                'insurance_claim_mailing_address' => $policy?->claims_address,
                'insurance_company_phone_number' => $policy?->payer_phone,
                'effective_date' => $policy?->effective_date,
                'group_name' => $policy?->plan_name,
                'group_number' => $policy?->group_number,
                'verification_notes' => $appointment->appointment_type,
            ]);

            $lockedAppointment->forceFill([
                'verification_status' => Appointment::VERIFICATION_STATUS_SENT,
                'verification_work_item_id' => $workItem->getKey(),
            ])->save();

            return $workItem;
        });
    }
}
