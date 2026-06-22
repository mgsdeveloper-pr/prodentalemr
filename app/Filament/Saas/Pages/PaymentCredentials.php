<?php

namespace App\Filament\Saas\Pages;

use App\Models\SaasSetting;
use App\Support\PayPalGateway;
use App\Support\StripeGateway;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

class PaymentCredentials extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Payment Credentials';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Payment Credentials';

    protected static ?string $slug = 'payment-credentials';

    protected string $view = 'filament.saas.pages.payment-credentials';

    public ?array $data = [];

    public string $activeProvider = 'stripe';

    protected SaasSetting $settings;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessSaasModule('settings') ?? false;
    }

    public function mount(): void
    {
        $this->settings = SaasSetting::current();

        $this->form->fill([
            ...$this->settings->toArray(),
            'stripe_secret_key' => null,
            'stripe_webhook_secret' => null,
            'stripe_webhook_url' => StripeGateway::webhookUrl(),
            'paypal_client_secret' => null,
            'paypal_webhook_url' => PayPalGateway::webhookUrl(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Stripe')
                    ->description('Connect Stripe first for hosted checkout, invoice payment links, and webhook-based payment confirmation.')
                    ->visible(fn (): bool => $this->activeProvider === 'stripe')
                    ->schema([
                        Toggle::make('stripe_enabled')
                            ->label('Stripe status')
                            ->default(false),
                        Select::make('stripe_environment')
                            ->label('Select environment')
                            ->options([
                                'test' => 'Test',
                                'live' => 'Live',
                            ])
                            ->default('test')
                            ->required()
                            ->native(false),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('stripe_publishable_key')
                                    ->label('Stripe publishable key')
                                    ->maxLength(65535)
                                    ->columnSpan(1),
                                TextInput::make('stripe_secret_key')
                                    ->label('Stripe secret key')
                                    ->password()
                                    ->revealable()
                                    ->helperText('Leave blank to keep the currently saved Stripe secret key.')
                                    ->maxLength(65535)
                                    ->columnSpan(1),
                                TextInput::make('stripe_webhook_secret')
                                    ->label('Stripe webhook secret')
                                    ->password()
                                    ->revealable()
                                    ->helperText('Add the `whsec_...` secret from your Stripe webhook endpoint. Leave blank to keep the currently saved secret.')
                                    ->maxLength(65535)
                                    ->columnSpan(1),
                                Placeholder::make('stripe_webhook_url')
                                    ->label('Webhook URL')
                                    ->content(fn (): string => StripeGateway::webhookUrl())
                                    ->columnSpan(1),
                            ]),
                    ]),
                Section::make('PayPal')
                    ->description('Connect PayPal Orders v2 for hosted checkout, capture-on-return, and verified webhooks.')
                    ->visible(fn (): bool => $this->activeProvider === 'paypal')
                    ->schema([
                        Toggle::make('paypal_enabled')
                            ->label('PayPal status')
                            ->default(false),
                        Select::make('paypal_environment')
                            ->label('Select environment')
                            ->options([
                                'sandbox' => 'Sandbox',
                                'live' => 'Live',
                            ])
                            ->default('sandbox')
                            ->required()
                            ->native(false),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('paypal_client_id')
                                    ->label('PayPal client ID')
                                    ->maxLength(65535)
                                    ->columnSpan(1),
                                TextInput::make('paypal_client_secret')
                                    ->label('PayPal client secret')
                                    ->password()
                                    ->revealable()
                                    ->helperText('Leave blank to keep the currently saved PayPal client secret.')
                                    ->maxLength(65535)
                                    ->columnSpan(1),
                                TextInput::make('paypal_webhook_id')
                                    ->label('PayPal webhook ID')
                                    ->helperText('Add the webhook ID from your PayPal developer app so webhook signatures can be verified.')
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                Placeholder::make('paypal_webhook_url')
                                    ->label('Webhook URL')
                                    ->content(fn (): string => PayPalGateway::webhookUrl())
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testStripeConnection')
                ->label('Test Stripe')
                ->visible(fn (): bool => $this->activeProvider === 'stripe')
                ->action('testStripeConnection'),
            Action::make('testPayPalConnection')
                ->label('Test PayPal')
                ->visible(fn (): bool => $this->activeProvider === 'paypal')
                ->action('testPayPalConnection'),
            Action::make('save')
                ->label('Save payment credentials')
                ->submit('save'),
        ];
    }

    public function showProvider(string $provider): void
    {
        if (! in_array($provider, ['stripe', 'paypal'], true)) {
            return;
        }

        $this->activeProvider = $provider;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $persistedSecret = $this->settings->stripe_secret_key;
        $persistedWebhookSecret = $this->settings->stripe_webhook_secret;
        $newSecret = $data['stripe_secret_key'] ?? null;
        $newWebhookSecret = $data['stripe_webhook_secret'] ?? null;
        $persistedPayPalSecret = $this->settings->paypal_client_secret;
        $newPayPalSecret = $data['paypal_client_secret'] ?? null;

        unset(
            $data['stripe_webhook_url'],
            $data['stripe_secret_key'],
            $data['stripe_webhook_secret'],
            $data['paypal_webhook_url'],
            $data['paypal_client_secret'],
        );

        if (filled($newSecret)) {
            $data['stripe_secret_key'] = $newSecret;
        } elseif (filled($persistedSecret)) {
            $data['stripe_secret_key'] = $persistedSecret;
        }

        if (filled($newWebhookSecret)) {
            $data['stripe_webhook_secret'] = $newWebhookSecret;
        } elseif (filled($persistedWebhookSecret)) {
            $data['stripe_webhook_secret'] = $persistedWebhookSecret;
        }

        if (filled($newPayPalSecret)) {
            $data['paypal_client_secret'] = $newPayPalSecret;
        } elseif (filled($persistedPayPalSecret)) {
            $data['paypal_client_secret'] = $persistedPayPalSecret;
        }

        $this->settings->update($data);

        Notification::make()
            ->title('Payment credentials saved')
            ->body('Stripe and PayPal payment settings are ready for invoice payment links and webhooks.')
            ->success()
            ->send();
    }

    public function testStripeConnection(): void
    {
        $state = $this->form->getState();
        $state['stripe_secret_key'] = filled($state['stripe_secret_key'] ?? null)
            ? $state['stripe_secret_key']
            : $this->settings->stripe_secret_key;

        try {
            $result = StripeGateway::testConnection($state);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Stripe test failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Stripe connection successful')
            ->body('Stripe ' . strtoupper((string) $result['environment']) . ' credentials are working' . (($result['livemode'] ?? false) ? ' in live mode.' : ' in test mode.'))
            ->success()
            ->send();
    }

    public function testPayPalConnection(): void
    {
        $state = $this->form->getState();
        $state['paypal_client_secret'] = filled($state['paypal_client_secret'] ?? null)
            ? $state['paypal_client_secret']
            : $this->settings->paypal_client_secret;

        try {
            $result = PayPalGateway::testConnection($state);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('PayPal test failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('PayPal connection successful')
            ->body('PayPal ' . strtoupper((string) $result['environment']) . ' credentials are working.')
            ->success()
            ->send();
    }
}
