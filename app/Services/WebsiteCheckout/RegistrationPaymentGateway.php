<?php

namespace App\Services\WebsiteCheckout;

use App\Models\WebsitePaymentAttempt;
use Illuminate\Http\Request;

interface RegistrationPaymentGateway
{
    public function label(): string;
    public function environment(): string;
    public function available(): bool;
    public function supportsCurrency(string $currency): bool;
    /** Return remote_id and checkout_url; use the attempt UUID as the idempotency key. */
    public function create(WebsitePaymentAttempt $attempt): array;
    /** Authenticated provider lookup. Return status, remote_id, attempt_reference,
     * amount_minor, currency, environment and payment_reference. Never trust a return URL. */
    public function inspect(WebsitePaymentAttempt $attempt): array;
    /** Verify signature first. Return event_id and remote_id / attempt_reference /
     * payment_reference, or null for unrelated events. Do not return card or payer data. */
    public function webhook(Request $request): ?array;
    public function checkoutHosts(): array;
}
