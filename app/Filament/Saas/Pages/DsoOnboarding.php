<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Saas\Resources\Dsos\DsoResource;
use App\Models\Clinic;
use App\Models\Dso;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\UsLocationOptions;
use App\Support\UsTimezoneOptions;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

class DsoOnboarding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|UnitEnum|null $navigationGroup = 'Organizations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'DSO Onboarding';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = '';

    protected static ?string $slug = 'dso-onboarding';

    protected string $view = 'filament.saas.pages.dso-onboarding';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('organizations', 'add') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'dso_account_code' => $this->generateAccountCode(),
            'dso_country' => 'USA',
            'dso_status' => true,
            'dso_lifecycle_status' => 'onboarding',
            'dso_billing_mode' => 'centralized',
            'dso_service_status' => 'pending_setup',
            'organization_status' => true,
            'organization_lifecycle_status' => 'active',
            'organization_onboarding_status' => 'complete',
            'clinic_code' => $this->generateClinicCode(),
            'clinic_timezone' => 'America/New_York',
            'clinic_status' => true,
            'location_country' => 'USA',
            'location_status' => true,
            'dso_admin_status' => true,
            'attach_subscription' => SubscriptionPlan::query()->where('status', true)->exists(),
            'subscription_scope' => 'dso',
            'subscription_start_date' => now()->toDateString(),
            'subscription_status' => 'active',
            'service_status' => 'active',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    Step::make('DSO')
                        ->description('Create the parent enterprise account.')
                        ->schema([
                            TextInput::make('dso_name')
                                ->label('DSO name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('dso_legal_name')
                                ->label('Legal name')
                                ->maxLength(255),
                            TextInput::make('dso_account_code')
                                ->label('Account code')
                                ->required()
                                ->unique(Dso::class, 'account_code')
                                ->maxLength(255),
                            TextInput::make('dso_primary_contact_name')
                                ->label('Primary contact')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('dso_email')
                                ->label('DSO email')
                                ->email()
                                ->required()
                                ->maxLength(255),
                            TextInput::make('dso_phone')
                                ->label('DSO phone')
                                ->tel()
                                ->maxLength(255),
                            Select::make('dso_billing_mode')
                                ->label('Billing mode')
                                ->options([
                                    'centralized' => 'Centralized billing',
                                    'by_organization' => 'Bill by organization',
                                    'by_clinic' => 'Bill by clinic',
                                ])
                                ->required()
                                ->native(false),
                            Select::make('dso_lifecycle_status')
                                ->label('Lifecycle status')
                                ->options([
                                    'onboarding' => 'Onboarding',
                                    'active' => 'Active',
                                    'paused' => 'Paused',
                                    'suspended' => 'Suspended',
                                ])
                                ->required()
                                ->native(false),
                            Select::make('dso_service_status')
                                ->label('Service status')
                                ->options([
                                    'pending_setup' => 'Pending setup',
                                    'active' => 'Active',
                                    'trial' => 'Trial',
                                    'suspended' => 'Suspended',
                                ])
                                ->required()
                                ->native(false),
                            Toggle::make('dso_status')
                                ->label('DSO active')
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('First Organization')
                        ->description('Add the first organization under this DSO.')
                        ->schema([
                            TextInput::make('organization_name')
                                ->label('Organization name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('organization_owner_name')
                                ->label('Organization owner')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('organization_email')
                                ->label('Organization email')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('organization_phone')
                                ->label('Organization phone')
                                ->tel()
                                ->maxLength(255),
                            Toggle::make('organization_status')
                                ->label('Organization active')
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('First Clinic')
                        ->description('Create the first clinic and location in the network.')
                        ->schema([
                            TextInput::make('clinic_name')
                                ->label('Clinic name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('clinic_code')
                                ->label('Clinic code')
                                ->required()
                                ->unique(Clinic::class, 'clinic_code')
                                ->maxLength(255),
                            Select::make('clinic_timezone')
                                ->label('Timezone')
                                ->options(UsTimezoneOptions::options())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->native(false),
                            Toggle::make('clinic_status')
                                ->label('Clinic active')
                                ->required(),
                            TextInput::make('location_name')
                                ->label('Location name')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('location_address')
                                ->label('Address')
                                ->rows(3)
                                ->columnSpanFull(),
                            Select::make('location_state')
                                ->label('State')
                                ->options(UsLocationOptions::stateOptions())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                    $set('location_city', null);
                                    $set('location_zip_code', null);
                                    $set('clinic_timezone', UsTimezoneOptions::timezoneForState($get('location_state')));
                                }),
                            Select::make('location_city')
                                ->label('City')
                                ->options(fn (Get $get): array => UsLocationOptions::cityOptions($get('location_state')))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set, ?string $state) => $set('location_zip_code', UsLocationOptions::zipFor($get('location_state'), $state))),
                            TextInput::make('location_zip_code')
                                ->label('ZIP code')
                                ->maxLength(255),
                            Select::make('location_country')
                                ->label('Country')
                                ->options(['USA' => 'USA'])
                                ->default('USA')
                                ->disabled()
                                ->dehydrated(),
                            Toggle::make('location_status')
                                ->label('Location active')
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Plan')
                        ->description('Attach the first plan and service status.')
                        ->schema([
                            Toggle::make('attach_subscription')
                                ->label('Attach subscription now')
                                ->default(true)
                                ->live(),
                            Select::make('subscription_scope')
                                ->label('Subscription scope')
                                ->options([
                                    'dso' => 'DSO-wide',
                                    'organization' => 'First organization only',
                                    'clinic' => 'First clinic only',
                                ])
                                ->required(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->native(false),
                            Select::make('subscription_plan_id')
                                ->label('Subscription plan')
                                ->options(fn (): array => SubscriptionPlan::query()
                                    ->where('status', true)
                                    ->orderBy('price')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    $plan = filled($state) ? SubscriptionPlan::find($state) : null;

                                    if (! $plan) {
                                        return;
                                    }

                                    $status = $plan->trial_days ? 'trial' : 'active';
                                    $set('subscription_status', $status);
                                    $set('service_status', $status);
                                }),
                            DatePicker::make('subscription_start_date')
                                ->label('Start date')
                                ->required(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription')),
                            DatePicker::make('subscription_end_date')
                                ->label('End date')
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription')),
                            Select::make('subscription_status')
                                ->label('Subscription status')
                                ->options([
                                    'active' => 'Active',
                                    'trial' => 'Trial',
                                    'paused' => 'Paused',
                                    'cancelled' => 'Cancelled',
                                    'expired' => 'Expired',
                                ])
                                ->required(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->native(false),
                            Select::make('service_status')
                                ->label('Service status')
                                ->options([
                                    'active' => 'Active',
                                    'trial' => 'Trial',
                                    'pending_setup' => 'Pending setup',
                                    'suspended' => 'Suspended',
                                ])
                                ->required(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->native(false),
                            Select::make('managed_services_status')
                                ->label('Managed services')
                                ->default('not_enabled')
                                ->options([
                                    'not_enabled' => 'Not enabled',
                                    'requested' => 'Requested',
                                    'active' => 'Active',
                                ])
                                ->native(false),
                        ])
                        ->columns(2),
                    Step::make('DSO User')
                        ->description('Invite the first DSO admin user.')
                        ->schema([
                            TextInput::make('dso_admin_name')
                                ->label('DSO admin name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('dso_admin_email')
                                ->label('DSO admin email')
                                ->email()
                                ->required()
                                ->unique(User::class, 'email')
                                ->maxLength(255),
                            TextInput::make('dso_admin_phone')
                                ->label('DSO admin phone')
                                ->tel()
                                ->maxLength(255),
                            TextInput::make('dso_admin_password')
                                ->label('Temporary password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(8)
                                ->confirmed(),
                            TextInput::make('dso_admin_password_confirmation')
                                ->label('Confirm temporary password')
                                ->password()
                                ->revealable()
                                ->required(),
                            Toggle::make('dso_admin_status')
                                ->label('User active')
                                ->required(),
                        ])
                        ->columns(2),
                ])->persistStepInQueryString(),
            ]);
    }

    public function create(): mixed
    {
        $state = $this->form->getState();

        try {
            $result = DB::transaction(function () use ($state): array {
                $plan = (! empty($state['attach_subscription']) && ! empty($state['subscription_plan_id']))
                    ? SubscriptionPlan::find($state['subscription_plan_id'])
                    : null;

                $dso = Dso::create([
                    'name' => $state['dso_name'],
                    'legal_name' => $state['dso_legal_name'] ?? null,
                    'account_code' => $state['dso_account_code'],
                    'primary_contact_name' => $state['dso_primary_contact_name'],
                    'email' => $state['dso_email'],
                    'phone' => $state['dso_phone'] ?? null,
                    'country' => $state['dso_country'] ?? 'USA',
                    'lifecycle_status' => $state['dso_lifecycle_status'],
                    'billing_mode' => $state['dso_billing_mode'],
                    'service_status' => $state['dso_service_status'],
                    'status' => (bool) $state['dso_status'],
                    'account_manager_user_id' => auth()->id(),
                ]);

                $organization = Organization::create([
                    'dso_id' => $dso->id,
                    'name' => $state['organization_name'],
                    'owner_name' => $state['organization_owner_name'],
                    'email' => $state['organization_email'] ?? null,
                    'phone' => $state['organization_phone'] ?? null,
                    'status' => (bool) $state['organization_status'],
                    'lifecycle_status' => $state['organization_lifecycle_status'] ?? 'active',
                    'onboarding_status' => $state['organization_onboarding_status'] ?? 'complete',
                    'account_manager_user_id' => auth()->id(),
                ]);

                $clinic = Clinic::create([
                    'organization_id' => $organization->id,
                    'clinic_name' => $state['clinic_name'],
                    'clinic_code' => $state['clinic_code'],
                    'timezone' => $state['clinic_timezone'],
                    'status' => (bool) $state['clinic_status'],
                    'verification_services_enabled' => $plan?->includesVerification() ?? true,
                    'clinic_operations_enabled' => $plan?->includesPms() ?? true,
                    'service_status' => $state['service_status'] ?? 'active',
                    'pms_service_status' => ($plan?->includesPms() ?? true) ? ($state['service_status'] ?? 'active') : 'not_enabled',
                    'verification_service_status' => ($plan?->includesVerification() ?? true) ? ($state['service_status'] ?? 'active') : 'not_enabled',
                    'managed_services_status' => $state['managed_services_status'] ?? 'not_enabled',
                    'trial_ends_at' => $plan?->trial_days ? now()->addDays((int) $plan->trial_days)->toDateString() : null,
                    'demo_mode' => (bool) ($plan?->demo_mode_available && ($state['subscription_status'] ?? null) === 'trial'),
                    'account_manager_user_id' => auth()->id(),
                ]);

                $location = Location::create([
                    'clinic_id' => $clinic->id,
                    'location_name' => $state['location_name'],
                    'address' => $state['location_address'] ?? null,
                    'city' => $state['location_city'] ?? null,
                    'state' => $state['location_state'] ?? null,
                    'zip_code' => $state['location_zip_code'] ?? null,
                    'country' => $state['location_country'] ?? 'USA',
                    'status' => (bool) $state['location_status'],
                ]);

                $user = User::create([
                    'dso_id' => $dso->id,
                    'name' => $state['dso_admin_name'],
                    'email' => $state['dso_admin_email'],
                    'phone' => $state['dso_admin_phone'] ?? null,
                    'created_by' => auth()->id(),
                    'status' => (bool) $state['dso_admin_status'],
                    'password' => Hash::make($state['dso_admin_password']),
                    'allowed_workspaces' => ['dso'],
                    'default_workspace' => 'dso',
                ]);

                $user->assignRole('dso_admin');

                $subscription = null;

                if ($plan) {
                    $scope = $state['subscription_scope'] ?? 'dso';

                    $subscription = Subscription::create([
                        'dso_id' => $dso->id,
                        'organization_id' => in_array($scope, ['organization', 'clinic'], true) ? $organization->id : null,
                        'clinic_id' => $scope === 'clinic' ? $clinic->id : null,
                        'subscription_scope' => $scope,
                        'subscription_plan_id' => $plan->id,
                        'start_date' => $state['subscription_start_date'],
                        'end_date' => $state['subscription_end_date'] ?? null,
                        'status' => $state['subscription_status'],
                        'service_status' => $state['service_status'] ?? 'active',
                        'change_type' => 'new',
                        'effective_date' => $state['subscription_start_date'],
                        'trial_starts_at' => $plan->trial_days ? $state['subscription_start_date'] : null,
                        'trial_ends_at' => $plan->trial_days ? now()->addDays((int) $plan->trial_days)->toDateString() : null,
                        'is_demo' => (bool) ($plan->demo_mode_available && ($state['subscription_status'] ?? null) === 'trial'),
                        'account_manager_user_id' => auth()->id(),
                    ]);
                }

                return compact('dso', 'organization', 'clinic', 'location', 'user', 'subscription');
            });
        } catch (Throwable $exception) {
            Notification::make()
                ->title('DSO onboarding failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return null;
        }

        Notification::make()
            ->title('DSO created successfully')
            ->body($this->successMessage($result))
            ->success()
            ->send();

        return redirect(DsoResource::getUrl('view', ['record' => $result['dso']]));
    }

    protected function generateAccountCode(): string
    {
        do {
            $code = 'DSO-' . Str::upper(Str::random(6));
        } while (Dso::query()->where('account_code', $code)->exists());

        return $code;
    }

    protected function generateClinicCode(): string
    {
        do {
            $code = 'CLN-' . Str::upper(Str::random(6));
        } while (Clinic::query()->where('clinic_code', $code)->exists());

        return $code;
    }

    protected function successMessage(array $result): string
    {
        $message = "{$result['dso']->name} is ready with {$result['organization']->name} and {$result['clinic']->clinic_name}. DSO admin: {$result['user']->email}.";

        if ($result['subscription']) {
            $result['subscription']->loadMissing('subscriptionPlan');
            $message .= " Plan attached: {$result['subscription']->subscriptionPlan->name}.";
        }

        return $message;
    }
}
