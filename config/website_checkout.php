<?php

return [
    'enabled' => env('WEBSITE_CHECKOUT_ENABLED', false),
    'return_url' => env('WEBSITE_CHECKOUT_RETURN_URL', 'https://denentiq.com/checkout'),
    'stripe_webhook_secret' => env('WEBSITE_CHECKOUT_STRIPE_WEBHOOK_SECRET'),
    'paypal_webhook_id' => env('WEBSITE_CHECKOUT_PAYPAL_WEBHOOK_ID'),
    // Add another implementation of RegistrationPaymentGateway here.
    'drivers' => [
        'stripe' => \App\Services\WebsiteCheckout\StripeRegistrationGateway::class,
        'paypal' => \App\Services\WebsiteCheckout\PayPalRegistrationGateway::class,
    ],
];
