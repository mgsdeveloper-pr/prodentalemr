<?php

namespace App\Services\WebsiteCheckout;

use App\Models\OnboardingDraft;
use App\Models\SubscriptionPlan;
use App\Models\WebsiteCheckout;
use App\Models\WebsitePaymentAttempt;
use App\Models\WebsiteRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(private GatewayRegistry $gateways) {}

    public function summary(WebsiteRegistration $registration): array
    {
        $order = WebsiteCheckout::where('website_registration_id', $registration->id)->first();
        $snapshot = $order?->plan_snapshot ?? data_get($registration->draft->data, 'plan_snapshot');
        $status = $order?->status ?? 'not_started';
        if (!$snapshot) $status = 'registration_incomplete';
        elseif (!$order && (($snapshot['quote_required'] ?? false) || ($snapshot['billing_interval'] ?? '') === 'custom')) $status = 'quote_required';
        elseif (!$order && CheckoutMoney::minor((string) ($snapshot['price'] ?? '0')) === 0) $status = 'not_required';
        $attempts = $order?->attempts()->orderByDesc('id')->get() ?? collect();
        $active = $attempts->first(fn ($a) => in_array($a->status, ['creating', 'pending'], true));
        $options = $this->gateways->available($order?->currency ?? ($snapshot['currency'] ?? null));
        return [
            'reference' => $order?->public_id, 'status' => $status, 'plan' => $snapshot,
            'amount' => $order ? CheckoutMoney::decimal($order->amount_minor) : ($snapshot['price'] ?? null), 'currency' => $order?->currency ?? ($snapshot['currency'] ?? null),
            'paid_at' => $order?->paid_at?->toIso8601String(), 'registration_status' => $registration->draft->status,
            'can_start' => config('website_checkout.enabled') && in_array($registration->draft->status, ['submitted', 'under_review'], true) && in_array($status, ['not_started', 'pending', 'failed', 'expired'], true),
            'gateways' => $options, 'active_gateway' => $active?->gateway,
            'checkout_url' => $active?->status === 'pending' ? $this->gateways->safeUrl($active->gateway, $active->checkout_url) : null,
            'needs_attention' => (bool) $active?->last_error,
            'attempts' => $attempts->map(fn ($a) => ['reference' => $a->public_id, 'gateway' => $a->gateway, 'environment' => $a->environment, 'status' => $a->status, 'payment_reference' => $a->payment_reference, 'created_at' => $a->created_at->toIso8601String(), 'checked_at' => $a->last_checked_at?->toIso8601String()])->all(),
        ];
    }

    public function start(WebsiteRegistration $registration, string $gateway): array
    {
        abort_unless(config('website_checkout.enabled'), 503, 'Online checkout is not enabled.');
        $driver = $this->gateways->get($gateway);
        abort_unless($driver->available(), 422, 'This payment gateway is not available.');
        $attempt = DB::transaction(function () use ($registration, $gateway, $driver) {
            WebsiteRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $draft = OnboardingDraft::whereKey($registration->onboarding_draft_id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($draft->status, ['submitted', 'under_review'], true), 409, 'Submit your registration before checkout.');
            $order = WebsiteCheckout::where('website_registration_id', $registration->id)->lockForUpdate()->first();
            if (!$order) {
                $plan = $draft->data['plan_snapshot'] ?? null;
                abort_unless($plan && !($plan['quote_required'] ?? true) && in_array($plan['billing_interval'] ?? '', ['monthly', 'yearly', 'one_time'], true), 422, 'This plan requires a custom quote.');
                abort_unless(SubscriptionPlan::websiteAvailable()->whereKey($plan['id'])->exists(), 409, 'This plan is no longer available. Contact the team.');
                $amount = CheckoutMoney::minor((string) $plan['price']);
                abort_unless($amount > 0 && in_array($plan['currency'], CheckoutMoney::CURRENCIES, true), 422, 'This plan cannot be paid online.');
                $order = WebsiteCheckout::create(['public_id' => (string) Str::uuid(), 'website_registration_id' => $registration->id, 'amount_minor' => $amount, 'currency' => $plan['currency'], 'plan_snapshot' => $plan, 'initial_payment_confirmed_at' => now(), 'status' => 'unpaid']);
            }
            abort_unless($driver->supportsCurrency($order->currency), 422, 'Choose another gateway for this currency.');
            abort_unless(in_array($order->status, ['unpaid', 'pending', 'failed', 'expired'], true), 409, 'A payment is already recorded. Contact the team for changes.');
            $active = $order->attempts()->whereIn('status', ['creating', 'pending'])->latest('id')->first();
            if ($active) {
                abort_unless($active->gateway === $gateway && $active->environment === $driver->environment(), 409, 'Continue the existing payment attempt before selecting another gateway.');
                return $active;
            }
            $order->update(['status' => 'pending']);
            return $order->attempts()->create(['public_id' => (string) Str::uuid(), 'gateway' => $gateway, 'environment' => $driver->environment(), 'status' => 'creating']);
        });
        $this->synchronize($attempt);
        return $this->summary($registration->fresh());
    }

    public function refresh(WebsiteRegistration $registration): array
    {
        $order = WebsiteCheckout::where('website_registration_id', $registration->id)->first();
        $attempt = $order?->attempts()->latest('id')->first();
        if ($attempt) $this->synchronize($attempt);
        return $this->summary($registration->fresh());
    }

    /** Persist an attempt before any network request; keep the same idempotency key
     * after timeouts, and serialize gateway calls on the checkout row. */
    public function synchronize(WebsitePaymentAttempt $attempt, ?array $event = null): void
    {
        $error = DB::transaction(function () use ($attempt, $event) {
            $order = WebsiteCheckout::whereKey($attempt->website_checkout_id)->lockForUpdate()->firstOrFail();
            $attempt = WebsitePaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $attempt->setRelation('checkout', $order);
            if ($event && DB::table('website_payment_events')->where('gateway', $attempt->gateway)->where('event_id', $event['event_id'])->exists()) return false;
            try {
                $driver = $this->gateways->get($attempt->gateway);
                if ($driver->environment() !== $attempt->environment) throw new \RuntimeException('Gateway environment changed.');
                if (!$attempt->remote_id && !empty($event['remote_id'])) $attempt->update(['remote_id' => $event['remote_id']]);
                if (!$attempt->remote_id) {
                    // PayPal keys expire after six hours by default; Stripe after
                    // at least 24. Use a conservative common retry window.
                    if ($attempt->created_at->lt(now()->subHours(5))) throw new \RuntimeException('Creation requires reconciliation.');
                    $created = $driver->create($attempt);
                    $url = $this->gateways->safeUrl($attempt->gateway, $created['checkout_url'] ?? null);
                    if (!$url || empty($created['remote_id'])) throw new \RuntimeException('Gateway checkout is incomplete.');
                    $attempt->update(['remote_id' => $created['remote_id'], 'checkout_url' => $url, 'status' => 'pending', 'last_error' => null]);
                } else {
                    $result = $driver->inspect($attempt);
                    // These hints come only from a signature-verified webhook.
                    // The normal reference/amount/environment checks still apply.
                    if (($event['terminal_status'] ?? null) === 'failed' && $result['status'] === 'pending') $result['status'] = 'failed';
                    if (($event['terminal_status'] ?? null) === 'reversed' && in_array($result['status'], ['paid', 'reversed'], true)) $result['status'] = 'reversed';
                    $this->apply($order, $attempt, $result);
                }
                $attempt->update(['last_checked_at' => now(), 'last_error' => null]);
                if ($event) DB::table('website_payment_events')->insert(['gateway' => $attempt->gateway, 'event_id' => $event['event_id'], 'website_payment_attempt_id' => $attempt->id, 'processed_at' => now()]);
                return false;
            } catch (\Throwable $e) {
                // Do not log gateway response bodies, credentials or payer data.
                Log::warning('Website payment requires retry or reconciliation.', ['attempt' => $attempt->public_id, 'exception_type' => get_class($e)]);
                $attempt->update(['last_error' => 'gateway_verification_unavailable', 'last_checked_at' => now()]);
                return true;
            }
        });
        abort_if($error, 503, 'Payment could not be verified. Your attempt is saved; refresh its status before trying again.');
    }

    private function apply(WebsiteCheckout $order, WebsitePaymentAttempt $attempt, array $result): void
    {
        $states = ['pending', 'paid', 'failed', 'expired', 'refunded', 'partially_refunded', 'disputed', 'reversed'];
        if (($result['remote_id'] ?? '') !== $attempt->remote_id || ($result['attempt_reference'] ?? '') !== $attempt->public_id
            || ($result['environment'] ?? '') !== $attempt->environment || ($result['currency'] ?? '') !== $order->currency
            || ($result['amount_minor'] ?? -1) !== $order->amount_minor || !in_array($result['status'] ?? '', $states, true)) {
            throw new \RuntimeException('Gateway payment did not match checkout.');
        }
        $status = $result['status'];
        $settled = ['paid', 'refunded', 'partially_refunded', 'disputed', 'reversed'];
        if (in_array($status, $settled, true) && empty($result['payment_reference'])) throw new \RuntimeException('Missing payment reference.');
        // An old pending/failed delivery must never undo a recorded settlement.
        if (in_array($attempt->status, $settled, true) && !in_array($status, $settled, true)) return;
        if (in_array($attempt->status, ['refunded', 'partially_refunded', 'reversed'], true) && $status === 'paid') return;
        if (in_array($attempt->status, ['failed', 'expired'], true) && $status === 'pending') return;
        $attempt->update(['status' => $status, 'payment_reference' => $result['payment_reference'] ?? $attempt->payment_reference]);
        if (in_array($status, $settled, true)) {
            $duplicate = $order->attempts()->where('id', '!=', $attempt->id)->whereIn('status', $settled)->exists();
            $order->update(['status' => $duplicate ? 'review_required' : $status, 'paid_at' => $order->paid_at ?? now()]);
        } elseif (!$order->paid_at && $order->attempts()->latest('id')->value('id') === $attempt->id) {
            $order->update(['status' => $status]);
        }
    }

    public function webhook(string $gateway, \Illuminate\Http\Request $request): void
    {
        $event = $this->gateways->get($gateway)->webhook($request);
        if (!$event || empty($event['event_id'])) return;
        $query = WebsitePaymentAttempt::where('gateway', $gateway)->where('environment', $this->gateways->get($gateway)->environment());
        if (!empty($event['remote_id'])) $query->where('remote_id', $event['remote_id']);
        elseif (!empty($event['payment_reference'])) $query->where('payment_reference', $event['payment_reference']);
        elseif (!empty($event['attempt_reference'])) $query->where('public_id', $event['attempt_reference']);
        else return;
        $attempt = $query->first();
        // A signed event may arrive while create is returning. Metadata identifies
        // that persisted attempt; inspect still checks the full provider reference.
        if (!$attempt && !empty($event['attempt_reference'])) $attempt = WebsitePaymentAttempt::where('gateway', $gateway)->where('public_id', $event['attempt_reference'])->first();
        if ($attempt) $this->synchronize($attempt, $event);
    }

    public static function assertReadyForActivation(OnboardingDraft $draft, ?array $state = null): void
    {
        if ($draft->entry_point !== 'website') return;
        $registration = WebsiteRegistration::where('onboarding_draft_id', $draft->id)->first();
        $order = $registration ? WebsiteCheckout::where('website_registration_id', $registration->id)->first() : null;
        $plan = $draft->data['plan_snapshot'] ?? [];
        $requiresPayment = config('website_checkout.enabled') && !empty($plan) && empty($plan['quote_required']) && ($plan['billing_interval'] ?? '') !== 'custom' && CheckoutMoney::minor((string) ($plan['price'] ?? '0')) > 0;
        if (!$order && !$requiresPayment) return;
        abort_unless($order?->status === 'paid' && $order->attempts()->where('status', 'paid')->where('environment', 'live')->exists(), 409, 'A confirmed live payment is required before activation. Test payments do not activate accounts.');
        abort_unless((int) (($state ?? $draft->data)['subscription_plan_id'] ?? 0) === (int) ($order->plan_snapshot['id'] ?? 0), 409, 'The selected plan differs from the paid offer. Reconcile billing before activation.');
    }
}
