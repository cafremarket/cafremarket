<?php

namespace App\Services\Emola;

use App\Exceptions\PaymentFailedException;
use App\Models\Order;

/**
 * Movitel USSD Push API v1.5 — input validation and constants.
 */
final class EmolaSpec
{
    public const CONTEXT_ORDER = 'order';

    public const CONTEXT_DEPOSIT = 'deposit';

    public static function normalizeMsisdn(string $msisdn): string
    {
        $digits = preg_replace('/\D/', '', $msisdn);

        if (preg_match('/^(86|87)\d{7}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/(?:258)?((86|87)\d{7})$/', $digits, $m)) {
            return $m[1];
        }

        throw new PaymentFailedException(trans('theme.emola_number_invalid'));
    }

    public static function sanitizeTransId(string $transId): string
    {
        $transId = preg_replace('/[^A-Za-z0-9]/', '', $transId);
        $max = (int) config('emola.limits.trans_id_max', 30);
        $min = (int) config('emola.limits.trans_id_min', 15);

        $transId = substr($transId, 0, $max);

        if (strlen($transId) < $min) {
            throw new PaymentFailedException('eMola transId must be '.$min.'–'.$max.' characters.');
        }

        return $transId;
    }

    public static function sanitizeRefNo(string $refNo): string
    {
        $refNo = substr(preg_replace('/[^A-Za-z0-9]/', '', $refNo), 0, (int) config('emola.limits.ref_no_max', 20));

        if ($refNo === '') {
            throw new PaymentFailedException('eMola refNo is required.');
        }

        return $refNo;
    }

    /** Movitel USSD maximum per push (spec §B.1 — 5-digit transAmount). */
    public static function movitelUssdMaxMzn(): int
    {
        $digits = (int) config('emola.limits.trans_amount_digits', 5);

        if ($digits <= 0) {
            return 99_999;
        }

        return (int) str_repeat('9', min($digits, 9));
    }

