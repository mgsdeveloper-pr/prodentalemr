<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\OnboardingDraft;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\SaasNotifications;
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
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

class TenantOnboarding extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|UnitEnum|null $navigationGroup = 'Tenant Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Organization Onboarding';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Organization Onboarding';

    protected static ?string $slug = 'organization-onboarding';

    protected string $view = 'filament.saas.pages.tenant-onboarding';

    public ?array $data = [];

    protected ?OnboardingDraft $draft = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('organizations') ?? false;
    }

    public function mount(): void
    {
        $defaults = [
            'organization_status' => true,
            'clinic_code' => $this->generateClinicCode(),
            'clinic_timezone' => 'America/New_York',
            'clinic_status' => true,
            'location_country' => 'USA',
            'location_status' => true,
            'owner_status' => true,
            'attach_subscription' => SubscriptionPlan::query()->where('status', true)->exists(),
            'subscription_start_date' => now()->toDateString(),
            'subscription_status' => 'active',
        ];

        $this->draft = OnboardingDraft::query()
            ->where('user_id', auth()->id())
            ->where('type', 'organization_onboarding')
            ->first();

        $this->form->fill([
            ...$defaults,
            ...($this->draft?->data ?? []),
        ]);
    }

    public function updated($name, $value): void
    {
        if (! str_starts_with((string) $name, 'data.')) {
            return;
        }

        $this->syncDraft();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    Step::make('Organization')
                        ->description('Create the parent practice group or business record.')
                        ->schema([
                            TextInput::make('organization_name')
                                ->label('Organization name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('organization_owner_name')
                                ->label('Primary owner name')
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
                                ->default(true)
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Clinic')
                        ->description('Set up the first clinic under this organization.')
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
                                ->default('America/New_York')
                                ->native(false),
                            Toggle::make('clinic_status')
                                ->label('Clinic active')
                                ->default(true)
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Location')
                        ->description('Create the clinic\'s first operating location.')
                        ->schema([
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
                                })
                                ->required(),
                            Select::make('location_city')
                                ->label('City')
                                ->options(fn (Get $get): array => UsLocationOptions::cityOptions($get('location_state')))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set, ?string $state) => $set('location_zip_code', UsLocationOptions::zipFor($get('location_state'), $state)))
                                ->required(),
                            TextInput::make('location_zip_code')
                                ->label('ZIP code')
                                ->maxLength(255),
                            Select::make('location_country')
                                ->label('Country')
                                ->required()
                                ->default('USA')
                                ->options(['USA' => 'USA'])
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('location_phone')
                                ->label('Location phone')
                                ->tel()
                                ->maxLength(255),
                            Toggle::make('location_status')
                                ->label('Location active')
                                ->default(true)
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Owner Account')
                        ->description('Create the first clinic owner login with clinic_admin access.')
                        ->schema([
                            TextInput::make('owner_name')
                                ->label('Owner full name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('owner_email')
                                ->label('Owner login email')
                                ->email()
                                ->required()
                                ->unique(User::class, 'email')
                                ->maxLength(255),
                            TextInput::make('owner_phone')
                                ->label('Owner phone')
                                ->tel()
                                ->required()
                                ->maxLength(255),
                            TextInput::make('owner_password')
                                ->label('Temporary password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(8)
                                ->confirmed(),
                            TextInput::make('owner_password_confirmation')
                                ->label('Confirm temporary password')
                                ->password()
                                ->revealable()
                                ->required(),
                            Toggle::make('owner_status')
                                ->label('Owner account active')
                                ->default(true)
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Subscription')
                        ->description('Attach a billing plan during organization onboarding.')
                        ->schema([
                            Toggle::make('attach_subscription')
                                ->label('Attach a subscription now')
                                ->default(true)
                                ->live(),
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
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription')),
                            DatePicker::make('subscription_start_date')
                                ->label('Start date')
                                ->default(now()->toDateString())
                                ->required(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription')),
                            DatePicker::make('subscription_end_date')
                                ->label('End date')
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription')),
                            Select::make('subscription_status')
                                ->label('Subscription status')
                                ->default('active')
                                ->options([
                                    'active' => 'Active',
                                    'trial' => 'Trial',
                                    'paused' => 'Paused',
                                    'cancelled' => 'Cancelled',
                                    'expired' => 'Expired',
                                ])
                                ->required(fn (Get $get): bool => (bool) $get('attach_subscription'))
                                ->visible(fn (Get $get): bool => (bool) $get('attach_subscription')),
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
                $organization = Organization::create([
                    'name' => $state['organization_name'],
                    'owner_name' => $state['organization_owner_name'],
                    'email' => $state['organization_email'] ?? null,
                    'phone' => $state['organization_phone'] ?? null,
                    'status' => (bool) $state['organization_status'],
                ]);

                $clinic = Clinic::create([
                    'organization_id' => $organization->id,
                    'clinic_name' => $state['clinic_name'],
                    'clinic_code' => $state['clinic_code'],
                    'timezone' => $state['clinic_timezone'],
                    'status' => (bool) $state['clinic_status'],
                ]);

                $location = Location::create([
                    'clinic_id' => $clinic->id,
                    'location_name' => $state['location_name'],
                    'address' => $state['location_address'] ?? null,
                    'city' => $state['location_city'] ?? null,
                    'state' => $state['location_state'] ?? null,
                    'zip_code' => $state['location_zip_code'] ?? null,
                    'country' => $state['location_country'],
                    'phone' => $state['location_phone'] ?? null,
                    'status' => (bool) $state['location_status'],
                ]);

                $owner = User::create([
                    'name' => $state['owner_name'],
                    'email' => $state['owner_email'],
                    'phone' => $state['owner_phone'],
                    'organization_id' => $organization->id,
                    'clinic_id' => $clinic->id,
                    'location_id' => $location->id,
                    'created_by' => auth()->id(),
                    'status' => (bool) $state['owner_status'],
                    'password' => Hash::make($state['owner_password']),
                ]);

                $owner->assignRole('clinic_admin');

                $subscription = null;

                if (! empty($state['attach_subscription']) && ! empty($state['subscription_plan_id'])) {
                    $subscription = Subscription::create([
                        'organization_id' => $organization->id,
                        'subscription_plan_id' => $state['subscription_plan_id'],
                        'start_date' => $state['subscription_start_date'],
                        'end_date' => $state['subscription_end_date'] ?? null,
                        'status' => $state['subscription_status'],
                    ]);
                }

                return compact('organization', 'clinic', 'location', 'owner', 'subscription');
            });
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Organization onboarding failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return null;
        }

        Notification::make()
            ->title('Organization created successfully')
            ->body($this->buildSuccessMessage($result))
            ->success()
            ->send();

        SaasNotifications::organizationOnboarded($result['organization'], $result['owner']);
        $this->deleteDraft();

        return redirect(OrganizationResource::getUrl('view', ['record' => $result['organization']]));
    }

    protected function syncDraft(): void
    {
        $state = $this->form->getState();

        if (! $this->hasMeaningfulDraftData($state)) {
            $this->deleteDraft();

            return;
        }

        $this->draft ??= OnboardingDraft::query()->firstOrNew([
            'user_id' => auth()->id(),
            'type' => 'organization_onboarding',
        ]);

        $this->draft->fill([
            'last_completed_step' => $this->estimateLastCompletedStep($state),
            'data' => $state,
        ]);

        $this->draft->save();

        if (! $this->draft->notification_sent_at) {
            SaasNotifications::incompleteOnboarding($this->draft);
        }
    }

    protected function deleteDraft(): void
    {
        if (! $this->draft) {
            return;
        }

        SaasNotifications::clearIncompleteOnboarding($this->draft);
        $this->draft->delete();
        $this->draft = null;
    }

    protected function hasMeaningfulDraftData(array $state): bool
    {
        return collect([
            $state['organization_name'] ?? null,
            $state['organization_owner_name'] ?? null,
            $state['organization_email'] ?? null,
            $state['organization_phone'] ?? null,
            $state['clinic_name'] ?? null,
            $state['location_name'] ?? null,
            $state['owner_name'] ?? null,
            $state['owner_email'] ?? null,
        ])->contains(fn (?string $value): bool => filled($value));
    }

    protected function estimateLastCompletedStep(array $state): int
    {
        if (filled($state['owner_name'] ?? null) || filled($state['owner_email'] ?? null)) {
            return 4;
        }

        if (filled($state['location_name'] ?? null) || filled($state['location_state'] ?? null)) {
            return 3;
        }

        if (filled($state['clinic_name'] ?? null)) {
            return 2;
        }

        return 1;
    }

    protected function generateClinicCode(): string
    {
        do {
            $code = 'CLN-' . Str::upper(Str::random(6));
        } while (Clinic::query()->where('clinic_code', $code)->exists());

        return $code;
    }

    protected function buildSuccessMessage(array $result): string
    {
        $message = "{$result['organization']->name} is ready. Owner login: {$result['owner']->email}";

        if ($result['subscription']) {
            $result['subscription']->loadMissing('subscriptionPlan');

            $message .= " Plan attached: {$result['subscription']->subscriptionPlan->name}.";
        }

        return $message;
    }
}
