<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Saas\Resources\Clinics\ClinicResource;
use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use App\Filament\Saas\Resources\SaasEntitlementAuditLogs\SaasEntitlementAuditLogResource;
use App\Filament\Saas\Resources\Subscriptions\SubscriptionResource;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\User;
use App\Support\SaasNotifications;
use App\Support\SaasSupportAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use UnitEnum;

class OrganizationWorkspace extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Client Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Organization Workspace';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Organization Workspace';

    protected static ?string $slug = 'client-workspace/{record}';

    protected string $view = 'filament.saas.pages.organization-workspace';

    public Organization $organization;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('organizations') ?? false;
    }

    public function mount(string $record): void
    {
        $this->organization = Organization::query()
            ->with(['dso', 'accountManager'])
            ->withCount(['clinics', 'locations', 'users', 'providers', 'subscriptions', 'invoices'])
            ->where(function (Builder $query) use ($record): void {
                $query->where('public_id', $record);

                if (ctype_digit($record)) {
                    $query->orWhere($query->getModel()->qualifyColumn('id'), (int) $record);
                }
            })
            ->firstOr(fn () => throw new ModelNotFoundException);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startSupportAccess')
                ->label('Enter Support Mode')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->outlined()
                ->visible(fn (): bool => SaasSupportAccess::active() === null)
                ->form([
                    Select::make('clinic_id')
                        ->label('Clinic scope')
                        ->placeholder('Entire organization')
                        ->options(fn (): array => $this->organization->clinics()
                            ->orderBy('clinic_name')
                            ->pluck('clinic_name', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),
                    Textarea::make('reason')
                        ->label('Reason for support access')
                        ->helperText('Required for HIPAA-safe support auditing.')
                        ->rows(3)
                        ->minLength(8)
                        ->maxLength(500)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    abort_unless(auth()->user()?->canPerformSaasModuleAction('organizations', 'update'), 403);

                    $clinic = filled($data['clinic_id'] ?? null)
                        ? $this->organization->clinics()->whereKey($data['clinic_id'])->firstOrFail()
                        : null;

                    SaasSupportAccess::start(auth()->user(), $this->organization, $clinic, (string) $data['reason']);

                    Notification::make()
                        ->title('Support mode started')
                        ->body('All support access for this client context is now visible and logged.')
                        ->warning()
                        ->send();
                }),
            Action::make('endSupportAccess')
                ->label('End Support Mode')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->visible(fn (): bool => SaasSupportAccess::active() !== null)
                ->requiresConfirmation()
                ->action(function (): void {
                    SaasSupportAccess::end(auth()->user());

                    Notification::make()
                        ->title('Support mode ended')
                        ->success()
                        ->send();
                }),
            Action::make('clientRegistry')
                ->label('Client Registry')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(OrganizationResource::getUrl()),
            Action::make('editOrganization')
                ->label('Edit Organization')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn (): string => OrganizationResource::getUrl('edit', ['record' => $this->organization])),
        ];
    }

    public function tabs(): array
    {
        return collect([
            'overview' => 'Overview',
            'clinics' => 'Clinics',
            'providers' => 'Providers',
            'users' => 'Users',
            'verification' => 'Verification',
            'billing' => 'Billing',
            'activity' => 'Activity',
            'settings' => 'Settings',
        ])->map(fn (string $label, string $key): array => [
            'key' => $key,
            'label' => $label,
            'active' => $this->activeTab() === $key,
            'url' => static::getUrl([
                'record' => $this->routeRecordKey(),
                'tab' => $key,
            ]),
        ])->values()->all();
    }

    public function activeTab(): string
    {
        $tab = request()->query('tab');

        return in_array($tab, ['overview', 'clinics', 'providers', 'users', 'verification', 'billing', 'activity', 'settings'], true)
            ? $tab
            : 'overview';
    }

    public function summary(): array
    {
        $subscription = $this->organization->currentSubscription();

        return [
            'client_type' => $this->clientTypeLabel(),
            'verification_model' => $this->verificationModelLabel(),
            'subscription' => $subscription?->subscriptionPlan?->name ?? 'No active plan',
            'subscription_status' => $subscription?->status ? str($subscription->status)->headline()->toString() : 'Not active',
            'status' => $this->organization->status ? 'Active' : 'Inactive',
            'lifecycle' => $this->formatState($this->organization->lifecycle_status),
            'onboarding' => $this->formatState($this->organization->onboarding_status),
            'manager' => $this->organization->accountManager?->name ?? 'Unassigned',
        ];
    }

    public function kpis(): array
    {
        return [
            ['label' => 'Clinics', 'value' => $this->organization->clinics_count, 'meta' => 'operating locations'],
            ['label' => 'Locations', 'value' => $this->organization->locations_count, 'meta' => 'physical addresses'],
            ['label' => 'Users', 'value' => $this->organization->users_count, 'meta' => 'assigned client users'],
            ['label' => 'Providers', 'value' => $this->organization->providers_count, 'meta' => 'clinic-owned provider records'],
            ['label' => 'Subscriptions', 'value' => $this->organization->subscriptions_count, 'meta' => 'plan records'],
        ];
    }

    public function clinics(): array
    {
        return $this->organization->clinics()
            ->withCount(['locations', 'users', 'providers', 'billingWorkItems'])
            ->orderBy('clinic_name')
            ->limit(8)
            ->get()
            ->map(fn (Clinic $clinic): array => [
                'name' => $clinic->clinic_name,
                'status' => $clinic->status ? 'Active' : 'Inactive',
                'service_status' => $this->formatState($clinic->service_status),
                'verification_model' => $this->clinicVerificationModelLabel($clinic),
                'locations' => $clinic->locations_count,
                'users' => $clinic->users_count,
                'providers' => $clinic->providers_count,
                'work_items' => $clinic->billing_work_items_count,
                'url' => ClinicResource::getUrl('view', ['record' => $clinic]),
            ])
            ->all();
    }

    public function providers(): array
    {
        return $this->organization->providers()
            ->with(['user', 'clinic', 'location'])
            ->withCount('appointments')
            ->orderByDesc('status')
            ->orderBy('id')
            ->limit(12)
            ->get()
            ->map(fn (Provider $provider): array => [
                'name' => $provider->display_name,
                'user' => $provider->user?->name ?? 'No linked user',
                'clinic' => $provider->clinic?->clinic_name ?? 'No clinic',
                'location' => $provider->location?->location_name ?? 'No location',
                'specialization' => $provider->specialization ?: 'Not set',
                'npi' => $provider->npi_number ?: '-',
                'visits' => $provider->appointments_count,
                'status' => $provider->status ? 'Active' : 'Inactive',
            ])
            ->all();
    }

    public function clientUsers(): array
    {
        return $this->organization->users()
            ->with(['roles', 'clinic', 'location'])
            ->orderByDesc('status')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getPrimaryRoleLabel() ?: 'Not assigned',
                'clinic' => $user->clinic?->clinic_name ?: 'Not assigned',
                'location' => $user->location?->location_name ?: 'Not assigned',
                'status' => $user->status ? 'Active' : 'Inactive',
                'verified' => $user->hasVerifiedEmail() ? 'Verified' : 'Pending',
                'access' => $user->canAccessClinicWorkspace() ? 'Ready' : 'Blocked',
                'access_detail' => $this->clientUserAccessDetail($user),
                'manageable' => ! $user->hasAnyRole(array_keys(User::saasRoleOptions())),
            ])
            ->all();
    }

    public function manageClientUserAction(): Action
    {
        return Action::make('manageClientUser')
            ->label('Manage')
            ->icon('heroicon-o-user-circle')
            ->color('gray')
            ->modalHeading(fn (array $arguments): string => 'Manage access - '.$this->clientUser($arguments)->name)
            ->modalDescription('Update the client role and operating assignment. Platform administrator roles cannot be changed here.')
            ->modalSubmitActionLabel('Save Access')
            ->fillForm(function (array $arguments): array {
                $user = $this->clientUser($arguments);

                return [
                    'selected_role' => $user->getPrimaryRoleName(),
                    'clinic_id' => $user->clinic_id,
                    'location_id' => $user->location_id,
                    'status' => $user->status,
                ];
            })
            ->form([
                Select::make('selected_role')
                    ->label('Client role')
                    ->options(User::clinicRoleOptions())
                    ->native(false)
                    ->required(),
                Select::make('clinic_id')
                    ->label('Clinic')
                    ->options(fn (): array => $this->organization->clinics()
                        ->orderBy('clinic_name')
                        ->pluck('clinic_name', 'id')
                        ->all())
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label('Location')
                    ->options(fn (): array => $this->organization->locations()
                        ->with('clinic:id,clinic_name')
                        ->orderBy('location_name')
                        ->get()
                        ->mapWithKeys(fn ($location): array => [
                            $location->getKey() => ($location->clinic?->clinic_name ? $location->clinic->clinic_name.' - ' : '').$location->location_name,
                        ])
                        ->all())
                    ->native(false)
                    ->searchable()
                    ->preload(),
                Toggle::make('status')
                    ->label('Account active')
                    ->helperText('Inactive users can sign in only to the restricted profile fallback and cannot enter a workspace.'),
            ])
            ->action(function (array $arguments, array $data): void {
                abort_unless(auth()->user()?->canPerformSaasModuleAction('users', 'update'), 403);

                $user = $this->clientUser($arguments);
                abort_if($user->hasAnyRole(array_keys(User::saasRoleOptions())), 403);

                $clinic = $this->organization->clinics()->whereKey($data['clinic_id'])->firstOrFail();
                $locationId = filled($data['location_id'] ?? null)
                    ? $clinic->locations()->whereKey($data['location_id'])->value('id')
                    : null;

                abort_if(filled($data['location_id'] ?? null) && ! $locationId, 422, 'The selected location does not belong to this clinic.');

                $user->update([
                    'organization_id' => $this->organization->getKey(),
                    'clinic_id' => $clinic->getKey(),
                    'location_id' => $locationId,
                    'status' => (bool) $data['status'],
                ]);
                $user->syncRoles([(string) $data['selected_role']]);

                SaasNotifications::userUpdated($user->fresh(['roles']), auth()->user());

                Notification::make()
                    ->title('Client access updated')
                    ->body($user->name.' can now be reviewed from the Users tab.')
                    ->success()
                    ->send();
            });
    }

    public function quickActions(): array
    {
        return [
            ['label' => 'Add Clinic', 'description' => 'Create another clinic under this client.', 'url' => ClinicResource::getUrl('create', [
                'organization_id' => $this->organization->getKey(),
            ])],
            ['label' => 'Support Audit Trail', 'description' => 'Review support access and provider support changes.', 'url' => SaasEntitlementAuditLogResource::getUrl('index')],
            ['label' => 'Manage Subscription', 'description' => 'Review plan and service status.', 'url' => SubscriptionResource::getUrl()],
            ['label' => 'Edit Organization', 'description' => 'Update legal, billing, and owner details.', 'url' => OrganizationResource::getUrl('edit', ['record' => $this->organization])],
        ];
    }

    public function supportAccessSummary(): array
    {
        $supportAccess = SaasSupportAccess::active();
        $isCurrentClient = SaasSupportAccess::isActiveForOrganization($this->organization);

        return [
            'active' => $supportAccess !== null,
            'current_client' => $isCurrentClient,
            'title' => $supportAccess
                ? ($isCurrentClient ? 'Support mode active' : 'Support mode active elsewhere')
                : 'Support mode inactive',
            'organization' => $supportAccess['organization_name'] ?? null,
            'clinic' => $supportAccess['clinic_name'] ?? 'Entire organization',
            'reason' => $supportAccess['reason'] ?? 'Start support mode before changing sensitive clinic data.',
            'started_at' => filled($supportAccess['started_at'] ?? null)
                ? Carbon::parse($supportAccess['started_at'])->format('M d, Y h:i A')
                : null,
            'audit_url' => SaasEntitlementAuditLogResource::getUrl('index'),
        ];
    }

    protected function routeRecordKey(): string
    {
        return (string) ($this->organization->public_id ?: $this->organization->getKey());
    }

    protected function clientTypeLabel(): string
    {
        if ($this->organization->dso_id) {
            return 'DSO Organization';
        }

        return $this->organization->clinics_count > 1 ? 'Multi Location' : 'Solo Practice';
    }

    protected function verificationModelLabel(): string
    {
        $statuses = $this->organization->clinics()
            ->pluck('managed_services_status')
            ->filter()
            ->unique()
            ->values();

        if ($statuses->contains('requested')) {
            return 'Hybrid';
        }

        if ($statuses->intersect(['active', 'trial'])->isNotEmpty()) {
            return 'Managed Service';
        }

        return 'Self-Service';
    }

    protected function clinicVerificationModelLabel(Clinic $clinic): string
    {
        return match ($clinic->managed_services_status) {
            'active', 'trial' => 'Managed Service',
            'requested' => 'Hybrid',
            default => 'Self-Service',
        };
    }

    protected function formatState(?string $state): string
    {
        return filled($state) ? str($state)->replace('_', ' ')->headline()->toString() : 'Not set';
    }

    protected function clientUser(array $arguments): User
    {
        return $this->organization->users()
            ->with(['roles', 'clinic', 'location'])
            ->whereKey($arguments['user'] ?? null)
            ->firstOrFail();
    }

    protected function clientUserAccessDetail(User $user): string
    {
        if (! $user->status) {
            return 'Activate the account.';
        }

        if (! $user->hasAnyRole(array_keys(User::clinicRoleOptions()))) {
            return 'Assign a client role.';
        }

        if (! $user->clinic) {
            return 'Assign a clinic.';
        }

        if (! $user->clinic->hasActiveVerificationServices() && ! $user->clinic->hasActiveClinicOperations()) {
            return 'Activate a clinic service.';
        }

        return $user->canAccessClinicWorkspace()
            ? 'Clinic workspace available.'
            : 'Review role permissions.';
    }
}
