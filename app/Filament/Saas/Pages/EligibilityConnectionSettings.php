<?php

namespace App\Filament\Saas\Pages;

use App\Services\Eligibility\DentalXChangeConnectionService;
use App\Services\Eligibility\EligibilityDemonstration;
use App\Services\Eligibility\EligibilityProviderCatalog;
use App\Services\Eligibility\PVerifyConnectionService;
use App\Services\Eligibility\StediConnectionService;
use App\Services\Eligibility\VyneConnectionService;
use App\Services\Eligibility\ZuubConnectionService;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Livewire\Attributes\Locked;
use UnitEnum;

class EligibilityConnectionSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Eligibility Connection';

    protected static ?string $title = 'Eligibility Connection';

    protected static ?string $slug = 'eligibility-connection';

    protected static ?int $navigationSort = 21;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.saas.pages.eligibility-connection-settings';

    #[Locked]
    public string $environment = 'sandbox';

    #[Locked]
    public string $provider = 'zuub';

    public ?array $data = [];

    public string $scenario = 'partial';

    public string $sandboxPayer = 'PRINCIPAL';

    public ?array $testResult = null;

    public ?array $demoResult = null;

    public static function canAccess(): bool
    {
        return (auth()->user()?->isSaasAdmin() ?? false)
            && (auth()->user()?->canAccessSaasModule('settings') ?? false);
    }

    public function getTitle(): string
    {
        return EligibilityProviderCatalog::PROVIDERS[$this->provider].' Settings';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->provider = (string) request()->query('provider', 'zuub');
        app(EligibilityProviderCatalog::class)->validateProvider($this->provider);
        if ($this->storageReady()) {
            $this->fillConnection();
        }
    }

    public function storageReady(): bool
    {
        return DatabaseSchema::hasTable('eligibility_connections') && DatabaseSchema::hasTable('eligibility_connection_events')
            && ($this->provider !== 'vyne' || DatabaseSchema::hasColumn('eligibility_connections', 'eligibility_enabled'));
    }

    public function selectEnvironment(string $environment): void
    {
        $this->authorizeSettings();
        abort_unless(in_array($environment, ['sandbox', 'production'], true), 422);
        $this->environment = $environment;
        $this->testResult = null;
        $this->resetValidation();
        $this->fillConnection();
    }

    public function form(Schema $schema): Schema
    {
        if (in_array($this->provider, ['vyne', 'pverify'], true)) {
            return $schema->statePath('data')->components([
                TextInput::make('client_id')->label($this->provider === 'pverify' ? 'New Client API ID' : 'New Client ID')->password()->autocomplete('new-password')->maxLength(1024)->visible($this->provider === 'pverify' || $this->environment === 'production'),
                TextInput::make('client_secret')->label('New Client Secret')->password()->autocomplete('new-password')->maxLength(2048)->visible($this->provider === 'pverify' || $this->environment === 'production'),
                Toggle::make('remove_key')->label('Remove saved credentials')->default(false)->visible($this->provider === 'pverify' || $this->environment === 'production'),
                Toggle::make('enabled')->label($this->provider === 'vyne' && $this->environment === 'sandbox' ? 'Enable sandbox API tests' : 'Enable authentication tests')->default(false),
                TextInput::make('password')->label('Confirm your administrator password')->password()->autocomplete('current-password')->required(),
            ]);
        }

        return $schema->statePath('data')->components([
            TextInput::make('api_key')->label(in_array($this->provider, ['dentalxchange', 'stedi'], true) ? 'New API key' : 'New API credential')->password()->autocomplete('new-password')->maxLength(4096),
            Toggle::make('remove_key')->label('Remove saved credential')->default(false),
            Toggle::make('enabled')->label(match ($this->provider) {
                'dentalxchange' => 'Enable non-patient health checks',
                'stedi' => 'Enable payer-directory connection tests',
                default => 'Enable non-patient connection tests',
            })->default(false),
            TextInput::make('password')->label('Confirm your administrator password')->password()->autocomplete('current-password')->required(),
        ]);
    }

    public function save(): void
    {
        $this->authorizeSettings();
        try {
            $state = $this->form->getState();
            validator($state, ['password' => ['required', 'current_password']])->validate();
            $this->service()->save(auth()->user(), $this->environment, $state);
        } finally {
            $this->data['password'] = null;
            $this->data['api_key'] = null;
            $this->data['client_id'] = null;
            $this->data['client_secret'] = null;
        }
        $this->testResult = null;
        $this->fillConnection();
        Notification::make()->title('Connection settings saved')->success()->send();
    }

    public function testConnection(): void
    {
        $this->authorizeSettings();
        $this->testResult = $this->provider === 'vyne'
            ? $this->service()->probe(auth()->user(), $this->environment, $this->sandboxPayer)
            : $this->service()->probe(auth()->user(), $this->environment);
    }

    public function runDemonstration(): void
    {
        $this->authorizeSettings();
        abort_unless($this->provider === 'zuub', 403);
        $this->demoResult = app(EligibilityDemonstration::class)->run($this->scenario);
    }

    public function connectionDetails(): array
    {
        $this->authorizeSettings();
        $connection = $this->service()->connection($this->environment);

        return [
            'has_key' => filled($connection->api_key),
            'checks' => $this->service()->readiness($connection),
            'last_checked' => $connection->last_checked_at?->format('M d, Y H:i').' UTC',
            'last_status' => $connection->last_check_status ?: 'Not tested',
            'events' => $connection->events()->latest('id')->limit(8)->get(),
        ];
    }

    protected function fillConnection(): void
    {
        $connection = $this->service()->connection($this->environment);
        $this->form->fill(['api_key' => null, 'client_id' => null, 'client_secret' => null, 'password' => null, 'enabled' => $connection->enabled, 'remove_key' => false]);
    }

    protected function service(): ZuubConnectionService|VyneConnectionService
    {
        return app(match ($this->provider) {
            'vyne' => VyneConnectionService::class,
            'dentalxchange' => DentalXChangeConnectionService::class,
            'pverify' => PVerifyConnectionService::class,
            'stedi' => StediConnectionService::class,
            default => ZuubConnectionService::class,
        });
    }

    protected function authorizeSettings(): void
    {
        abort_unless(static::canAccess(), 403);
        app(EligibilityProviderCatalog::class)->validateProvider($this->provider);
        abort_unless($this->storageReady(), 503, 'Apply the eligibility connection database update first.');
    }
}
