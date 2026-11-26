<?php

use App\Filament\Saas\Pages\AdaProcedureCodeImport;
use App\Filament\Saas\Pages\NotificationCentre;
use App\Filament\Saas\Pages\PaymentCredentials;
use App\Filament\Saas\Resources\InsuranceCarriers\Pages\ImportInsuranceCarriers;
use App\Models\InsuranceCarrier;
use App\Models\SaasSetting;
use App\Models\User;
use App\Support\AdaProcedureCodeImportService;
use App\Support\InsuranceCarrierImportService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

beforeEach(function (): void {
    request()->attributes->remove('prodental.saas_settings.current');
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    Exceptions::fake();
    Http::preventStrayRequests();
});

it('guides staff when payment connection credentials are missing', function (): void {
    $settings = SaasSetting::current();
    $settings->update([
        'stripe_publishable_key' => null, 'stripe_secret_key' => null,
        'paypal_client_id' => null, 'paypal_client_secret' => null,
    ]);

    Livewire::test(PaymentCredentials::class)
        ->call('testStripeConnection')
        ->assertNotified(Notification::make()->title('Stripe test failed')
            ->body('Enter a Stripe publishable key and secret key before testing the connection.')->warning())
        ->call('showProvider', 'paypal')
        ->call('testPayPalConnection')
        ->assertNotified(Notification::make()->title('PayPal test failed')
            ->body('Enter a PayPal client ID and client secret before testing the connection.')->warning());

    Exceptions::assertNothingReported();
    Http::assertNothingSent();
});

it('keeps saved secrets when updating payment settings without entering replacements', function (): void {
    SaasSetting::current()->update([
        'stripe_secret_key' => 'sk_test_synthetic',
        'stripe_webhook_secret' => 'whsec_synthetic',
        'paypal_client_secret' => 'synthetic-paypal-secret',
    ]);

    Livewire::test(PaymentCredentials::class)
        ->set('data.stripe_publishable_key', 'pk_test_updated')
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Payment credentials saved');

    $settings = SaasSetting::query()->firstOrFail();
    expect($settings->stripe_publishable_key)->toBe('pk_test_updated')
        ->and($settings->stripe_secret_key)->toBe('sk_test_synthetic')
        ->and($settings->stripe_webhook_secret)->toBe('whsec_synthetic')
        ->and($settings->paypal_client_secret)->toBe('synthetic-paypal-secret');
});

it('reports Stripe failures and shows a safe message when testing saved keys', function (): void {
    $failure = new RuntimeException('Synthetic private Stripe error');
    $stripe = Mockery::mock(ClientInterface::class);
    $stripe->shouldReceive('request')->once()->andThrow($failure);
    $client = new ReflectionProperty(ApiRequestor::class, '_httpClient');
    $previousClient = $client->getValue();
    ApiRequestor::setHttpClient($stripe);

    try {
        SaasSetting::current()->update([
            'stripe_publishable_key' => 'pk_test_synthetic',
            'stripe_secret_key' => 'sk_test_synthetic',
        ]);

        Livewire::test(PaymentCredentials::class)
            ->assertSet('data.stripe_publishable_key', 'pk_test_synthetic')
            ->call('testStripeConnection')
            ->assertHasNoFormErrors()
            ->assertNotified(Notification::make()->title('Stripe test failed')
                ->body('Check that the Stripe keys match the selected environment, then try again. If the problem persists, ask your administrator to check the server logs.')->danger())
            ->assertDontSee($failure->getMessage());

        Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $failure);
    } finally {
        ApiRequestor::setHttpClient($previousClient);
    }
});

it('reports a PayPal rejection without showing the provider response to staff', function (): void {
    $details = 'Synthetic private PayPal response';
    SaasSetting::current()->update([
        'paypal_environment' => 'sandbox',
        'paypal_client_id' => 'synthetic-client',
        'paypal_client_secret' => 'synthetic-secret',
    ]);
    Http::fake(['https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['message' => $details], 401)]);

    Livewire::test(PaymentCredentials::class)
        ->call('showProvider', 'paypal')
        ->assertSet('data.paypal_client_id', 'synthetic-client')
        ->call('testPayPalConnection')
        ->assertHasNoFormErrors()
        ->assertNotified(Notification::make()->title('PayPal test failed')
            ->body('Check that the PayPal client ID and secret match the selected environment, then try again. If the problem persists, ask your administrator to check the server logs.')->danger())
        ->assertDontSee($details);

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === $details);
    Http::assertSentCount(1);
});

it('reports a test email failure and shows mail setup guidance', function (): void {
    $failure = new RuntimeException('Synthetic private mail transport error');
    Mail::shouldReceive('mailer')->once()->with('log')->andThrow($failure);

    Livewire::test(NotificationCentre::class)
        ->set('data.email_enabled', true)
        ->set('data.email_mailer', 'log')
        ->set('data.email_from_address', 'sender@example.test')
        ->set('data.test_email_recipient', 'qa@example.test')
        ->call('sendTestEmail')
        ->assertHasNoFormErrors()
        ->assertNotified(Notification::make()->title('Test email failed')
            ->body('Check the mail server settings and the recipient, then try again. If the problem persists, ask your administrator to check the server logs.')->danger())
        ->assertDontSee($failure->getMessage());

    Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $failure);
});

