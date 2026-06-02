<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Models\Location;
use App\Support\VerificationAutoAssigner;
use App\Support\VerificationRequestDuplicateGuard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateVerificationRequest extends CreateRecord
{
    protected static string $resource = VerificationRequestResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected array $verificationProfileData = [];

    protected array $verificationPlanSnapshotData = [];

    public function getTitle(): string
    {
        return 'Create Insurance Verification';
    }

    protected function getRedirectUrl(): string
    {
        if ($this->record->source === 'clinic_request' && ! $this->record->clinicWorkspaceEnabled()) {
            return VerificationRequestResource::getUrl('view', ['record' => $this->record]);
        }

        return VerificationRequestResource::getUrl('edit', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->verificationPlanSnapshotData = $data['verification_plan_snapshots'] ?? [];
        unset($data['verification_plan_snapshots']);

        $data = $this->applyManagedServiceRouting($data);

        [$data, $this->verificationProfileData] = static::splitVerificationProfileData($data);

        $patientName = $this->verificationProfileData['patient_full_name'] ?? null;
        $appointmentDate = $this->verificationProfileData['appointment_date'] ?? null;

        $data['title'] = trim(collect([
            'Insurance Verification',
            $patientName,
            filled($appointmentDate) ? date('M d, Y', strtotime((string) $appointmentDate)) : null,
        ])->filter()->implode(' - '));

        $data['due_at'] = static::resolveDueAt($data);
        $data['assigned_to'] = $data['assigned_to'] ?? VerificationAutoAssigner::resolve(
            $data['source'] ?? null,
            filled($data['clinic_id'] ?? null) ? (int) $data['clinic_id'] : null,
        )?->id;

        VerificationRequestDuplicateGuard::ensureNotQueued(
            $data,
            $this->verificationProfileData,
            $this->verificationPlanSnapshotData,
            null,
            'clinic',
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->verificationProfile()->updateOrCreate([], $this->verificationProfileData);
        $this->record->verificationPlanSnapshots()->delete();
        $this->record->verificationPlanSnapshots()->createMany($this->verificationPlanSnapshotData);
        $this->record->recordActivity('verification_profile_saved', 'Structured verification request details captured.');
        if ($this->record->source === 'clinic_request') {
            $this->record->recordActivity('managed_service_requested', 'Insurance verification was submitted from the clinic portal to the Admin verification queue.');
        } else {
            $this->record->recordActivity('clinic_self_service_created', 'Insurance verification was created from the clinic portal for self-service use.');
        }
    }

    protected static function splitVerificationProfileData(array $data): array
    {
        $verificationData = [];

        foreach ($data as $key => $value) {
            if (! str_starts_with($key, 'vf_')) {
                continue;
            }

            $verificationData[str_replace('vf_', '', $key)] = $value;
            unset($data[$key]);
        }

        return [$data, $verificationData];
    }

    protected static function resolveDueAt(array $data)
    {
        $enrollment = filled($data['client_service_enrollment_id'] ?? null)
            ? \App\Models\ClientServiceEnrollment::query()->find($data['client_service_enrollment_id'])
            : null;

        if ($enrollment) {
            return $enrollment->calculateDueAt((string) ($data['priority'] ?? 'normal'));
        }

        return ($data['priority'] ?? 'normal') === 'urgent'
            ? now()->addHours(24)
            : now()->addDays(3);
    }

    protected function applyManagedServiceRouting(array $data): array
    {
        $location = filled($data['location_id'] ?? null)
            ? Location::query()->with('clinic')->find($data['location_id'])
            : null;

        $organizationId = $location?->clinic?->organization_id ?? ($data['organization_id'] ?? null);
        $clinicId = $location?->clinic_id ?? ($data['clinic_id'] ?? null);
        $locationId = $location?->id ?? ($data['location_id'] ?? null);

        $enrollment = VerificationRequestForm::resolveVerificationEnrollment(
            filled($organizationId) ? (int) $organizationId : null,
            filled($clinicId) ? (int) $clinicId : null,
            filled($locationId) ? (int) $locationId : null,
        );

        $data['managed_billing_service_id'] = $enrollment?->managed_billing_service_id
            ?: VerificationRequestForm::resolveDefaultVerificationServiceId();
        $data['client_service_enrollment_id'] = $enrollment?->id;
        $data['source'] = $enrollment ? 'clinic_request' : 'clinic_self_service';
        $data['status'] = 'pending';

        if ($location?->clinic) {
            $data['organization_id'] = $location->clinic->organization_id;
            $data['clinic_id'] = $location->clinic_id;
        }

        return $data;
    }
}
