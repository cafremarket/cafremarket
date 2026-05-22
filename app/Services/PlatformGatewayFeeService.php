<?php

namespace App\Services;

/**
 * Platform fees for mobile-money payments (M-Pesa / eMola) and vendor payouts.
 * Configured via Admin → Wallet settings (options table).
 */
final class PlatformGatewayFeeService
{
    public const TYPE_FLAT = 'flat';

    public const TYPE_PERCENT = 'percent';

    public const PAYMENT_METHODS = ['mpesa', 'emola'];

    /**
     * @return array{base: float, fee: float, total: float, enabled: bool, type: string|null, rate: float}
     */
    public static function paymentFee(string $paymentMethod, float|int|string $baseAmount): array
    {
        $base = round((float) $baseAmount, 2);
        $method = strtolower(trim($paymentMethod));

        if (! in_array($method, self::PAYMENT_METHODS, true)) {
            return self::result($base, 0, false, null, 0);
        }

        if (! self::isEnabled("platform_fee_{$method}_enabled")) {
            return self::result($base, 0, false, null, 0);
        }

        $type = self::feeType("platform_fee_{$method}_type");
        $rate = self::feeValue("platform_fee_{$method}_value");
        $fee = self::calculate($base, $type, $rate);

        return self::result($base, $fee, true, $type, $rate);
    }

    public static function payoutFee(float|int|string $withdrawalAmount): float
    {
        $base = round(abs((float) $withdrawalAmount), 2);

        if (! self::isEnabled('platform_fee_payout_enabled')) {
            return 0.0;
        }

        $type = self::feeType('platform_fee_payout_type');
        $rate = self::feeValue('platform_fee_payout_value');

        return self::calculate($base, $type, $rate);
    }

    public static function isPaymentFeeEnabled(string $paymentMethod): bool
    {
        $method = strtolower(trim($paymentMethod));

        return in_array($method, self::PAYMENT_METHODS, true)
            && self::isEnabled("platform_fee_{$method}_enabled");
    }

    public static function isPayoutFeeEnabled(): bool
    {
        return self::isEnabled('platform_fee_payout_enabled');
    }

    /**
     * Charge amount for gateways (whole MZN for M-Pesa USSD).
     */
    public static function chargeAmount(string $paymentMethod, float|int|string $baseAmount): int
    {
        $breakdown = self::paymentFee($paymentMethod, $baseAmount);

        return (int) round($breakdown['total']);
    }

    private static function calculate(float $base, string $type, float $rate): float
    {
        if ($base <= 0 || $rate <= 0) {
            return 0.0;
        }

        $fee = $type === self::TYPE_PERCENT
            ? round($base * $rate / 100, 2)
            : round($rate, 2);

        return max(0, $fee);
    }

    /**
     * @return array{base: float, fee: float, total: float, enabled: bool, type: string|null, rate: float}
     */
    private static function result(float $base, float $fee, bool $enabled, ?string $type, float $rate): array
    {
        return [
            'base' => $base,
            'fee' => $fee,
            'total' => round($base + $fee, 2),
            'enabled' => $enabled,
            'type' => $type,
            'rate' => $rate,
        ];
    }

    private static function isEnabled(string $option): bool
    {
        return (bool) get_from_option_table($option, 0);
    }

    private static function feeType(string $option): string
    {
        $type = strtolower((string) get_from_option_table($option, self::TYPE_FLAT));

        return $type === self::TYPE_PERCENT ? self::TYPE_PERCENT : self::TYPE_FLAT;
    }

    private static function feeValue(string $option): float
    {
        return max(0, (float) get_from_option_table($option, 0));
    }
}
