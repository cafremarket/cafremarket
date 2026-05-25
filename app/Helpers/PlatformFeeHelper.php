<?php

use App\Models\Order;
use App\Models\Shop;
use App\Services\OrderCheckoutFeeService;
use App\Services\PlatformGatewayFeeService;

if (! function_exists('format_subscription_plan_fee')) {
    function format_subscription_plan_fee(float $value, string $type): string
    {
        if (strtolower($type) === 'percent') {
            return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'%';
        }

        return get_formated_currency($value);
    }
}

if (! function_exists('get_platform_payment_fee')) {
    /** Wallet top-up gateway fee preview only. */
    function get_platform_payment_fee(string $paymentMethod, float|int|string $baseAmount): array
    {
        return PlatformGatewayFeeService::paymentFee($paymentMethod, $baseAmount);
    }
}

if (! function_exists('get_customer_transaction_fee')) {
    /**
     * @return array{base: float, subscription_fee: float, fee: float, total: float, enabled: bool}
     */
    function get_customer_transaction_fee(string $paymentMethod, float|int|string $baseAmount, $shop = null): array
    {
        return OrderCheckoutFeeService::customerTransactionFee($paymentMethod, $baseAmount, $shop);
    }
}

if (! function_exists('get_customer_transaction_fee_for_order')) {
    function get_customer_transaction_fee_for_order(Order $order, ?string $paymentMethod = null): array
    {
        return OrderCheckoutFeeService::customerTransactionFeeForOrder($order, $paymentMethod);
    }
}

if (! function_exists('get_platform_payout_fee')) {
    function get_platform_payout_fee(float|int|string $withdrawalAmount): float
    {
        return PlatformGatewayFeeService::payoutFee($withdrawalAmount);
    }
}

if (! function_exists('get_marketplace_commission_for_order')) {
    function get_marketplace_commission_for_order($order): float
    {
        return OrderCheckoutFeeService::marketplaceCommissionForOrder($order);
    }
}

if (! function_exists('get_vendor_settlement_for_order')) {
    /**
     * @return array{gross: float, sales_commission: float, marketplace_commission: float, total_deductions: float, net: float}
     */
    function get_vendor_settlement_for_order($order): array
    {
        if (! $order instanceof Order) {
            $order = Order::findOrFail($order);
        }

        $settlement = OrderCheckoutFeeService::vendorSettlementForOrder($order);

        return [
            'gross' => $settlement['gross'],
            'sales_commission' => $settlement['marketplace_commission'],
            'marketplace_commission' => $settlement['marketplace_commission'],
            'total_deductions' => $settlement['total_deductions'],
            'net' => $settlement['net'],
        ];
    }
}

if (! function_exists('shop_can_accept_sales')) {
    function shop_can_accept_sales(Shop|int|null $shop): bool
    {
        return OrderCheckoutFeeService::shopCanAcceptSales($shop);
    }
}

if (! function_exists('persist_order_checkout_fees')) {
    function persist_order_checkout_fees(Order $order, string $paymentMethod): void
    {
        if (! in_array($paymentMethod, ['mpesa', 'emola'], true)) {
            return;
        }

        $fees = get_customer_transaction_fee_for_order($order, $paymentMethod);
        $order->platform_payment_fee = 0;
        $order->subscription_transaction_fee = $fees['subscription_fee'];
        $order->save();
    }
}

if (! function_exists('get_payout_commission_preview')) {
    function get_payout_commission_preview(string $payoutMethod, float|int|string $amount): array
    {
        return PlatformGatewayFeeService::payoutFeeForMethod($payoutMethod, $amount);
    }
}

if (! function_exists('format_payout_instruction_text')) {
    function format_payout_instruction_text(string $payoutMethod, array $details): string
    {
        $method = strtolower(trim($payoutMethod));

        if ($method === 'mpesa') {
            return 'M-Pesa: '.($details['mobile'] ?? '');
        }

        if ($method === 'emola') {
            return 'eMola: '.($details['mobile'] ?? '');
        }

        $parts = array_filter([
            $details['bank_name'] ?? null,
            $details['account_holder'] ?? null,
            $details['account_number'] ?? null,
        ]);

        return trans('packages.wallet.payout_method_bank_transfer').': '.implode(' / ', $parts);
    }
}

if (! function_exists('resolve_platform_payout_fee')) {
    function resolve_platform_payout_fee(float|int|string $withdrawalAmount, float|int|string|null $requestedFee = null): float
    {
        return 0.0;
    }
}

if (! function_exists('resolve_platform_payout_fee_for_method')) {
    function resolve_platform_payout_fee_for_method(
        string $payoutMethod,
        float|int|string $withdrawalAmount,
        float|int|string|null $requestedFee = null
    ): float {
        return 0.0;
    }
}
