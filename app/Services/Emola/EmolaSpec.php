<?php

namespace App\Services\Emola;

use App\Exceptions\PaymentFailedException;
use App\Models\Order;

/**
 * Movitel USSD Push API v1.5 — input validation and constants.
 */
final class EmolaSpec
{
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
     * transAmount for pushUssdMessage — whole meticals, 1–5 digits (spec §B.1).
     */
    public static function formatTransAmount(float|int|string $amount): string
    {
        $value = (int) round((float) $amount);
        $max = (int) config('emola.limits.trans_amount_max', 9999);
        $min = 1;

        if ($value < $min || $value > $max) {
            throw new PaymentFailedException(
                trans('theme.emola_amount_limit', [
                    'amount' => number_format($value, 0, '.', ','),
                    'max' => number_format($max, 0, '.', ','),
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
        $amount = (float) $order->grand_total;

        if (is_incevio_package_loaded('dynamic-currency') && $order->currency_id) {
            $amount = (float) get_system_currency_value($amount, $order->currency_id);
        }

        return self::formatTransAmount($amount);
    }

    /** @deprecated Use formatTransAmount() */
    public static function sanitizeAmount(float|int|string $amount): string
    {
        return self::formatTransAmount($amount);
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

    /** Callback / query success — errorCode 0 (accepts 0, 00, 000). */
    public static function isPaymentSuccessCode(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        $normalized = trim($code);

        return $normalized === '0' || $normalized === '00' || (ctype_digit($normalized) && (int) $normalized === 0);
    }

    /** pushUssdQueryTrans — orgResponseCode 01 means origin transaction successful (spec §B.2). */
    public static function isOrgResponseSuccess(?string $code): bool
    {
        $normalized = trim((string) $code);

        return in_array($normalized, ['01', '1'], true);
    }
}
