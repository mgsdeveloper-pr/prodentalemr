<?php

namespace App\Filament\Clinic\Pages\Concerns;

use App\Filament\Clinic\Pages\DocumentCenter;
use App\Filament\Clinic\Pages\VerificationNotificationCentre;
use App\Filament\Clinic\Pages\VerificationReports;
use App\Filament\Clinic\Pages\VerificationSettings;
use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource;
use App\Filament\Clinic\Resources\Users\UserResource;
use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Models\AuditLog;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemAttachment;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\PortalCredential;
use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationNotification;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use App\Support\WorkContext\Providers\OrganizationContextProvider;
use App\Support\WorkContext\WorkContext;
use Illuminate\Support\Collection;

trait InteractsWithOrganizationOperationsWorkspace
{
    private bool $organizationOperationsOrganizationLoaded = false;

    private ?Organization $organizationOperationsOrganization = null;

    private bool $organizationOperationsClinicLoaded = false;

    private ?Clinic $organizationOperationsClinic = null;

    private ?array $organizationOperationsMetrics = null;

    private ?array $organizationOperationsLinks = null;

    private ?Collection $organizationOperationsClinicRows = null;

    private ?Collection $organizationOperationsRecentVerifications = null;

    private ?Collection $organizationOperationsRecentActivity = null;

    private ?array $organizationOperationsReadiness = null;

    private ?WorkContext $organizationOperationsWorkContext = null;

    public function getOrganization(): ?Organization
    {
        if ($this->organizationOperationsOrganizationLoaded) {
            return $this->organizationOperationsOrganization;
        }

        $clinic = $this->getClinic();

        if ($clinic?->organization) {
            $this->organizationOperationsOrganization = $clinic->organization;
            $this->organizationOperationsOrganizationLoaded = true;

            return $this->organizationOperationsOrganization;
        }

        $this->organizationOperationsOrganization = auth()->user()?->organization;
        $this->organizationOperationsOrganizationLoaded = true;

        return $this->organizationOperationsOrganization;
    }

    public function getClinic(): ?Clinic
    {
        if ($this->organizationOperationsClinicLoaded) {
            return $this->organizationOperationsClinic;
        }

        $this->organizationOperationsClinic = ClinicWorkspace::clinicForUser();
        $this->organizationOperationsClinicLoaded = true;

        return $this->organizationOperationsClinic;
    }

