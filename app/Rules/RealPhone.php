<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects phone numbers that are obviously fake (8888888888, 123456789, ...)
 * and Mozambican numbers that don't match a real mobile/landline prefix.
 */
class RealPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isPlausible((string) $value)) {
            $fail(trans('validation.phone_invalid'));
        }
    }

    public static function normalize(string $phone): string
    {
        $phone = trim($phone);
        $plus = str_starts_with($phone, '+') || str_starts_with($phone, '00');
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '00')) {
            $digits = substr($digits, 2);
        }

        return ($plus ? '+' : '').$digits;
    }

    public static function isPlausible(string $phone): bool
    {
        // Only spaces, dashes, dots, parentheses and a leading + are allowed around digits.
        if (! preg_match('/^\s*(\+|00)?[\d\s\-().]+$/', $phone)) {
            return false;
        }

        $normalized = self::normalize($phone);
        $digits = ltrim($normalized, '+');

        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return false;
        }

        // Same digit 7+ times in a row, or the whole number one repeated digit.
        if (preg_match('/(\d)\1{6,}/', $digits) || count(array_unique(str_split($digits))) <= 2) {
            return false;
        }

        foreach (['1234567', '2345678', '3456789', '9876543', '8765432', '7654321'] as $run) {
            if (str_contains($digits, $run)) {
                return false;
            }
        }

        // Mozambique: +258 followed by 8[2-7]XXXXXXX (mobile) or 2XXXXXXX (landline).
        $national = null;
        if (str_starts_with($digits, '258') && strlen($digits) >= 11) {
            $national = substr($digits, 3);
        } elseif (! str_starts_with($normalized, '+') && strlen($digits) === 9 && $digits[0] === '8') {
            $national = $digits;
        }

        if ($national !== null && ! preg_match('/^(8[2-7]\d{7}|2\d{7})$/', $national)) {
            return false;
        }

        return true;
    }
}