it('preserves actionable file errors in the SaaS import actions', function (string $page, string $action, string $title, string $header, string $message): void {
    Storage::fake('local');
    $file = UploadedFile::fake()->createWithContent('sample.csv', $header.PHP_EOL);

    Livewire::test($page)
        ->set('data.import_file', [$file])
        ->call($action)
        ->assertNotified(Notification::make()->title($title)->body($message)->warning())
        ->assertSet('previewResult', null);

    Exceptions::assertNothingReported();
})->with([
    'ADA preview' => [AdaProcedureCodeImport::class, 'previewCodes', 'Preview failed', 'code,description', 'The uploaded file does not contain ADA/CDT rows.'],
    'ADA import' => [AdaProcedureCodeImport::class, 'importCodes', 'Import failed', 'code,description', 'The uploaded file does not contain ADA/CDT rows.'],
    'insurance preview' => [ImportInsuranceCarriers::class, 'previewImport', 'Insurance preview failed', 'insurance_name,payer_id', 'The uploaded file does not contain any insurance rows.'],
    'insurance import' => [ImportInsuranceCarriers::class, 'importInsurance', 'Insurance import failed', 'insurance_name,payer_id', 'The uploaded file does not contain any insurance rows.'],
]);

it('reports unexpected import failures and replaces the toast with recovery guidance', function (
    string $page, string $action, string $service, string $method, string $title, string $message,
): void {
    Storage::fake('local');
    $failure = new RuntimeException('Synthetic private import error');
    $this->mock($service, fn ($mock) => $mock->shouldReceive($method)->once()->andThrow($failure));
    $file = UploadedFile::fake()->createWithContent('sample.csv', "insurance_name,payer_id\nSynthetic,QA-1");

    Livewire::test($page)
        ->set('data.import_file', [$file])
        ->call($action)
        ->assertNotified(Notification::make()->title($title)->body($message)->danger())
        ->assertSet('previewResult', null)
        ->assertDontSee($failure->getMessage());

    Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $failure);
})->with([
    'ADA preview' => [AdaProcedureCodeImport::class, 'previewCodes', AdaProcedureCodeImportService::class, 'previewFromStoredFile',
        'Preview failed', 'Try the downloaded ADA/CDT sample. If a valid file still fails, ask your administrator to check the server logs.'],
    'ADA import' => [AdaProcedureCodeImport::class, 'importCodes', AdaProcedureCodeImportService::class, 'importFromStoredFile',
        'Import failed', 'Review the code directory before retrying. If a valid file still fails, ask your administrator to check the server logs.'],
    'insurance preview' => [ImportInsuranceCarriers::class, 'previewImport', InsuranceCarrierImportService::class, 'preview',
        'Insurance preview failed', 'Try the downloaded insurance directory sample. If a valid file still fails, ask your administrator to check the server logs.'],
    'insurance import' => [ImportInsuranceCarriers::class, 'importInsurance', InsuranceCarrierImportService::class, 'import',
        'Insurance import failed', 'Try the downloaded insurance directory sample. If a valid file still fails, ask your administrator to check the server logs.'],
]);

it('reports unexpected insurance row errors and keeps validation instructions for invalid rows', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('insurance.csv', "insurance_name,payer_id\n,QA-1\nSynthetic,QA-2\n");
    $failure = new RuntimeException('Synthetic private insurance storage error');
    $service = new class($failure) extends InsuranceCarrierImportService
    {
        public function __construct(private RuntimeException $failure) {}

        protected function findCarrier(array $row): ?InsuranceCarrier
        {
            throw $this->failure;
        }
    };

    $result = $service->import(Storage::disk('local')->path('insurance.csv'));
    expect($result['failed'])->toBe(2)
        ->and($result['rows'][0]['message'])->toBe('Insurance Name is required.')
        ->and($result['rows'][1]['message'])->toBe('This row could not be processed. Ask your administrator to check the server logs before retrying.');
    Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $failure);
    Exceptions::assertReportedCount(1);
});