    /**
     * Parse a monetary value into whole Meticals for Movitel USSD (no decimals, no separators).
     */
    public static function parseMeticalAmount(float|int|string $amount): int
    {
        if (is_int($amount)) {
            return max(0, $amount);
        }

        if (is_float($amount)) {
            return max(0, (int) round($amount));
        }

        $normalized = trim((string) $amount);
        $normalized = str_replace([' ', "\u{00A0}"], '', $normalized);

        if ($normalized === '' || ! preg_match('/[\d]/', $normalized)) {
            throw new PaymentFailedException(trans('theme.emola_code_invalid_amount', [
                'movitel_max' => number_format(self::movitelUssdMaxMzn(), 0, '.', ','),
            ]));
        }

        // European: 1.234,56 or 1.234 (thousands with dot)
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $normalized)) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d{1,2})?$/', $normalized)) {
            // US thousands: 1,234.56
            $normalized = str_replace(',', '', $normalized);
        } elseif (str_contains($normalized, ',') && ! str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        if (! is_numeric($normalized)) {
            throw new PaymentFailedException(trans('theme.emola_code_invalid_amount', [
                'movitel_max' => number_format(self::movitelUssdMaxMzn(), 0, '.', ','),
            ]));
        }

        return max(0, (int) round((float) $normalized));
    }

    /** 0 means no application-side maximum. */
    public static function transactionMax(string $context = self::CONTEXT_ORDER): int
    {
        $key = $context === self::CONTEXT_DEPOSIT
            ? 'emola.limits.deposit_transaction_max'
            : 'emola.limits.order_transaction_max';

        return max(0, (int) config($key, 0));
    }

    /**
     * transAmount for pushUssdMessage — whole meticals, 1–5 digits (spec §B.1).
     *
     * @param  self::CONTEXT_*  $context
     */
    public static function formatTransAmount(float|int|string $amount, string $context = self::CONTEXT_ORDER): string
    {
        $value = self::parseMeticalAmount($amount);
        $max = self::transactionMax($context);
        $min = (int) config('emola.limits.trans_amount_min', 1);

        if ($value < $min) {
            throw new PaymentFailedException(
                trans('theme.emola_amount_below_min', [
                    'amount' => number_format($value, 0, '.', ','),
                    'min' => number_format($min, 0, '.', ','),
                ])
            );
        }

        if ($max > 0 && $value > $max) {
            $messageKey = $context === self::CONTEXT_DEPOSIT
                ? 'theme.emola_deposit_amount_limit'
                : 'theme.emola_amount_limit';

            throw new PaymentFailedException(
                trans($messageKey, [
                    'amount' => number_format($value, 0, '.', ','),
                    'max' => number_format($max, 0, '.', ','),
                    'min' => number_format($min, 0, '.', ','),
                ])
            );
        }

        $movitelMax = self::movitelUssdMaxMzn();

        if ($value > $movitelMax) {
            throw new PaymentFailedException(
                trans('theme.emola_movitel_max_exceeded', [
                    'amount' => number_format($value, 0, '.', ','),
                    'movitel_max' => number_format($movitelMax, 0, '.', ','),
                ])
            );
        }

        return (string) $value;
    }

    public static function transAmountFromOrder(Order $order): string
    {
        // grand_total is stored in platform (Metical) units at checkout — do not divide by FX
        // (get_system_currency_value sends wrong totals and Movitel returns business error 06).
        return self::formatTransAmount($order->grand_total, self::CONTEXT_ORDER);
    }

    /**
     * Whole-MZN charge for an order (grand total + subscription transaction fee).
     */
    public static function chargeMznForOrder(Order $order, ?string $paymentMethod = null): int
    {
        $breakdown = get_customer_transaction_fee_for_order($order, $paymentMethod);

        return (int) self::formatTransAmount($breakdown['total'], self::CONTEXT_ORDER);
    }

    /** @deprecated Use formatTransAmount() */
    public static function sanitizeAmount(float|int|string $amount, string $context = self::CONTEXT_ORDER): string
    {
        return self::formatTransAmount($amount, $context);
    }

    public static function sanitizeSmsContent(string $content): string
    {
        $max = (int) config('emola.limits.sms_content_max', 180);
        $content = trim($content);

        if ($content === '') {
            throw new PaymentFailedException('eMola smsContent is required.');
        }

        if (function_exists('mb_substr')) {
            return mb_substr($content, 0, $max);
        }

        return substr($content, 0, $max);
    }

    public static function sanitizeLanguage(?string $language): string
    {
        $lang = strtolower(substr((string) ($language ?: config('emola.language', 'pt')), 0, 2));

        return in_array($lang, ['en', 'pt'], true) ? $lang : 'pt';
    }

    public static function gatewayErrorMessage(string $code): ?string
    {
        return config('emola.gateway_errors.'.$code);
    }

    public static function businessErrorMessage(string $code): ?string
    {
        return config('emola.business_errors.'.$code);
    }

    /** Map Movitel business errorCode → theme.php translation key (no numeric suffixes). */
    public static function businessErrorThemeKey(?string $code): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $normalized = str_pad(ltrim(trim($code), '0') ?: '0', 2, '0', STR_PAD_LEFT);

        return match ($normalized) {
            '06' => 'theme.emola_code_invalid_amount',
            '10' => 'theme.emola_code_msisdn_not_whitelisted',
            '11' => 'theme.emola_code_pin_cancelled',
            default => null,
        };
    }

    /** Callback / query — payment completed (spec §C: errorCode 0 only). */
    public static function isPaymentSuccessCode(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        $normalized = trim($code);

        return $normalized === '0' || $normalized === '00' || (ctype_digit($normalized) && (int) $normalized === 0);
    }

    /** USSD dispatched, awaiting customer PIN (spec §C: 22). */
    public static function isPaymentPendingCode(?string $code): bool
    {
        return in_array(trim((string) $code), ['22'], true);
    }

    /**
     * Terminal failure / cancellation — must not show paid (e.g. 11 = PIN cancelled).
     */
    public static function isPaymentFailureCode(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        if (self::isPaymentSuccessCode($code) || self::isPaymentPendingCode($code)) {
            return false;
        }

        return true;
    }

    /** pushUssdQueryTrans — informational; never treat as paid without errorCode 0. */
    public static function isOrgResponseSuccess(?string $code): bool
    {
        $normalized = trim((string) $code);

        return in_array($normalized, ['01', '1'], true);
    }
}
