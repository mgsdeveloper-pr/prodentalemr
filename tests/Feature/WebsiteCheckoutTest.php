<?php

use App\Models\SaasSetting;
use App\Models\SubscriptionPlan;
use App\Models\WebsiteCheckout;
use App\Models\WebsitePaymentAttempt;
use App\Models\WebsiteRegistration;
use App\Services\ClientOnboardingService;
use App\Services\WebsiteCheckout\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['website_registration.enabled' => true, 'website_registration.bridge_secret' => str_repeat('s', 40),
        'website_checkout.enabled' => true, 'website_checkout.stripe_webhook_secret' => 'whsec_checkout_test', 'website_checkout.paypal_webhook_id' => 'TEST-HOOK']);
    SaasSetting::query()->updateOrCreate(['id' => 1], ['stripe_enabled' => true, 'stripe_environment' => 'test', 'stripe_secret_key' => 'sk_test_not_real',
        'paypal_enabled' => true, 'paypal_environment' => 'sandbox', 'paypal_client_id' => 'fake-client', 'paypal_client_secret' => 'fake-secret']);
    $this->withHeaders(['X-Website-Key' => str_repeat('s', 40), 'X-Registration-Token' => str_repeat('a', 64)]);
    Http::preventStrayRequests();
});

function checkoutRegistration(array $planChanges = []): WebsiteRegistration
{
    $plan = SubscriptionPlan::create(array_replace(['name' => 'Tool Starter', 'price' => '99.99', 'plan_type' => 'verification', 'workspace_mode' => 'verification', 'max_clinics' => 1, 'max_users' => 3, 'status' => true, 'website_published' => true, 'verification_model' => 'self_service', 'billing_interval' => 'monthly', 'currency' => 'USD', 'trial_days' => 0, 'quote_required' => false], $planChanges));
    $draft = app(ClientOnboardingService::class)->start(null, 'single_clinic', 'self_service', 'website');
    $draft->update(['status' => 'submitted', 'data' => ['subscription_plan_id' => $plan->id, 'plan_snapshot' => ['id' => $plan->id, 'name' => $plan->name, 'price' => $plan->price, 'currency' => $plan->currency, 'quote_required' => $plan->quote_required, 'billing_interval' => $plan->billing_interval]]]);
    return WebsiteRegistration::create(['onboarding_draft_id' => $draft->id, 'email' => 'checkout@example.com', 'email_verified_at' => now(), 'access_hash' => hash('sha256', str_repeat('a', 64)), 'access_expires_at' => now()->addHour()]);
}

function fakeRegistrationStripe(object $state): void
{
    Http::fake(function ($request) use ($state) {
        $state->calls = ($state->calls ?? 0) + 1;
        if (!str_starts_with($request->url(), 'https://api.stripe.com/v1/checkout/sessions')) throw new RuntimeException('Unexpected test request');
        $attempt = WebsitePaymentAttempt::latest('id')->firstOrFail();
        if ($request->method() === 'POST') {
            if (!empty($state->timeout)) { $state->timeout = false; throw new \Illuminate\Http\Client\ConnectionException('Simulated network interruption'); }
            return Http::response(['id' => 'cs_'.$attempt->public_id, 'url' => 'https://checkout.stripe.com/c/pay/'.$attempt->public_id, 'livemode' => $state->live ?? false]);
        }
        return Http::response(['id' => $attempt->remote_id, 'client_reference_id' => $state->reference ?? $attempt->public_id, 'livemode' => $state->live ?? false,
            'amount_total' => $state->amount ?? $attempt->checkout->amount_minor, 'currency' => 'usd', 'status' => $state->status === 'expired' ? 'expired' : 'complete',
            'payment_status' => in_array($state->status, ['paid', 'refunded'], true) ? 'paid' : 'unpaid',
            'payment_intent' => ['id' => 'pi_'.$attempt->public_id, 'latest_charge' => ['amount_refunded' => $state->status === 'refunded' ? $attempt->checkout->amount_minor : 0]]]);
    });
}

