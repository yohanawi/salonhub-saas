<?php

namespace App\Services\Messaging;

/**
 * Turns the phone numbers people type at the counter into international digits for SMS/WhatsApp
 * providers. Local Sri Lankan numbers (077 123 4567) get the 94 country code.
 */
final class PhoneNumber
{
    public static function normalize(?string $phone, string $countryCode = '94'): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        $digits = match (true) {
            str_starts_with($digits, '00') => substr($digits, 2),
            str_starts_with($digits, '0') && strlen($digits) === 10 => $countryCode . substr($digits, 1),
            strlen($digits) === 9 && str_starts_with($digits, '7') => $countryCode . $digits,
            default => $digits,
        };

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }
}
