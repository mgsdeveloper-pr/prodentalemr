<?php

namespace App\Services\WebsiteCheckout;

class GatewayRegistry
{
    public function get(string $name): RegistrationPaymentGateway
    {
        $class = config('website_checkout.drivers.'.$name);
        abort_unless(is_string($class) && is_a($class, RegistrationPaymentGateway::class, true), 422, 'Unsupported payment gateway.');
        return app($class);
    }

    public function available(?string $currency = null): array
    {
        if (! config('website_checkout.enabled')) return [];
        $options = [];
        foreach (array_keys(config('website_checkout.drivers', [])) as $name) {
            $gateway = $this->get($name);
            if ($gateway->available() && (!$currency || $gateway->supportsCurrency($currency))) $options[] = ['id' => $name, 'name' => $gateway->label(), 'environment' => $gateway->environment()];
        }
        return $options;
    }

    public static function returnUrl(string $outcome): string
    {
        $url = (string) config('website_checkout.return_url');
        $parts = parse_url($url);
        abort_unless(($parts['scheme'] ?? '') === 'https' && isset($parts['host']) && ! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['query']) && ! isset($parts['fragment']), 503, 'Checkout return URL is not configured.');
        return $url.'?return='.$outcome;
    }

    public function safeUrl(string $name, ?string $url): ?string
    {
        if (! $url) return null;
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || ! in_array($parts['host'] ?? '', $this->get($name)->checkoutHosts(), true)) {
            throw new \RuntimeException('Gateway returned an unexpected checkout URL.');
        }
        return $url;
    }
}
