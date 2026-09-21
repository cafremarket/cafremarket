<?php

namespace Incevio\Package\Wallet\Services;

use App\Models\Order;
use Incevio\Package\Wallet\Models\Transaction;

class OrderWalletService
{
    /**
     * Credit vendor wallet for a delivered prepaid order (net of marketplace commission).
     *
     * @param  array|null  $meta
     * @return \Incevio\Package\Wallet\Models\Transaction
     */
    public function payVendor(Order $order, bool $confirmed = true, array $meta = [])
    {
        if ($existing = $this->findVendorSaleCredit($order)) {
            return $existing;
        }

        $settlement = get_vendor_settlement_for_order($order);

        return $order->shop->deposit($settlement['net'], array_merge([
            'type' => trans('app.sale'),
            'description' => trans('packages.wallet.sale_credit_after_commission', [
                'order' => $order->order_number,
                'commission' => get_formated_currency($settlement['total_deductions']),
            ]),
            'fee' => $settlement['total_deductions'],
            'sales_commission' => $settlement['marketplace_commission'],
            'marketplace_commission' => $settlement['marketplace_commission'],
            'affiliate_commission' => $settlement['affiliate_commission'] ?? 0,
            'gross_sale_amount' => $settlement['gross'],
            'net_vendor_amount' => $settlement['net'],
            'order_id' => $order->id,
        ], $meta), $confirmed);
    }

    public function reversal(Order $order, bool $confirmed = true, array $meta = [])
    {
        $settlement = get_vendor_settlement_for_order($order);

        // Take the net order amount from vendor's wallet
        $transection = $order->shop->forceWithdraw($settlement['net'], array_merge([
            'type' => trans('app.reversal'),
            'description' => trans('app.reversal_for_sale_of', ['order' => $order->order_number]),
            'fee' => $settlement['total_deductions'],
            'sales_commission' => $settlement['marketplace_commission'],
            'marketplace_commission' => $settlement['marketplace_commission'],
            'affiliate_commission' => $settlement['affiliate_commission'] ?? 0,
            'gross_sale_amount' => $settlement['gross'],
            'net_vendor_amount' => $settlement['net'],
            'order_id' => $order->id,
        ], $meta));

        return $transection;
    }

    public function refund(Order $order, bool $confirmed = true, array $meta = []) {}

    /**
     * Whether this order already has a vendor wallet sale credit.
     */
    protected function findVendorSaleCredit(Order $order): ?Transaction
    {
        if (! $order->shop) {
            return null;
        }

        return $order->shop->transactions()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->where('meta->order_id', $order->id)
            ->first();
    }
}
