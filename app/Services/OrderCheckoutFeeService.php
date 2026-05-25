<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shop;
use App\Models\SubscriptionPlan;

/**
 * Order fees: subscription plan when enabled; otherwise default vendor commission.
 */
final class OrderCheckoutFeeService
{
    public const FEE_TYPE_FLAT = 'flat';

    public const FEE_TYPE_PERCENT = 'percent';

    private const MOBILE_PAYMENT_METHODS = ['mpesa', 'emola'];

    /**
     * @return array{base: float, subscription_fee: float, fee: float, total: float, enabled: bool}
     */
    public static function customerTransactionFee(
        string $paymentMethod,
        float|int|string $baseAmount,
        Shop|int|null $shop = null
    ): array {
        $base = round((float) $baseAmount, 2);
        $subscriptionFee = 0.0;

        if (in_array(strtolower(trim($paymentMethod)), self::MOBILE_PAYMENT_METHODS, true)) {
            $subscriptionFee = self::subscriptionTransactionFeeForShop($shop, $base);
        }

        $fee = round($subscriptionFee, 2);

        return [
            'base' => $base,
            'subscription_fee' => $subscriptionFee,
            'fee' => $fee,
            'total' => round($base + $fee, 2),
            'enabled' => in_array(strtolower(trim($paymentMethod)), self::MOBILE_PAYMENT_METHODS, true)
                && ($fee > 0 || (is_subscription_enabled() && self::subscriptionPlanForShop($shop))),
        ];
    }

    /**
     * @return array{base: float, subscription_fee: float, fee: float, total: float, enabled: bool}
     */
    public static function customerTransactionFeeForOrder(Order $order, ?string $paymentMethod = null): array
    {
        $order->loadMissing(['shop', 'paymentMethod']);
        $method = $paymentMethod
            ?? strtolower(trim((string) optional($order->paymentMethod)->code));

        return self::customerTransactionFee($method, $order->grand_total, $order->shop);
    }

    public static function subscriptionTransactionFeeForShop(Shop|int|null $shop, float|int|string $saleBase = 0): float
    {
        if (! is_subscription_enabled()) {
            return 0.0;
        }

        $plan = self::subscriptionPlanForShop($shop);

        if (! $plan) {
            return 0.0;
        }

        return self::calculatePlanFee(
            (float) $plan->transaction_fee,
            (string) ($plan->transaction_fee_type ?? self::FEE_TYPE_FLAT),
            (float) $saleBase
        );
    }

    public static function marketplaceCommissionForOrder($order): float
    {
        if (! $order instanceof Order) {
            $order = Order::findOrFail($order);
        }

        $order->loadMissing('shop');
        $shop = $order->shop;
        $saleBase = max(0, round((float) $order->grand_total, 2));

        if (! is_subscription_enabled()) {
            return self::defaultMarketplaceCommission($saleBase);
        }

        if (is_incevio_package_loaded('dynamicCommission')) {
            if ($shop->commission_rate !== null && (float) $shop->commission_rate > 0) {
                return round(((float) $shop->commission_rate * $saleBase) / 100, 2);
            }

            $dynamicCommissions = get_from_option_table('dynamicCommission_milestones');

            if (is_array($dynamicCommissions) && $dynamicCommissions !== []) {
                usort($dynamicCommissions, function ($a, $b) {
                    return $b['milestone'] - $a['milestone'];
                });

                foreach ($dynamicCommissions as $milestoneRow) {
                    if ($shop->periodic_sold_amount >= $milestoneRow['milestone']) {
                        return round(($milestoneRow['commission'] * $saleBase) / 100, 2);
                    }
                }
            }
        }

        $plan = self::subscriptionPlanForShop($shop);

        if ($plan && (float) $plan->marketplace_commission > 0) {
            return self::calculatePlanFee(
                (float) $plan->marketplace_commission,
                (string) ($plan->marketplace_commission_type ?? self::FEE_TYPE_PERCENT),
                $saleBase
            );
        }

        return 0.0;
    }

    public static function calculatePlanFee(float $rate, string $type, float $saleBase): float
    {
        if ($rate <= 0) {
            return 0.0;
        }

        if (strtolower(trim($type)) === self::FEE_TYPE_PERCENT) {
            return round($rate * max(0, $saleBase) / 100, 2);
        }

        return round($rate, 2);
    }

    /**
     * @return array{gross: float, marketplace_commission: float, total_deductions: float, net: float}
     */
    public static function vendorSettlementForOrder(Order $order): array
    {
        $gross = max(0, round((float) $order->grand_total, 2));
        $marketplaceCommission = self::marketplaceCommissionForOrder($order);
        $totalDeductions = round($marketplaceCommission, 2);

        return [
            'gross' => $gross,
            'marketplace_commission' => $marketplaceCommission,
            'total_deductions' => $totalDeductions,
            'net' => max(0, round($gross - $totalDeductions, 2)),
        ];
    }

    /**
     * @return array{gross: float, marketplace_commission: float, total_deductions: float, net: float}
     */
    public static function vendorSettlementPreview(
        float|int|string $baseAmount,
        Shop|int|null $shop = null
    ): array {
        $gross = max(0, round((float) $baseAmount, 2));

        $orderStub = new Order(['grand_total' => $gross]);
        if ($shop = self::resolveShop($shop)) {
            $orderStub->setRelation('shop', $shop);
        }

        $marketplaceCommission = $shop
            ? self::marketplaceCommissionForOrder($orderStub)
            : self::defaultMarketplaceCommission($gross);

        return [
            'gross' => $gross,
            'marketplace_commission' => $marketplaceCommission,
            'total_deductions' => round($marketplaceCommission, 2),
            'net' => max(0, round($gross - $marketplaceCommission, 2)),
        ];
    }

    public static function shopCanAcceptSales(Shop|int|null $shop): bool
    {
        $shop = self::resolveShop($shop);

        if (! $shop || ! (int) $shop->active) {
            return false;
        }

        if (! is_subscription_enabled()) {
            return true;
        }

        return self::subscriptionPlanForShop($shop) !== null;
    }

    public static function subscriptionPlanForShop(Shop|int|null $shop): ?SubscriptionPlan
    {
        $shop = self::resolveShop($shop);

        if (! $shop || ! is_subscription_enabled()) {
            return null;
        }

        return self::lookupPlan($shop);
    }

    public static function defaultMarketplaceCommissionPercent(): float
    {
        return max(0, (float) config('system.subscription.default_marketplace_commission', 10));
    }

    private static function defaultMarketplaceCommission(float $saleBase): float
    {
        return self::calculatePlanFee(
            self::defaultMarketplaceCommissionPercent(),
            self::FEE_TYPE_PERCENT,
            $saleBase
        );
    }

    private static function lookupPlan(Shop $shop): ?SubscriptionPlan
    {
        if (empty($shop->current_billing_plan)) {
            return null;
        }

        return SubscriptionPlan::where('plan_id', $shop->current_billing_plan)->first();
    }

    private static function resolveShop(Shop|int|null $shop): ?Shop
    {
        if ($shop === null) {
            return null;
        }

        return $shop instanceof Shop ? $shop : Shop::find($shop);
    }
}
