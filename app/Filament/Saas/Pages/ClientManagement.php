<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\OnboardingDraft;
use App\Services\ClientOnboardingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use UnitEnum;

class ClientManagement extends Page
{
    use WithPagination;

    protected static string|UnitEnum|null $navigationGroup = 'Client Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    protected static ?string $navigationLabel = 'Clients';
    protected static ?int $navigationSort = 0;
    protected static ?string $title = 'Clients';
    protected static ?string $slug = 'client-management';
    protected string $view = 'filament.saas.pages.client-management';

    public string $search = '';
    public string $typeFilter = 'all';
    public string $serviceFilter = 'all';
    public string $statusFilter = 'all';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('organizations') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Find, review, and manage client accounts from one place.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newClient')
                ->label('New Client')
                ->icon('heroicon-o-plus')
                ->modalHeading('Create a new client')
                ->modalDescription('Choose the account structure and verification service model. The correct setup wizard will open next.')
                ->modalSubmitActionLabel('Continue')
                ->form([
                    Select::make('client_type')
                        ->label('Client type')
                        ->options([
                            'single_clinic' => 'Solo Practice',
                            'organization' => 'Multi Location Organization',
                            'dso' => 'DSO',
                        ])
                        ->default('single_clinic')
                        ->native(false)
                        ->required(),
                    Select::make('verification_model')
                        ->label('Verification model')
                        ->options([
                            'managed_service' => 'Managed Service',
                            'self_service' => 'Self-Managed',
                            'hybrid' => 'Hybrid',
                        ])
                        ->default('managed_service')
                        ->native(false)
                        ->required(),
                ])
                ->action(function (array $data, ClientOnboardingService $onboarding): void {
                    $draft = $onboarding->start(
                        auth()->user(),
                        $data['client_type'],
                        $data['verification_model'],
                    );

                    $this->redirect($onboarding->resumeUrl($draft), navigate: true);
                }),
        ];
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedTypeFilter(): void { $this->resetPage(); }
    public function updatedServiceFilter(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'serviceFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function stats(): array
    {
        return [
            'total' => Organization::query()->count(),
            'active' => Organization::query()->where('status', true)->count(),
            'onboarding' => Organization::query()->where('onboarding_status', '!=', 'complete')->count()
                + OnboardingDraft::query()->whereIn('status', [
                    OnboardingDraft::STATUS_DRAFT,
                    OnboardingDraft::STATUS_CHANGES_REQUESTED,
                ])->count(),
            'attention' => Organization::query()
                ->where(function (Builder $query): void {
                    $query->where('status', false)
                        ->orWhere('onboarding_status', '!=', 'complete')
                        ->orWhereIn('lifecycle_status', ['at_risk', 'blocked', 'paused']);
                })
                ->count(),
        ];
    }

    public function onboardingDrafts(): array
    {
        $onboarding = app(ClientOnboardingService::class);

        return OnboardingDraft::query()
            ->whereIn('status', [OnboardingDraft::STATUS_DRAFT, OnboardingDraft::STATUS_CHANGES_REQUESTED])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (OnboardingDraft $draft): array => [
                'reference' => $draft->public_id,
                'name' => data_get($draft->data, 'dso_name')
                    ?: data_get($draft->data, 'organization_name')
                    ?: 'Untitled client',
                'structure' => match ($draft->account_structure) {
                    'single_clinic' => 'Solo Practice',
                    'dso' => 'DSO',
                    default => 'Multi Location Organization',
                },
                'verification_model' => match ($draft->verification_model) {
                    'self_service' => 'Self-Managed',
                    'hybrid' => 'Hybrid',
                    default => 'Managed Service',
                },
                'progress' => max(1, (int) $draft->last_completed_step),
                'updated' => $draft->updated_at?->diffForHumans() ?? 'Not started',
                'url' => $onboarding->resumeUrl($draft),
            ])
            ->all();
    }

    public function clients(): LengthAwarePaginator
    {
        return Organization::query()
            ->with([
                'dso:id,name',
                'clinics:id,organization_id,verification_services_enabled,managed_services_status',
                'clinics.serviceEnrollments:id,clinic_id,managed_billing_service_id,status',
                'clinics.serviceEnrollments.managedBillingService:id,category',
                'subscriptions' => fn ($query) => $query
                    ->with('subscriptionPlan:id,name')
                    ->latest('start_date'),
            ])
            ->withCount(['clinics', 'users', 'locations'])
            ->when(filled($this->search), function (Builder $query): void {
                $search = trim($this->search);
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('dso', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->typeFilter === 'dso', fn (Builder $query) => $query->whereNotNull('dso_id'))
            ->when($this->typeFilter === 'multi_location', fn (Builder $query) => $query->whereNull('dso_id')->has('clinics', '>', 1))
            ->when($this->typeFilter === 'solo', fn (Builder $query) => $query->whereNull('dso_id')->has('clinics', '<=', 1))
            ->when($this->serviceFilter === 'managed', fn (Builder $query) => $query
                ->whereHas('serviceEnrollments', fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))))
            ->when($this->serviceFilter === 'hybrid', fn (Builder $query) => $query
                ->whereHas('serviceEnrollments', fn (Builder $query) => $query
                    ->where('status', 'requested')
                    ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))))
            ->when($this->serviceFilter === 'self', fn (Builder $query) => $query
                ->whereHas('clinics', fn (Builder $query) => $query
                    ->where('verification_services_enabled', true))
                ->whereDoesntHave('serviceEnrollments', fn (Builder $query) => $query
                    ->whereIn('status', ['active', 'requested'])
                    ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))))
            ->when($this->statusFilter === 'active', fn (Builder $query) => $query->where('status', true))
            ->when($this->statusFilter === 'inactive', fn (Builder $query) => $query->where('status', false))
            ->when($this->statusFilter === 'onboarding', fn (Builder $query) => $query->where('onboarding_status', '!=', 'complete'))
            ->latest('updated_at')
            ->paginate(15);
    }

    public function clientRow(Organization $organization): array
    {
        $subscription = $organization->subscriptions
            ->first(fn ($subscription) => in_array($subscription->status, ['active', 'trial'], true))
            ?? $organization->subscriptions->first();
        $managedStatuses = $organization->clinics
            ->flatMap(fn (Clinic $clinic) => $clinic->serviceEnrollments
                ->filter(fn ($enrollment): bool => $enrollment->managedBillingService?->category === 'verification')
                ->pluck('status'));
        $serviceModel = match (true) {
            $managedStatuses->contains('active') => 'Managed Service',
            $managedStatuses->contains('requested') => 'Hybrid',
            default => 'Self-Managed',
        };

        return [
            'type' => $organization->dso_id ? 'DSO Organization' : ($organization->clinics_count > 1 ? 'Multi Location' : 'Solo Practice'),
            'service_model' => $serviceModel,
            'subscription' => $subscription?->subscriptionPlan?->name ?? 'Not assigned',
            'subscription_status' => $subscription?->status ? str($subscription->status)->headline()->toString() : 'Not assigned',
            'onboarding' => $organization->onboarding_status ? str($organization->onboarding_status)->replace('_', ' ')->headline()->toString() : 'Pending',
            'status' => $organization->status ? 'Active' : 'Inactive',
            'view_url' => OrganizationResource::getUrl('view', ['record' => $organization]),
            'manage_url' => OrganizationWorkspace::getUrl([
                'record' => $organization->public_id ?: $organization->getKey(),
            ]),
        ];
    }
}
