<?php

namespace App\Services\WebsiteCheckout;

class CheckoutMoney
{
    public const CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'INR'];
    public static function minor(string $amount): int
    {
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $amount)) throw new \RuntimeException('Invalid payment amount.');
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
    public static function decimal(int $minor): string { return intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT); }
}
