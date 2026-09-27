<?php

namespace Incevio\Package\Wallet\Services;

use App\Models\Order;
use App\Models\Refund;
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
     * Give the vendor back part of the marketplace commission when a refund is approved.
     *
     * Only commission actually collected (sale credit on delivery) is returned:
     * the configured share (default 50%) scaled by the refunded part of the order,
     * never more than that share in total across several partial refunds.
     */
    public function returnCommissionForRefund(Refund $refund): ?Transaction
    {
        $order = $refund->order;
        $shop = $order ? $order->shop : null;

        if (! $refund->isApproved() || ! $shop || (float) $refund->amount <= 0) {
            return null;
        }

        $returns = $shop->transactions()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->where('meta->commission_return_order_id', $order->id)
            ->get();

        if ($returns->contains(fn ($t) => (int) $t->getFromMetaData('refund_id') === (int) $refund->id)) {
            return null; // already returned for this refund
        }

        $sale = $this->findVendorSaleCredit($order);
        $collected = $sale ? (float) $sale->getFromMetaData('marketplace_commission') : 0.0;

        if ($collected <= 0) {
            return null; // refunded before delivery: no commission was taken
        }

        $percent = max(0, min(100, (float) config('system.subscription.refund_commission_return_percent', 50)));
        $cap = round($collected * $percent / 100, 2);
        $alreadyReturned = (float) $returns->sum(fn ($t) => (float) $t->getFromMetaData('commission_return'));
        $gross = (float) ($sale->getFromMetaData('gross_sale_amount') ?: $order->grand_total);
        $refundedShare = $gross > 0 ? min(1, (float) $refund->amount / $gross) : 1;
        $amount = min(round($cap * $refundedShare, 2), round($cap - $alreadyReturned, 2));

        if ($amount <= 0) {
            return null;
        }

        return $shop->deposit($amount, [
            'type' => trans('packages.wallet.commission_return'),
            'purpose' => 'commission_return',
            'description' => trans('packages.wallet.commission_return_of', [
                'percent' => rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.'),
                'order' => $order->order_number,
            ]),
            'commission_return' => $amount,
            'commission_return_order_id' => $order->id,
            'refund_id' => $refund->id,
        ], true);
    }

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
