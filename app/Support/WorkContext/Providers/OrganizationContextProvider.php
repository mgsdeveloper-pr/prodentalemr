<?php

namespace App\Support\WorkContext\Providers;

use App\Models\Clinic;
use App\Models\Organization;
use App\Support\WorkContext\ContextCard;
use App\Support\WorkContext\ContextProviderInterface;
use App\Support\WorkContext\WorkContext;
use Illuminate\Support\Collection;

class OrganizationContextProvider implements ContextProviderInterface
{
    public function __construct(
        protected ?Organization $organization,
        protected ?Clinic $clinic,
        protected array $summary = [],
        protected iterable $recentActivity = [],
        protected iterable $readiness = [],
        protected array $links = [],
    ) {}

    public function context(): WorkContext
    {
        return new WorkContext(
            title: 'Organization Context',
            description: 'Context supplied by the Organization workspace provider.',
            cards: [
                $this->organizationSummaryCard(),
                $this->clinicsCard(),
                $this->usersCard(),
                $this->verificationConfigurationCard(),
                $this->recentActivityCard(),
                $this->documentsCard(),
                $this->workspaceReadinessCard(),
                $this->futureWorkspaceIntelligenceCard(),
            ],
            search: [
                'enabled' => false,
                'placeholder' => 'Organization Context Search',
            ],
            ai: [
                'enabled' => false,
                'title' => 'Organization AI Assistant',
            ],
        );
    }

    protected function organizationSummaryCard(): ContextCard
    {
        return new ContextCard(
            title: 'Organization Summary',
            type: 'rows',
            items: $this->rows([
                'Organization' => $this->organization?->name ?? '-',
                'Clinic' => $this->clinic?->clinic_name ?? '-',
                'Status' => ($this->organization?->status ?? false) ? 'Active' : 'Inactive',
                'Lifecycle' => $this->organization?->lifecycle_status ?? '-',
            ]),
            badge: ($this->organization?->status ?? false) ? 'Active' : 'Inactive',
            pinned: true,
        );
    }

    protected function clinicsCard(): ContextCard
    {
        return new ContextCard(
            title: 'Clinics',
            type: 'rows',
            items: $this->rows([
                'Total Clinics' => $this->summary['clinic_count'] ?? 0,
                'Active Clinics' => $this->summary['active_clinic_count'] ?? 0,
                'Selected Clinic' => $this->clinic?->clinic_name ?? '-',
            ]),
            actions: $this->actionRows(['clinics']),
        );
    }

    protected function usersCard(): ContextCard
    {
        return new ContextCard(
            title: 'Users',
            type: 'rows',
            items: $this->rows([
                'Active Users' => $this->summary['active_user_count'] ?? 0,
                'Clinic Users' => $this->summary['clinic_user_count'] ?? 0,
            ]),
            actions: $this->actionRows(['users']),
        );
    }

    protected function verificationConfigurationCard(): ContextCard
    {
        return new ContextCard(
            title: 'Verification Configuration',
            type: 'rows',
            items: $this->rows([
                'Verification Service' => $this->clinic?->hasActiveVerificationServices() ? 'Active' : 'Inactive',
                'Portal Credentials' => $this->summary['portal_credential_count'] ?? 0,
                'Template Questions' => $this->summary['template_question_count'] ?? 0,
                'Unread Notifications' => $this->summary['unread_notification_count'] ?? 0,
            ]),
            actions: $this->actionRows(['settings', 'portal_credentials', 'notifications', 'reports']),
        );
    }

    protected function recentActivityCard(): ContextCard
    {
        $items = $this->toCollection($this->recentActivity)
            ->map(fn (array $activity): array => [
                'label' => $activity['label'] ?? 'Activity',
                'value' => $activity['value'] ?? null,
                'meta' => $activity['meta'] ?? null,
            ])
            ->values()
            ->all();

        return new ContextCard(
            title: 'Recent Activity',
            type: 'timeline',
            items: $items,
            badge: (string) count($items),
            state: $items === [] ? 'empty' : 'collapsed',
            scrollable: true,
        );
    }

    protected function documentsCard(): ContextCard
    {
        return new ContextCard(
            title: 'Documents',
            type: 'rows',
            items: $this->rows([
                'Verification Documents' => $this->summary['verification_document_count'] ?? 0,
                'Uploaded This Month' => $this->summary['verification_documents_this_month'] ?? 0,
            ]),
            actions: $this->actionRows(['document_center']),
        );
    }

    protected function workspaceReadinessCard(): ContextCard
    {
        $items = $this->toCollection($this->readiness)
            ->map(fn (array $item): array => [
                'label' => $item['label'] ?? 'Readiness check',
                'value' => ($item['status'] ?? null) === 'ready' ? 'Ready' : 'Needs attention',
                'meta' => $item['description'] ?? null,
            ])
            ->values()
            ->all();

        return new ContextCard(
            title: 'Workspace Readiness',
            type: 'list',
            items: $items,
            badge: (string) count($items),
            state: $items === [] ? 'empty' : 'expanded',
        );
    }

    protected function futureWorkspaceIntelligenceCard(): ContextCard
    {
        return new ContextCard(
            title: 'Future Workspace Intelligence',
            type: 'placeholder',
            items: [],
            description: 'Reserved for future verification operations intelligence.',
            badge: 'Reserved',
            state: 'disabled',
        );
    }

    protected function rows(array $values): array
    {
        return collect($values)
            ->map(fn (mixed $value, string $label): array => [
                'label' => $label,
                'value' => filled($value) ? (string) $value : '-',
            ])
            ->values()
            ->all();
    }

    protected function actionRows(array $keys): array
    {
        return collect($keys)
            ->map(fn (string $key): ?array => isset($this->links[$key])
                ? ['label' => $this->links[$key]['label'], 'href' => $this->links[$key]['url']]
                : null)
            ->filter()
            ->values()
            ->all();
    }

    protected function toCollection(iterable $items): Collection
    {
        return $items instanceof Collection ? $items : collect($items);
    }
}
