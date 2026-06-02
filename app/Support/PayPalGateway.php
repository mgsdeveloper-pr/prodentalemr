<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SaasSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class PayPalGateway
{
    public static function webhookUrl(): string
    {
        return route('paypal.webhook');
    }

    public static function paymentPageUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'billing.invoices.paypal.page',
            now()->addDays(30),
            ['invoice' => $invoice],
        );
    }

    public static function canCreatePaymentLinks(?SaasSetting $settings = null): bool
    {
        $settings ??= SaasSetting::current();

        return (bool) $settings->paypal_enabled
            && filled($settings->paypal_client_id)
            && filled($settings->paypal_client_secret);
    }

    public static function configurationError(?SaasSetting $settings = null): ?string
    {
        $settings ??= SaasSetting::current();

        if (! $settings->paypal_enabled) {
            return 'Enable PayPal in Payment Credentials first.';
        }

        if (blank($settings->paypal_client_id) || blank($settings->paypal_client_secret)) {
            return 'Add the PayPal client ID and client secret in Payment Credentials first.';
        }

        return null;
    }

    public static function createOrRefreshOrder(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('organization');

        $response = static::paypalRequest()
            ->post('/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $invoice->invoice_number,
                    'custom_id' => (string) $invoice->getKey(),
                    'invoice_id' => $invoice->invoice_number,
                    'description' => 'Outstanding service invoice balance',
                    'amount' => [
                        'currency_code' => strtoupper(SaasSetting::current()->default_currency ?: 'USD'),
                        'value' => number_format((float) $invoice->balance_due, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => SaasSetting::current()->brandName(),
                    'landing_page' => 'LOGIN',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('billing.invoices.paypal.return', ['invoice' => $invoice]),
                    'cancel_url' => route('billing.invoices.paypal.cancel', ['invoice' => $invoice]),
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(static::extractError($response));
        }

        $payload = $response->json();
        $approvalUrl = collect($payload['links'] ?? [])
            ->firstWhere('rel', 'approve')['href']
            ?? collect($payload['links'] ?? [])->firstWhere('rel', 'payer-action')['href']
            ?? null;

        if (blank($approvalUrl) || blank($payload['id'] ?? null)) {
            throw new RuntimeException('PayPal did not return an approval URL for this order.');
        }

        $invoice->forceFill([
            'paypal_order_id' => $payload['id'],
            'paypal_approval_url' => $approvalUrl,
            'paypal_order_status' => $payload['status'] ?? null,
        ])->save();

        return $invoice->fresh();
    }

    public static function captureApprovedOrder(Invoice $invoice, string $orderId): Invoice
    {
        $response = static::paypalRequest()
            ->post("/v2/checkout/orders/{$orderId}/capture");

        if ($response->failed()) {
            throw new RuntimeException(static::extractError($response));
        }

        $payload = $response->json();

        if (($payload['id'] ?? null) !== $orderId) {
            throw new RuntimeException('PayPal returned an unexpected order reference.');
        }

        $purchaseUnit = collect($payload['purchase_units'] ?? [])->first();
        $capture = collect(data_get($purchaseUnit, 'payments.captures', []))->first();

        if (! $purchaseUnit || ! $capture) {
            throw new RuntimeException('PayPal capture details were not returned.');
        }

        $invoiceId = $purchaseUnit['custom_id'] ?? null;

        if ((string) $invoice->getKey() !== (string) $invoiceId) {
            throw new RuntimeException('PayPal capture did not match the expected invoice.');
        }

        static::recordCapture($invoice, $payload, $capture);

        return $invoice->fresh();
    }

    public static function verifyWebhook(array $headers, array $event): bool
    {
        $settings = SaasSetting::current();

        if (blank($settings->paypal_webhook_id)) {
            return false;
        }

        $response = static::paypalRequest()
            ->post('/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? null,
                'cert_url' => $headers['PAYPAL-CERT-URL'] ?? null,
                'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? null,
                'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? null,
                'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? null,
                'webhook_id' => $settings->paypal_webhook_id,
                'webhook_event' => $event,
            ]);

        return $response->successful() && ($response->json('verification_status') === 'SUCCESS');
    }

    public static function handleWebhookEvent(array $event): void
    {
        if (($event['event_type'] ?? null) !== 'PAYMENT.CAPTURE.COMPLETED') {
            return;
        }

        $orderId = data_get($event, 'resource.supplementary_data.related_ids.order_id');
        $captureId = data_get($event, 'resource.id');

        if (blank($orderId) || blank($captureId)) {
            return;
        }

        $invoice = Invoice::query()->where('paypal_order_id', $orderId)->first();

        if (! $invoice) {
            return;
        }

        if (Payment::query()->where('reference_number', $captureId)->exists()) {
            $invoice->forceFill([
                'paypal_order_id' => $orderId,
                'paypal_capture_id' => $captureId,
                'paypal_order_status' => data_get($event, 'resource.status'),
            ])->save();

            return;
        }

        Payment::create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'payment_date' => now()->toDateString(),
            'amount' => (float) data_get($event, 'resource.amount.value', 0),
            'payment_method' => 'paypal',
            'reference_number' => $captureId,
            'notes' => 'Recorded automatically from PayPal checkout.',
        ]);

        $invoice->forceFill([
            'paypal_order_id' => $orderId,
            'paypal_capture_id' => $captureId,
            'paypal_order_status' => data_get($event, 'resource.status'),
        ])->save();

        $invoice->refreshPaymentSummary();
    }

    protected static function recordCapture(Invoice $invoice, array $payload, array $capture): void
    {
        $captureId = $capture['id'] ?? $payload['id'] ?? null;

        if (blank($captureId)) {
            throw new RuntimeException('PayPal capture reference is missing.');
        }

        if (! Payment::query()->where('reference_number', $captureId)->exists()) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'payment_date' => now()->toDateString(),
                'amount' => (float) ($capture['amount']['value'] ?? 0),
                'payment_method' => 'paypal',
                'reference_number' => $captureId,
                'notes' => 'Recorded automatically from PayPal checkout.',
            ]);
        }

        $invoice->forceFill([
            'paypal_order_id' => $payload['id'] ?? $invoice->paypal_order_id,
            'paypal_capture_id' => $captureId,
            'paypal_order_status' => $payload['status'] ?? null,
        ])->save();

        $invoice->refreshPaymentSummary();
    }

    protected static function paypalRequest()
    {
        return Http::baseUrl(static::baseUrl())
            ->withToken(static::accessToken())
            ->acceptJson()
            ->asJson();
    }

    protected static function accessToken(): string
    {
        $settings = SaasSetting::current();

        return static::accessTokenFromState([
            'paypal_client_id' => $settings->paypal_client_id,
            'paypal_client_secret' => $settings->paypal_client_secret,
            'paypal_environment' => $settings->paypal_environment,
        ]);
    }

    public static function testConnection(array $state): array
    {
        $token = static::accessTokenFromState($state);

        return [
            'environment' => $state['paypal_environment'] ?? 'sandbox',
            'token_prefix' => substr($token, 0, 12),
        ];
    }

    protected static function accessTokenFromState(array $state): string
    {
        $clientId = $state['paypal_client_id'] ?? null;
        $clientSecret = $state['paypal_client_secret'] ?? null;
        $environment = $state['paypal_environment'] ?? 'sandbox';

        if (blank($clientId) || blank($clientSecret)) {
            throw new RuntimeException('PayPal client ID and client secret are required before testing the connection.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->withBasicAuth((string) $clientId, (string) $clientSecret)
            ->post(static::baseUrlFor($environment) . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            throw new RuntimeException(static::extractError($response));
        }

        $token = $response->json('access_token');

        if (blank($token)) {
            throw new RuntimeException('PayPal did not return an access token.');
        }

        return $token;
    }

    protected static function baseUrl(): string
    {
        return static::baseUrlFor(SaasSetting::current()->paypal_environment);
    }

    protected static function baseUrlFor(?string $environment): string
    {
        return $environment === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    protected static function extractError(Response $response): string
    {
        return $response->json('message')
            ?? $response->json('error_description')
            ?? $response->json('details.0.description')
            ?? 'PayPal request failed.';
    }
}
