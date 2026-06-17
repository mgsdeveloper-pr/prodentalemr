<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\ManagedBillingService;
use App\Models\VerificationProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AppointmentVerificationSender
{
    public function send(Appointment $appointment): BillingWorkItem
    {
        if (filled($appointment->verification_work_item_id)) {
            return $appointment->verificationWorkItem()->firstOrFail();
        }

        $appointment->loadMissing(['patient', 'provider.user', 'location', 'clinic']);

        $serviceId = $this->resolveVerificationServiceId($appointment);

        if (! $serviceId) {
            throw new RuntimeException('No active verification service is available for this clinic.');
        }

        return DB::transaction(function () use ($appointment, $serviceId): BillingWorkItem {
            $workItem = BillingWorkItem::query()->create([
                'organization_id' => $appointment->organization_id,
                'clinic_id' => $appointment->clinic_id,
                'location_id' => $appointment->location_id,
                'managed_billing_service_id' => $serviceId,
                'client_service_enrollment_id' => $this->resolveEnrollmentId($appointment, $serviceId),
                'appointment_id' => $appointment->getKey(),
                'patient_id' => $appointment->patient_id,
                'provider_id' => $appointment->provider_id,
                'title' => trim('Verification for ' . ($appointment->patient?->full_name ?: 'Appointment')),
                'status' => BillingWorkItem::STATUS_PENDING,
                'outcome_status' => 'pending',
                'priority' => 'normal',
                'source' => 'appointment_sync',
                'notes' => $appointment->appointment_type,
                'due_at' => $appointment->appointment_date?->copy()?->endOfDay(),
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
                'insurance_provider_name' => $appointment->patient?->insurance_provider,
                'subscriber_id' => $appointment->patient?->insurance_number,
                'verification_notes' => $appointment->appointment_type,
            ]);

            $appointment->forceFill([
                'verification_status' => Appointment::VERIFICATION_STATUS_SENT,
                'verification_work_item_id' => $workItem->getKey(),
            ])->save();

            return $workItem;
        });
    }

    protected function resolveVerificationServiceId(Appointment $appointment): ?int
    {
        return $this->resolveEnrollment($appointment)?->managed_billing_service_id
            ?: ManagedBillingService::query()
                ->where('category', 'verification')
                ->where('status', true)
                ->orderBy('id')
                ->value('id');
    }

    protected function resolveEnrollmentId(Appointment $appointment, int $serviceId): ?int
    {
        return $this->resolveEnrollment($appointment, $serviceId)?->getKey();
    }

    protected function resolveEnrollment(Appointment $appointment, ?int $serviceId = null): ?ClientServiceEnrollment
    {
        return ClientServiceEnrollment::query()
            ->where('organization_id', $appointment->organization_id)
            ->where('clinic_id', $appointment->clinic_id)
            ->where('status', 'active')
            ->when($serviceId, fn (Builder $query): Builder => $query->where('managed_billing_service_id', $serviceId))
            ->whereHas('managedBillingService', fn (Builder $query): Builder => $query->where('category', 'verification'))
            ->orderByRaw('case when location_id is null then 1 else 0 end')
            ->first();
    }
}
