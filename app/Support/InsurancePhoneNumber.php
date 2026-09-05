<?php

namespace App\Support;

final class InsurancePhoneNumber
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $phone = trim((string) $value);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/^tel:/i', '', $phone) ?? $phone;

        // Preserve extensions until they have a dedicated storage field.
        if (preg_match('/(?:ext\.?|extension|x)\s*\d+\s*$/i', $phone)) {
            return $phone;
        }

        if (str_starts_with($phone, '+')) {
            $digits = preg_replace('/\D+/', '', $phone) ?? '';

            return preg_match('/^[1-9]\d{7,14}$/', $digits) ? '+'.$digits : $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+'.$digits;
        }

        return $phone;
    }
}
