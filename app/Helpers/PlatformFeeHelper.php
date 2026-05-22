<?php

use App\Services\PlatformGatewayFeeService;

if (! function_exists('get_platform_payment_fee')) {
    /**
     * @return array{base: float, fee: float, total: float, enabled: bool, type: string|null, rate: float}
     */
    function get_platform_payment_fee(string $paymentMethod, float|int|string $baseAmount): array
    {
        return PlatformGatewayFeeService::paymentFee($paymentMethod, $baseAmount);
    }
}

if (! function_exists('get_platform_payout_fee')) {
    function get_platform_payout_fee(float|int|string $withdrawalAmount): float
    {
        return PlatformGatewayFeeService::payoutFee($withdrawalAmount);
    }
}

if (! function_exists('resolve_platform_payout_fee')) {
    /**
     * Use admin-entered fee when > 0, otherwise apply configured payout fee.
     */
    function resolve_platform_payout_fee(float|int|string $withdrawalAmount, float|int|string|null $requestedFee = null): float
    {
        if ($requestedFee !== null && (float) $requestedFee > 0) {
            return round((float) $requestedFee, 2);
        }

        return PlatformGatewayFeeService::payoutFee($withdrawalAmount);
    }
}