    public function getWorkspaceMetrics(): array
    {
        if ($this->organizationOperationsMetrics !== null) {
            return $this->organizationOperationsMetrics;
        }

        $organization = $this->getOrganization();
        $clinic = $this->getClinic();

        if (! $organization) {
            return $this->organizationOperationsMetrics = [
                'clinic_count' => 0,
                'active_clinic_count' => 0,
                'active_user_count' => 0,
                'clinic_user_count' => 0,
                'open_verification_count' => 0,
                'waiting_on_clinic_count' => 0,
                'completed_this_month' => 0,
                'verification_document_count' => 0,
                'verification_documents_this_month' => 0,
                'portal_credential_count' => 0,
                'template_question_count' => 0,
                'unread_notification_count' => 0,
            ];
        }

        $clinicId = $clinic?->id ?? ClinicPanelScope::selectedClinicId();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return $this->organizationOperationsMetrics = [
            'clinic_count' => Clinic::query()
                ->where('organization_id', $organization->id)
                ->count(),
            'active_clinic_count' => Clinic::query()
                ->where('organization_id', $organization->id)
                ->where('status', true)
                ->count(),
            'active_user_count' => User::query()
                ->where('organization_id', $organization->id)
                ->where('status', true)
                ->count(),
            'clinic_user_count' => User::query()
                ->where('organization_id', $organization->id)
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->count(),
            'open_verification_count' => BillingWorkItem::query()
                ->where('organization_id', $organization->id)
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->where('status', '!=', BillingWorkItem::STATUS_DONE)
                ->count(),
            'waiting_on_clinic_count' => BillingWorkItem::query()
                ->where('organization_id', $organization->id)
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
                ->count(),
            'completed_this_month' => BillingWorkItem::query()
                ->where('organization_id', $organization->id)
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->where('status', BillingWorkItem::STATUS_DONE)
                ->whereBetween('completed_at', [$monthStart, $monthEnd])
                ->count(),
            'verification_document_count' => BillingWorkItemAttachment::query()
                ->whereHas('workItem', fn ($query) => $query
                    ->where('organization_id', $organization->id)
                    ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId)))
                ->count(),
            'verification_documents_this_month' => BillingWorkItemAttachment::query()
                ->whereHas('workItem', fn ($query) => $query
                    ->where('organization_id', $organization->id)
                    ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId)))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count(),
            'portal_credential_count' => PortalCredential::query()
                ->where('organization_id', $organization->id)
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->where('visible_to_clinic', true)
                ->where('is_active', true)
                ->count(),
            'template_question_count' => VerificationFormQuestion::query()
                ->visibleForClinic($clinicId, $organization->id)
                ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
                ->where('is_active', true)
                ->count(),
            'unread_notification_count' => VerificationNotification::query()
                ->where('user_id', auth()->id())
                ->where('panel', 'clinic')
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->whereNull('read_at')
                ->count(),
        ];
    }

    public function getWorkspaceLinks(): array
    {
        if ($this->organizationOperationsLinks !== null) {
            return $this->organizationOperationsLinks;
        }

        return $this->organizationOperationsLinks = [
            'verifications' => [
                'label' => 'Open Verification List',
                'url' => VerificationRequestResource::getUrl('index'),
                'visible' => VerificationRequestResource::canAccess(),
            ],
            'users' => [
                'label' => 'Manage Users',
                'url' => UserResource::getUrl('index'),
                'visible' => UserResource::canAccess(),
            ],
            'portal_credentials' => [
                'label' => 'Portal Credentials',
                'url' => PortalCredentialResource::getUrl('index'),
                'visible' => PortalCredentialResource::canViewAny(),
            ],
            'settings' => [
                'label' => 'Verification Settings',
                'url' => VerificationSettings::getUrl(),
                'visible' => VerificationSettings::canAccess(),
            ],
            'document_center' => [
                'label' => 'Document Center',
                'url' => DocumentCenter::getUrl(),
                'visible' => DocumentCenter::canAccess(),
            ],
            'notifications' => [
                'label' => 'Notifications',
                'url' => VerificationNotificationCentre::getUrl(),
                'visible' => VerificationNotificationCentre::canAccess(),
            ],
            'reports' => [
                'label' => 'Reports',
                'url' => VerificationReports::getUrl(),
                'visible' => VerificationReports::canAccess(),
            ],
        ];
    }

    public function getClinicRows(): Collection
    {
        if ($this->organizationOperationsClinicRows !== null) {
            return $this->organizationOperationsClinicRows;
        }

        $organization = $this->getOrganization();

        if (! $organization) {
            return $this->organizationOperationsClinicRows = collect();
        }

        return $this->organizationOperationsClinicRows = Clinic::query()
            ->where('organization_id', $organization->id)
            ->orderBy('clinic_name')
            ->limit(8)
            ->get()
            ->map(fn (Clinic $clinic): array => [
                'name' => $clinic->clinic_name,
                'code' => $clinic->clinic_code ?: '-',
                'status' => $clinic->status ? 'Active' : 'Inactive',
                'services' => $clinic->verification_services_enabled ? 'Verification' : 'Verification not enabled',
            ]);
    }

    public function getRecentVerificationRows(): Collection
    {
        if ($this->organizationOperationsRecentVerifications !== null) {
            return $this->organizationOperationsRecentVerifications;
        }

        $organization = $this->getOrganization();
        $clinic = $this->getClinic();

        if (! $organization) {
            return $this->organizationOperationsRecentVerifications = collect();
        }

        return $this->organizationOperationsRecentVerifications = BillingWorkItem::query()
            ->with(['patient', 'clinic'])
            ->where('organization_id', $organization->id)
            ->when($clinic?->id, fn ($query) => $query->where('clinic_id', $clinic->id))
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (BillingWorkItem $workItem): array => [
                'reference' => $workItem->reference_number,
                'patient' => $workItem->patient?->full_name ?? $workItem->title ?? '-',
                'clinic' => $workItem->clinic?->clinic_name ?? '-',
                'status' => BillingWorkItem::STATUS_OPTIONS[$workItem->normalized_status] ?? str($workItem->normalized_status)->headline()->toString(),
                'updated' => optional($workItem->updated_at)->format('M d, Y h:i A') ?? '-',
            ]);
    }

    public function getRecentActivityRows(): Collection
    {
        if ($this->organizationOperationsRecentActivity !== null) {
            return $this->organizationOperationsRecentActivity;
        }

        $organization = $this->getOrganization();
        $clinic = $this->getClinic();

        if (! $organization) {
            return $this->organizationOperationsRecentActivity = collect();
        }

        return $this->organizationOperationsRecentActivity = AuditLog::query()
            ->where('organization_id', $organization->id)
            ->when($clinic?->id, fn ($query) => $query->where('clinic_id', $clinic->id))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'label' => str($log->module ?: 'Activity')->headline()->toString(),
                'value' => str($log->action ?: 'Updated')->headline()->toString(),
                'meta' => optional($log->created_at)->format('M d, Y h:i A') ?? '-',
            ]);
    }

    public function getWorkspaceReadiness(): array
    {
        if ($this->organizationOperationsReadiness !== null) {
            return $this->organizationOperationsReadiness;
        }

        $organization = $this->getOrganization();
        $clinic = $this->getClinic();
        $metrics = $this->getWorkspaceMetrics();

        return $this->organizationOperationsReadiness = [
            [
                'label' => 'Organization Configured',
                'status' => $organization && filled($organization->name) && filled($organization->email) ? 'ready' : 'attention',
                'description' => $organization && filled($organization->name) && filled($organization->email)
                    ? 'Organization name and contact email are available.'
                    : 'Organization name or contact email is missing.',
            ],
            [
                'label' => 'Clinics Configured',
                'status' => ($metrics['active_clinic_count'] ?? 0) > 0 ? 'ready' : 'attention',
                'description' => ($metrics['active_clinic_count'] ?? 0) > 0
                    ? number_format($metrics['active_clinic_count']) . ' active clinic record available.'
                    : 'No active clinic record is available.',
            ],
            [
                'label' => 'Users Assigned',
                'status' => ($metrics['active_user_count'] ?? 0) > 0 ? 'ready' : 'attention',
                'description' => ($metrics['active_user_count'] ?? 0) > 0
                    ? number_format($metrics['active_user_count']) . ' active user record available.'
                    : 'No active users are assigned.',
            ],
            [
                'label' => 'Verification Enabled',
                'status' => $clinic?->hasActiveVerificationServices() ? 'ready' : 'attention',
                'description' => $clinic?->hasActiveVerificationServices()
                    ? 'The selected clinic has active verification services.'
                    : 'The selected clinic does not show active verification services.',
            ],
            [
                'label' => 'Templates Available',
                'status' => ($metrics['template_question_count'] ?? 0) > 0 ? 'ready' : 'attention',
                'description' => ($metrics['template_question_count'] ?? 0) > 0
                    ? number_format($metrics['template_question_count']) . ' active verification template questions available.'
                    : 'No active verification template questions are available.',
            ],
            [
                'label' => 'Portal Credentials',
                'status' => ($metrics['portal_credential_count'] ?? 0) > 0 ? 'ready' : 'attention',
                'description' => ($metrics['portal_credential_count'] ?? 0) > 0
                    ? number_format($metrics['portal_credential_count']) . ' active portal credential available.'
                    : 'Portal credentials are missing for the selected clinic.',
            ],
            [
                'label' => 'Notification Contact',
                'status' => filled($organization?->email) ? 'ready' : 'attention',
                'description' => filled($organization?->email)
                    ? 'Organization contact email is available for verification communication.'
                    : 'Organization contact email is missing.',
            ],
        ];
    }

    public function getOrganizationWorkContext(): WorkContext
    {
        if ($this->organizationOperationsWorkContext !== null) {
            return $this->organizationOperationsWorkContext;
        }

        $links = collect($this->getWorkspaceLinks())
            ->filter(fn (array $link): bool => (bool) ($link['visible'] ?? false))
            ->all();

        return $this->organizationOperationsWorkContext = (new OrganizationContextProvider(
            organization: $this->getOrganization(),
            clinic: $this->getClinic(),
            summary: $this->getWorkspaceMetrics(),
            recentActivity: $this->getRecentActivityRows(),
            readiness: $this->getWorkspaceReadiness(),
            links: $links,
        ))->context();
    }
}
