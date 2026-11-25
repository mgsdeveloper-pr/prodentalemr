<?php

namespace App\Services\WebsiteCheckout;

use App\Models\SaasSetting;
use App\Models\WebsitePaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Stripe\Webhook;

class StripeRegistrationGateway implements RegistrationPaymentGateway
{
    public function label(): string { return 'Stripe'; }
    public function environment(): string { return SaasSetting::current()->stripe_environment === 'live' ? 'live' : 'test'; }
    public function checkoutHosts(): array { return ['checkout.stripe.com']; }
    public function supportsCurrency(string $currency): bool { return in_array($currency, CheckoutMoney::CURRENCIES, true); }
    public function available(): bool
    {
        $settings = SaasSetting::current();
        return $settings->stripe_enabled && filled($settings->stripe_secret_key) && filled(config('website_checkout.stripe_webhook_secret'));
    }
    private function http()
    {
        return Http::baseUrl('https://api.stripe.com/v1')->withToken((string) SaasSetting::current()->stripe_secret_key)->asForm()->connectTimeout(5)->timeout(15);
    }
    public function create(WebsitePaymentAttempt $attempt): array
    {
        $order = $attempt->checkout;
        $session = $this->http()->withHeaders(['Idempotency-Key' => $attempt->public_id])->post('/checkout/sessions', [
            'mode' => 'payment', 'success_url' => GatewayRegistry::returnUrl('complete'), 'cancel_url' => GatewayRegistry::returnUrl('cancel'),
            'client_reference_id' => $attempt->public_id, 'metadata' => ['website_attempt' => $attempt->public_id],
            'payment_intent_data' => ['metadata' => ['website_attempt' => $attempt->public_id]],
            'customer_email' => $order->registration->email,
            'line_items' => [['quantity' => 1, 'price_data' => ['currency' => strtolower($order->currency), 'unit_amount' => $order->amount_minor,
                'product_data' => ['name' => $order->plan_snapshot['name'], 'description' => 'Initial payment only. Renewals handled separately.']]]],
        ])->throw()->json();
        if (empty($session['id']) || empty($session['url'])) throw new \RuntimeException('Missing Stripe checkout reference.');
        if (!array_key_exists('livemode', $session) || (bool) $session['livemode'] !== ($attempt->environment === 'live')) throw new \RuntimeException('Stripe checkout environment does not match configuration.');
        return ['remote_id' => $session['id'], 'checkout_url' => $session['url']];
    }
    public function inspect(WebsitePaymentAttempt $attempt): array
    {
        $session = $this->http()->get('/checkout/sessions/'.rawurlencode($attempt->remote_id), ['expand' => ['payment_intent.latest_charge']])->throw()->json();
        $intent = $session['payment_intent'] ?? null;
        $charge = is_array($intent) ? ($intent['latest_charge'] ?? null) : null;
        $status = ($session['payment_status'] ?? '') === 'paid' ? 'paid' : (($session['status'] ?? '') === 'expired' ? 'expired' : 'pending');
        if (is_array($charge) && $status === 'paid') {
            if (!empty($charge['disputed'])) $status = 'disputed';
            elseif (($charge['amount_refunded'] ?? 0) >= ($session['amount_total'] ?? PHP_INT_MAX)) $status = 'refunded';
            elseif (($charge['amount_refunded'] ?? 0) > 0) $status = 'partially_refunded';
        }
        return ['status' => $status, 'remote_id' => $session['id'] ?? '', 'attempt_reference' => $session['client_reference_id'] ?? '',
            'amount_minor' => $session['amount_total'] ?? -1, 'currency' => strtoupper($session['currency'] ?? ''),
            'environment' => !empty($session['livemode']) ? 'live' : 'test', 'payment_reference' => is_array($intent) ? ($intent['id'] ?? null) : $intent];
    }
    public function webhook(Request $request): ?array
    {
        abort_unless(filled(config('website_checkout.stripe_webhook_secret')), 503);
        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), (string) config('website_checkout.stripe_webhook_secret'))->toArray();
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            abort(400, 'Invalid Stripe webhook signature.');
        }
        $type = $event['type'] ?? '';
        $object = $event['data']['object'] ?? [];
        if (in_array($type, ['checkout.session.completed', 'checkout.session.expired', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed'], true)) {
            return ['event_id' => $event['id'], 'remote_id' => $object['id'], 'attempt_reference' => $object['client_reference_id'] ?? null,
                'terminal_status' => $type === 'checkout.session.async_payment_failed' ? 'failed' : null];
        }
        if (in_array($type, ['charge.refunded', 'charge.dispute.created', 'charge.dispute.closed'], true)) {
            return ['event_id' => $event['id'], 'payment_reference' => $object['payment_intent'] ?? null];
        }
        return null;
    }
}
