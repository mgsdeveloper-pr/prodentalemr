<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use Illuminate\Validation\ValidationException;

class ClientServiceEnrollmentWorkflow
{
    public function prepare(array $data, ?ClientServiceEnrollment $current = null): array
    {
        $clinic = Clinic::query()->find($data['clinic_id'] ?? null);

        if (! $clinic || (int) $clinic->organization_id !== (int) ($data['organization_id'] ?? 0)) {
            throw ValidationException::withMessages([
                'data.clinic_id' => 'Select a clinic that belongs to the chosen organization.',
            ]);
        }

        if (filled($data['location_id'] ?? null)) {
            $locationMatchesClinic = Location::query()
                ->whereKey($data['location_id'])
                ->where('clinic_id', $clinic->getKey())
                ->exists();

            if (! $locationMatchesClinic) {
                throw ValidationException::withMessages([
                    'data.location_id' => 'Select a location that belongs to the chosen clinic.',
                ]);
            }
        }

        $service = ManagedBillingService::query()
            ->whereKey($data['managed_billing_service_id'] ?? null)
            ->where('status', true)
            ->first();

        if (! $service) {
            throw ValidationException::withMessages([
                'data.managed_billing_service_id' => 'Select an active managed service.',
            ]);
        }

        $duplicate = ClientServiceEnrollment::query()
            ->where('clinic_id', $clinic->getKey())
            ->where('managed_billing_service_id', $service->getKey())
            ->where('status', '!=', 'closed')
            ->when($current, fn ($query) => $query->where($query->getModel()->qualifyColumn('id'), '!=', $current->getKey()))
            ->where(function ($query) use ($data): void {
                $locationId = $data['location_id'] ?? null;

                if (filled($locationId)) {
                    $query->whereNull('location_id')->orWhere('location_id', $locationId);

                    return;
                }

                $query->whereNotNull('id');
            })
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'data.managed_billing_service_id' => 'This clinic already has an open enrollment for the selected service.',
            ]);
        }

        $data['organization_id'] = $clinic->organization_id;
        $data['created_by'] ??= auth()->id();

        return $data;
    }

    public function synchronizeClinic(ClientServiceEnrollment $enrollment): void
    {
        $enrollment->loadMissing(['clinic', 'managedBillingService']);

        if (! $enrollment->clinic || ! $enrollment->managedBillingService) {
            return;
        }

        $openEnrollments = $enrollment->clinic->serviceEnrollments()
            ->whereIn('status', ['active', 'requested'])
            ->get(['status']);

        $changes = [
            'managed_services_status' => match (true) {
                $openEnrollments->contains('status', 'active') => 'active',
                $openEnrollments->contains('status', 'requested') => 'requested',
                default => 'not_enabled',
            },
        ];

        if ($enrollment->status === 'active' && $enrollment->managedBillingService->category === 'verification') {
            $changes['verification_services_enabled'] = true;
            $changes['verification_service_status'] = 'active';
        }

        $enrollment->clinic->update($changes);
    }
}
