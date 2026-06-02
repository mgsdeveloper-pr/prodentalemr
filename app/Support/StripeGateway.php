<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SaasSetting;
use Illuminate\Support\Facades\URL;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway
{
    public static function webhookUrl(): string
    {
        return route('stripe.webhook');
    }

    public static function paymentPageUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'billing.invoices.payment.page',
            now()->addDays(30),
            ['invoice' => $invoice],
        );
    }

    public static function canCreatePaymentLinks(?SaasSetting $settings = null): bool
    {
        $settings ??= SaasSetting::current();

        return (bool) $settings->stripe_enabled
            && filled($settings->stripe_secret_key)
            && filled($settings->stripe_publishable_key);
    }

    public static function configurationError(?SaasSetting $settings = null): ?string
    {
        $settings ??= SaasSetting::current();

        if (! $settings->stripe_enabled) {
            return 'Enable Stripe in Payment Credentials first.';
        }

        if (blank($settings->stripe_publishable_key) || blank($settings->stripe_secret_key)) {
            return 'Add the Stripe publishable key and secret key in Payment Credentials first.';
        }

        return null;
    }

    public static function createOrRefreshCheckoutSession(Invoice $invoice): Invoice
    {
        $invoice->loadMissing(['organization', 'items']);

        $session = static::client()->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => URL::temporarySignedRoute(
                'billing.invoices.payment.success',
                now()->addDays(30),
                ['invoice' => $invoice],
            ),
            'cancel_url' => URL::temporarySignedRoute(
                'billing.invoices.payment.cancel',
                now()->addDays(30),
                ['invoice' => $invoice],
            ),
            'client_reference_id' => (string) $invoice->getKey(),
            'customer_email' => $invoice->organization?->email,
            'metadata' => [
                'invoice_id' => (string) $invoice->getKey(),
                'invoice_number' => $invoice->invoice_number,
                'organization_id' => (string) $invoice->organization_id,
            ],
            'line_items' => static::lineItemsFor($invoice),
        ]);

        $invoice->forceFill([
            'stripe_checkout_session_id' => $session->id,
            'stripe_checkout_url' => $session->url,
            'stripe_checkout_expires_at' => filled($session->expires_at) ? now()->setTimestamp($session->expires_at) : null,
        ])->save();

        return $invoice->fresh();
    }

    public static function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        $settings = SaasSetting::current();

        return Webhook::constructEvent($payload, $signature, (string) $settings->stripe_webhook_secret);
    }

    public static function handleWebhookEvent(\Stripe\Event $event): void
    {
        if ($event->type !== 'checkout.session.completed') {
            return;
        }

        /** @var \Stripe\Checkout\Session $session */
        $session = $event->data->object;
        $invoiceId = data_get($session, 'metadata.invoice_id') ?? $session->client_reference_id;

        if (blank($invoiceId)) {
            return;
        }

        /** @var Invoice|null $invoice */
        $invoice = Invoice::query()->find($invoiceId);

        if (! $invoice) {
            return;
        }

        $referenceNumber = $session->payment_intent ?: $session->id;

        if (Payment::query()->where('reference_number', $referenceNumber)->exists()) {
            $invoice->forceFill([
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent,
                'stripe_checkout_url' => $session->url ?: $invoice->stripe_checkout_url,
                'stripe_checkout_expires_at' => filled($session->expires_at) ? now()->setTimestamp($session->expires_at) : $invoice->stripe_checkout_expires_at,
            ])->save();

            return;
        }

        Payment::create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'payment_date' => now()->toDateString(),
            'amount' => ((int) ($session->amount_total ?? 0)) / 100,
            'payment_method' => 'stripe',
            'reference_number' => $referenceNumber,
            'notes' => 'Recorded automatically from Stripe Checkout.',
        ]);

        $invoice->forceFill([
            'stripe_checkout_session_id' => $session->id,
            'stripe_payment_intent_id' => $session->payment_intent,
            'stripe_checkout_url' => $session->url ?: $invoice->stripe_checkout_url,
            'stripe_checkout_expires_at' => filled($session->expires_at) ? now()->setTimestamp($session->expires_at) : $invoice->stripe_checkout_expires_at,
        ])->save();

        $invoice->refreshPaymentSummary();
    }

    protected static function client(): StripeClient
    {
        $settings = SaasSetting::current();

        return new StripeClient((string) $settings->stripe_secret_key);
    }

    public static function testConnection(array $state): array
    {
        $secretKey = $state['stripe_secret_key'] ?? SaasSetting::current()->stripe_secret_key;

        if (blank($secretKey) || blank($state['stripe_publishable_key'] ?? SaasSetting::current()->stripe_publishable_key)) {
            throw new \RuntimeException('Stripe publishable key and secret key are required before testing the connection.');
        }

        $client = new StripeClient((string) $secretKey);
        $balance = $client->balance->retrieve();

        return [
            'environment' => $state['stripe_environment'] ?? SaasSetting::current()->stripe_environment ?? 'test',
            'livemode' => (bool) ($balance->livemode ?? false),
        ];
    }

    protected static function lineItemsFor(Invoice $invoice): array
    {
        return [[
            'quantity' => 1,
            'price_data' => [
                'currency' => strtolower(SaasSetting::current()->default_currency ?: 'USD'),
                'product_data' => [
                    'name' => 'Invoice ' . $invoice->invoice_number,
                    'description' => 'Outstanding service invoice balance',
                ],
                'unit_amount' => (int) round(((float) $invoice->balance_due) * 100),
            ],
        ]];
    }
}
