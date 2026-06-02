<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments\Pages\Concerns;

use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;

trait InteractsWithClientServiceEnrollmentEditor
{
    public function getSubmitMethodName(): string
    {
        return $this instanceof \Filament\Resources\Pages\CreateRecord ? 'create' : 'save';
    }

    public function getSubmitButtonLabel(): string
    {
        return $this instanceof \Filament\Resources\Pages\CreateRecord
            ? 'Create enrollment'
            : 'Save enrollment';
    }

    public function getEditorHeading(): string
    {
        return $this instanceof \Filament\Resources\Pages\CreateRecord
            ? 'Activate a service enrollment with the right scope, SLA, and clinic workspace behavior.'
            : 'Update the enrollment scope, SLA targets, and workspace behavior without losing context.';
    }

    public function getEditorDescription(): string
    {
        return 'Use this workspace to connect a managed service to the right organization, clinic, or location. Keep the coverage clear, define the response targets, and make it obvious whether clinics can collaborate on shared verification work.';
    }

    public function getCancelUrl(): string
    {
        return method_exists($this, 'getResource') ? $this->getResource()::getUrl() : '#';
    }

    public function getCurrentOrganizationLabel(): string
    {
        $id = $this->data['organization_id'] ?? null;

        return filled($id)
            ? Organization::query()->whereKey($id)->value('name') ?? 'Choose organization'
            : 'Choose organization';
    }

    public function getCurrentClinicLabel(): string
    {
        $id = $this->data['clinic_id'] ?? null;

        return filled($id)
            ? Clinic::query()->whereKey($id)->value('clinic_name') ?? 'All clinics'
            : 'All clinics';
    }

    public function getCurrentLocationLabel(): string
    {
        $id = $this->data['location_id'] ?? null;

        return filled($id)
            ? Location::query()->whereKey($id)->value('location_name') ?? 'All locations'
            : 'All locations';
    }

    public function getCurrentServiceLabel(): string
    {
        $id = $this->data['managed_billing_service_id'] ?? null;

        return filled($id)
            ? ManagedBillingService::query()->whereKey($id)->value('name') ?? 'Choose service'
            : 'Choose service';
    }

    public function getCurrentStatusLabel(): string
    {
        $status = $this->data['status'] ?? null;

        return filled($status)
            ? ClientServiceEnrollment::STATUS_OPTIONS[$status] ?? ucfirst((string) $status)
            : 'Choose status';
    }

    public function getCurrentNormalSlaLabel(): string
    {
        $days = $this->data['normal_sla_days'] ?? null;

        return filled($days) ? "{$days} days" : 'Set normal SLA';
    }

    public function getCurrentUrgentSlaLabel(): string
    {
        $hours = $this->data['urgent_sla_hours'] ?? null;

        return filled($hours) ? "{$hours} hours" : 'Set urgent SLA';
    }

    public function getWorkspaceModeLabel(): string
    {
        return ! empty($this->data['clinic_workspace_enabled'])
            ? 'Clinic workspace enabled'
            : 'Verification team only';
    }
}