function stripeCheckoutEvent(string $id, string $type = 'checkout.session.completed'): array
{
    $attempt = WebsitePaymentAttempt::firstOrFail();
    $payload = json_encode(['id' => $id, 'object' => 'event', 'type' => $type, 'data' => ['object' => ['id' => $attempt->remote_id, 'client_reference_id' => $attempt->public_id]]]);
    $time = time();
    return [$payload, 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$payload, 'whsec_checkout_test')];
}

it('creates checkout from the saved offer and cannot accept a browser price or paid flag', function () {
    checkoutRegistration();
    fakeRegistrationStripe((object) ['status' => 'pending']);
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true, 'amount' => 1, 'status' => 'paid'])->assertOk()->assertJsonPath('checkout.amount', '99.99')->assertJsonPath('checkout.status', 'pending');
    Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['mode'] === 'payment' && $r['line_items'][0]['price_data']['unit_amount'] === 9999 && !isset($r['line_items'][0]['price_data']['recurring']));
    $this->getJson('/website-api/checkout?return=complete&paid=1')->assertOk()->assertJsonPath('checkout.status', 'pending');
    expect(WebsitePaymentAttempt::count())->toBe(1);
});

it('reuses a saved attempt after a timeout and blocks simultaneous gateway switching', function () {
    checkoutRegistration();
    fakeRegistrationStripe((object) ['status' => 'pending', 'timeout' => true]);
    $data = ['gateway' => 'stripe', 'confirm_initial_payment' => true];
    $this->postJson('/website-api/checkout-start', $data)->assertStatus(503);
    $id = WebsitePaymentAttempt::first()->public_id;
    $this->postJson('/website-api/checkout-start', $data)->assertOk();
    $this->postJson('/website-api/checkout-start', [...$data, 'gateway' => 'paypal'])->assertStatus(409);
    expect(WebsitePaymentAttempt::count())->toBe(1)->and(WebsitePaymentAttempt::first()->public_id)->toBe($id);
    Http::assertSent(fn ($r) => ($r->header('Idempotency-Key')[0] ?? '') === $id);
});

it('only settles matching gateway payments and makes replayed webhooks harmless', function () {
    $registration = checkoutRegistration();
    $state = (object) ['status' => 'pending'];
    fakeRegistrationStripe($state);
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true])->assertOk();
    $state->status = 'paid'; $state->amount = 1;
    $this->postJson('/website-api/checkout-refresh')->assertStatus(503);
    expect(WebsiteCheckout::first()->status)->toBe('pending');
    $state->amount = 9999;
    [$payload, $signature] = stripeCheckoutEvent('evt_once');
    $this->call('POST', '/website-payments/webhook/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
    $this->call('POST', '/website-payments/webhook/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
    expect(WebsiteCheckout::first()->status)->toBe('paid')->and(DB::table('website_payment_events')->count())->toBe(1);
    expect($registration->draft->fresh()->status)->toBe('submitted');
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true])->assertStatus(409);
    expect(fn () => CheckoutService::assertReadyForActivation($registration->draft))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('rejects unsigned webhooks and unauthorized registration access', function () {
    $this->postJson('/website-payments/webhook/stripe', ['type' => 'checkout.session.completed'])->assertStatus(400);
    $this->postJson('/website-payments/webhook/paypal', ['event_type' => 'PAYMENT.CAPTURE.COMPLETED'])->assertStatus(400);
    $this->getJson('/website-api/checkout')->assertUnauthorized();
    expect(WebsiteCheckout::count())->toBe(0);
});

it('keeps quotes, zero-price offers and incomplete registrations out of checkout', function () {
    $registration = checkoutRegistration(['quote_required' => true]);
    $this->getJson('/website-api/checkout')->assertJsonPath('checkout.status', 'quote_required');
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true])->assertUnprocessable();
    $data = $registration->draft->data; $data['plan_snapshot']['quote_required'] = false; $data['plan_snapshot']['price'] = '0.00';
    $registration->draft->update(['data' => $data]);
    $this->getJson('/website-api/checkout')->assertJsonPath('checkout.status', 'not_required');
    $registration->draft->update(['status' => 'draft']);
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true])->assertStatus(409);
    Http::assertNothingSent();
});

