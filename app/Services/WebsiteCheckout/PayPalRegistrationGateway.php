<?php

namespace App\Services\WebsiteCheckout;

use App\Models\SaasSetting;
use App\Models\WebsitePaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPalRegistrationGateway implements RegistrationPaymentGateway
{
    private ?string $token = null;
    public function label(): string { return 'PayPal'; }
    public function environment(): string { return SaasSetting::current()->paypal_environment === 'live' ? 'live' : 'test'; }
    public function checkoutHosts(): array { return ['www.paypal.com', 'www.sandbox.paypal.com']; }
    public function supportsCurrency(string $currency): bool { return in_array($currency, ['USD', 'EUR', 'GBP', 'CAD', 'AUD'], true); }
    public function available(): bool
    {
        $settings = SaasSetting::current();
        return $settings->paypal_enabled && filled($settings->paypal_client_id) && filled($settings->paypal_client_secret) && filled(config('website_checkout.paypal_webhook_id'));
    }
    private function base(): string { return $this->environment() === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com'; }
    private function http()
    {
        if (!$this->token) {
            $settings = SaasSetting::current();
            $this->token = Http::asForm()->withBasicAuth((string) $settings->paypal_client_id, (string) $settings->paypal_client_secret)
                ->connectTimeout(5)->timeout(15)->post($this->base().'/v1/oauth2/token', ['grant_type' => 'client_credentials'])->throw()->json('access_token');
            if (!$this->token) throw new \RuntimeException('PayPal authorization unavailable.');
        }
        return Http::baseUrl($this->base())->withToken($this->token)->acceptJson()->asJson()->connectTimeout(5)->timeout(15);
    }
    public function create(WebsitePaymentAttempt $attempt): array
    {
        $order = $attempt->checkout;
        $result = $this->http()->withHeaders(['PayPal-Request-Id' => $attempt->public_id])->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [['reference_id' => $attempt->public_id, 'custom_id' => $attempt->public_id,
                'description' => 'Denentiq initial payment. Renewals handled separately.',
                'amount' => ['currency_code' => $order->currency, 'value' => CheckoutMoney::decimal($order->amount_minor)]]],
            'payment_source' => ['paypal' => ['experience_context' => [
                'brand_name' => 'Denentiq', 'shipping_preference' => 'NO_SHIPPING', 'user_action' => 'PAY_NOW',
                'return_url' => GatewayRegistry::returnUrl('complete'), 'cancel_url' => GatewayRegistry::returnUrl('cancel'),
            ]]],
        ])->throw()->json();
        $link = collect($result['links'] ?? [])->firstWhere('rel', 'payer-action') ?? collect($result['links'] ?? [])->firstWhere('rel', 'approve');
        if (empty($result['id']) || empty($link['href'])) throw new \RuntimeException('Missing PayPal checkout reference.');
        return ['remote_id' => $result['id'], 'checkout_url' => $link['href']];
    }
    public function inspect(WebsitePaymentAttempt $attempt): array
    {
        $order = $this->http()->get('/v2/checkout/orders/'.rawurlencode($attempt->remote_id))->throw()->json();
        $unit = $order['purchase_units'][0] ?? [];
        if (($order['id'] ?? null) !== $attempt->remote_id || count($order['purchase_units'] ?? []) !== 1
            || ($unit['custom_id'] ?? null) !== $attempt->public_id
            || ($unit['amount']['currency_code'] ?? '') !== $attempt->checkout->currency
            || CheckoutMoney::minor((string) ($unit['amount']['value'] ?? '')) !== $attempt->checkout->amount_minor) {
            throw new \RuntimeException('PayPal order did not match checkout.');
        }
        if (($order['status'] ?? '') === 'APPROVED' && empty($unit['payments']['captures'])) {
            // Never retry a potentially successful capture beyond PayPal's default
            // idempotency retention. Keep it pending for manual reconciliation.
            if ($attempt->capture_started_at?->lt(now()->subHours(5))) throw new \RuntimeException('Capture requires reconciliation.');
            if (!$attempt->capture_started_at) $attempt->update(['capture_started_at' => now()]);
            $this->http()->withHeaders(['PayPal-Request-Id' => 'c'.$attempt->public_id])->withBody('{}', 'application/json')
                ->post('/v2/checkout/orders/'.rawurlencode($attempt->remote_id).'/capture')->throw();
            $order = $this->http()->get('/v2/checkout/orders/'.rawurlencode($attempt->remote_id))->throw()->json();
            $unit = $order['purchase_units'][0] ?? [];
        }
        $status = in_array($order['status'] ?? '', ['VOIDED'], true) ? 'expired' : 'pending';
        $capture = $unit['payments']['captures'][0] ?? null;
        if ($capture) {
            if (count($unit['payments']['captures']) !== 1) throw new \RuntimeException('Unexpected PayPal split capture.');
            $capture = $this->http()->get('/v2/payments/captures/'.rawurlencode($capture['id']))->throw()->json();
            $status = match ($capture['status'] ?? '') {
                'COMPLETED' => 'paid', 'DECLINED', 'FAILED', 'DENIED' => 'failed', 'REVERSED' => 'reversed', 'REFUNDED' => 'refunded', 'PARTIALLY_REFUNDED' => 'partially_refunded', default => 'pending',
            };
            if (($capture['amount']['currency_code'] ?? '') !== $attempt->checkout->currency || CheckoutMoney::minor((string) ($capture['amount']['value'] ?? '')) !== $attempt->checkout->amount_minor) throw new \RuntimeException('PayPal capture amount did not match checkout.');
        }
        return ['status' => $status, 'remote_id' => $order['id'] ?? '', 'attempt_reference' => $unit['custom_id'] ?? '',
            'amount_minor' => CheckoutMoney::minor((string) ($unit['amount']['value'] ?? '')), 'currency' => $unit['amount']['currency_code'] ?? '',
            'environment' => $this->environment(), 'payment_reference' => $capture['id'] ?? null];
    }
    public function webhook(Request $request): ?array
    {
        abort_unless(filled(config('website_checkout.paypal_webhook_id')), 503);
        $event = $request->json()->all();
        foreach (['PAYPAL-AUTH-ALGO', 'PAYPAL-CERT-URL', 'PAYPAL-TRANSMISSION-ID', 'PAYPAL-TRANSMISSION-SIG', 'PAYPAL-TRANSMISSION-TIME'] as $header) abort_unless($request->header($header), 400, 'Missing PayPal webhook signature.');
        $verified = $this->http()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'), 'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'), 'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'), 'webhook_id' => config('website_checkout.paypal_webhook_id'), 'webhook_event' => $event,
        ])->throw()->json('verification_status');
        abort_unless($verified === 'SUCCESS', 400, 'Invalid PayPal webhook signature.');
        $type = $event['event_type'] ?? '';
        if (!in_array($type, ['CHECKOUT.ORDER.APPROVED', 'CHECKOUT.ORDER.COMPLETED', 'PAYMENT.CAPTURE.COMPLETED', 'PAYMENT.CAPTURE.PENDING', 'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.REVERSED'], true)) return null;
        return ['event_id' => $event['id'],
            'remote_id' => str_starts_with($type, 'CHECKOUT.ORDER.') ? data_get($event, 'resource.id') : data_get($event, 'resource.supplementary_data.related_ids.order_id'),
            'payment_reference' => data_get($event, 'resource.supplementary_data.related_ids.capture_id')
                ?? (str_starts_with($type, 'PAYMENT.CAPTURE.') && $type !== 'PAYMENT.CAPTURE.REFUNDED' ? data_get($event, 'resource.id') : null),
            'attempt_reference' => data_get($event, 'resource.custom_id') ?? data_get($event, 'resource.purchase_units.0.custom_id'),
            'terminal_status' => $type === 'PAYMENT.CAPTURE.REVERSED' ? 'reversed' : null,
        ];
    }
}
