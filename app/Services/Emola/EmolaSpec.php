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

    /**
     * Parse a monetary value into whole Meticals for Movitel USSD (no decimals, no separators).
     */
    public static function parseMeticalAmount(float|int|string $amount): int
    {
        if (is_int($amount)) {
            return $amount;
        }

        $normalized = trim((string) $amount);
        $normalized = str_replace([' ', "\u{00A0}"], '', $normalized);

        // European-style decimals: 1.234,56 → 1234.56
        if (preg_match('/^\d{1,3}(\.\d{3})+,\d{1,2}$/', $normalized)) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',') && ! str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        return (int) round((float) $normalized);
    }

    public static function transactionMax(string $context = self::CONTEXT_ORDER): int
    {
        if ($context === self::CONTEXT_DEPOSIT) {
            return (int) config('emola.limits.deposit_transaction_max', 1_000);
        }

        return (int) config('emola.limits.order_transaction_max', 50_000);
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

        if ($value < $min || $value > $max) {
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

        $formatted = (string) $value;

        if (strlen($formatted) > (int) config('emola.limits.trans_amount_digits', 5)) {
            throw new PaymentFailedException(trans('theme.emola_amount_too_long'));
        }

        return $formatted;
    }

    public static function transAmountFromOrder(Order $order): string
    {
        // grand_total is stored in platform (Metical) units at checkout — do not divide by FX
        // (get_system_currency_value sends wrong totals and Movitel returns business error 06).
        return self::formatTransAmount($order->grand_total, self::CONTEXT_ORDER);
    }

    /** @deprecated Use formatTransAmount() */
    public static function sanitizeAmount(float|int|string $amount): string
    {
        return self::formatTransAmount($amount, self::CONTEXT_ORDER);
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