it('records refund status and allows a fresh attempt only after confirmed expiry', function () {
    checkoutRegistration();
    $state = (object) ['status' => 'pending']; fakeRegistrationStripe($state);
    $input = ['gateway' => 'stripe', 'confirm_initial_payment' => true];
    $this->postJson('/website-api/checkout-start', $input)->assertOk();
    $state->status = 'expired'; $this->postJson('/website-api/checkout-refresh')->assertOk()->assertJsonPath('checkout.status', 'expired');
    $this->postJson('/website-api/checkout-start', $input)->assertOk();
    expect(WebsitePaymentAttempt::count())->toBe(2);
    $state->status = 'paid'; $this->postJson('/website-api/checkout-refresh')->assertOk()->assertJsonPath('checkout.status', 'paid');
    $state->status = 'refunded'; $this->postJson('/website-api/checkout-refresh')->assertOk()->assertJsonPath('checkout.status', 'refunded');
});

it('captures PayPal approval on the server and saves only a confirmed capture', function () {
    checkoutRegistration();
    $captured = false; $captureCalls = 0;
    Http::fake(function ($request) use (&$captured, &$captureCalls) {
        $url = $request->url();
        if (str_ends_with($url, '/v1/oauth2/token')) return Http::response(['access_token' => 'test-access']);
        if (str_ends_with($url, '/v1/notifications/verify-webhook-signature')) return Http::response(['verification_status' => 'SUCCESS']);
        if ($request->method() === 'POST' && str_ends_with($url, '/v2/checkout/orders')) return Http::response(['id' => 'ORDER-1', 'links' => [['rel' => 'payer-action', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1']]]);
        if (str_ends_with($url, '/ORDER-1/capture')) { $captureCalls++; $captured = true; throw new \Illuminate\Http\Client\ConnectionException('Capture succeeded but its response was lost'); }
        $attempt = WebsitePaymentAttempt::firstOrFail();
        if (str_ends_with($url, '/v2/checkout/orders/ORDER-1')) return Http::response(['id' => 'ORDER-1', 'status' => $captured ? 'COMPLETED' : 'APPROVED', 'purchase_units' => [['custom_id' => $attempt->public_id, 'amount' => ['value' => '99.99', 'currency_code' => 'USD'], 'payments' => ['captures' => $captured ? [['id' => 'CAPTURE-1']] : []]]]]);
        if (str_ends_with($url, '/v2/payments/captures/CAPTURE-1')) return Http::response(['id' => 'CAPTURE-1', 'status' => 'COMPLETED', 'amount' => ['value' => '99.99', 'currency_code' => 'USD']]);
        throw new RuntimeException('Unexpected test request');
    });
    $this->postJson('/website-api/checkout-start', ['gateway' => 'paypal', 'confirm_initial_payment' => true])->assertOk()->assertJsonPath('checkout.status', 'pending');
    $this->withHeaders(['PAYPAL-AUTH-ALGO' => 'SHA256withRSA', 'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert/test', 'PAYPAL-TRANSMISSION-ID' => 'id', 'PAYPAL-TRANSMISSION-SIG' => 'verified-by-fake', 'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String()])
        ->postJson('/website-payments/webhook/paypal', ['id' => 'PAYPAL-EVENT', 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource' => ['id' => 'ORDER-1']])->assertStatus(503);
    expect(WebsitePaymentAttempt::first()->capture_started_at)->not->toBeNull();
    expect(DB::table('website_payment_events')->count())->toBe(0);
    $this->postJson('/website-payments/webhook/paypal', ['id' => 'PAYPAL-EVENT', 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource' => ['id' => 'ORDER-1']])->assertOk();
    $this->postJson('/website-api/checkout-refresh', ['token' => 'ATTACKER-ORDER'])->assertOk()->assertJsonPath('checkout.status', 'paid');
    $this->postJson('/website-api/checkout-refresh')->assertOk();
    expect(WebsitePaymentAttempt::first()->payment_reference)->toBe('CAPTURE-1');
    expect($captureCalls)->toBe(1);
});

it('keeps an uncertain stale attempt for reconciliation and never starts another charge', function () {
    checkoutRegistration(); $state = (object) ['status' => 'pending', 'timeout' => true]; fakeRegistrationStripe($state);
    $input = ['gateway' => 'stripe', 'confirm_initial_payment' => true];
    $this->postJson('/website-api/checkout-start', $input)->assertStatus(503);
    WebsitePaymentAttempt::first()->update(['created_at' => now()->subHours(6)]);
    $this->postJson('/website-api/checkout-start', $input)->assertStatus(503);
    expect(WebsitePaymentAttempt::count())->toBe(1);
    expect($state->calls)->toBe(1);
});

it('requires initial-payment consent and isolates customers and payment environments', function () {
    $registration = checkoutRegistration();
    $state = (object) ['status' => 'pending', 'live' => true]; fakeRegistrationStripe($state);
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe'])->assertUnprocessable();
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true])->assertStatus(503);
    expect(WebsitePaymentAttempt::first()->checkout_url)->toBeNull();
    $this->withHeader('X-Registration-Token', str_repeat('b', 64))->getJson('/website-api/checkout')->assertUnauthorized();
    $registration->update(['access_expires_at' => now()->subMinute()]);
    $this->withHeader('X-Registration-Token', str_repeat('a', 64))->getJson('/website-api/checkout')->assertUnauthorized();
});

it('records a verified asynchronous failure without trusting a failed browser return', function () {
    checkoutRegistration(); fakeRegistrationStripe((object) ['status' => 'pending']);
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true])->assertOk();
    [$payload, $signature] = stripeCheckoutEvent('evt_failed', 'checkout.session.async_payment_failed');
    $this->call('POST', '/website-payments/webhook/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
    expect(WebsiteCheckout::first()->status)->toBe('failed');
});

it('renders app payment history and does not allow registration changes after checkout starts', function () {
    $registration = checkoutRegistration(); fakeRegistrationStripe((object) ['status' => 'pending']);
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true])->assertOk();
    $registration->draft->update(['status' => 'changes_requested']);
    $this->postJson('/website-api/save', ['subscription_plan_id' => 999])->assertStatus(409);
    $staff = \App\Models\User::factory()->create(['status' => true]);
    $staff->assignRole(\Spatie\Permission\Models\Role::findOrCreate('saas_admin', 'web'));
    $staff->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('saas.payments.view', 'web'));
    $this->actingAs($staff);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('saas'));
    \Livewire\Livewire::test(\App\Filament\Saas\Pages\WebsitePayments::class)->assertSuccessful()->assertSee('checkout@example.com')->assertSee('stripe / test');
});

it('allows activation only for a matching confirmed live payment', function () {
    $registration = checkoutRegistration(); $state = (object) ['status' => 'pending', 'live' => true];
    SaasSetting::query()->first()->update(['stripe_environment' => 'live']);
    fakeRegistrationStripe($state);
    $this->postJson('/website-api/checkout-start', ['gateway' => 'stripe', 'confirm_initial_payment' => true])->assertOk();
    expect(fn () => CheckoutService::assertReadyForActivation($registration->draft))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $state->status = 'paid'; $this->postJson('/website-api/checkout-refresh')->assertOk();
    CheckoutService::assertReadyForActivation($registration->draft);
    expect(fn () => CheckoutService::assertReadyForActivation($registration->draft, ['subscription_plan_id' => 999]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(WebsiteCheckout::first()->status)->toBe('paid');
});

it('verifies PayPal callbacks before any payment lookup or capture', function () {
    $headers = ['PAYPAL-AUTH-ALGO' => 'SHA256withRSA', 'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert/test', 'PAYPAL-TRANSMISSION-ID' => 'id', 'PAYPAL-TRANSMISSION-SIG' => 'invalid', 'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String()];
    Http::fake(['*/v1/oauth2/token' => Http::response(['access_token' => 'test']), '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'FAILURE'])]);
    $this->withHeaders($headers)->postJson('/website-payments/webhook/paypal', ['id' => 'FAKE', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED'])->assertStatus(400);
    expect(DB::table('website_payment_events')->count())->toBe(0);
});
