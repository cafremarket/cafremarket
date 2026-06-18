<?php

namespace Incevio\Package\Wallet\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Incevio\Package\Wallet\Models\Transaction;

class OrderWalletService
{
    /**
     * Credit vendor wallet for a paid order (net of marketplace commission).
     *
     * @param  array|null  $meta
     * @return \Incevio\Package\Wallet\Models\Transaction
     */
    public function payVendor(Order $order, bool $confirmed = true, array $meta = [])
    {
        if ($existing = $this->findVendorSaleCredit($order)) {
            return $existing;
        }

        $confirmed = get_order_escrow_holding_duration() == 0 ? true : false;

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
            'gross_sale_amount' => $settlement['gross'],
            'net_vendor_amount' => $settlement['net'],
            'order_id' => $order->id,
        ], $meta));

        // Revert all credit rewards that was not released yet
        if (is_wallet_credit_reward_enabled()) {
            foreach ($order->creditRewards as $reward) {
                // When the credit is not been released yet
                if ($reward->isReleased()) {
                    Log::channel('wallet')->info('The credit reward has been released and can\'t be reverted. Order #: '.$order->order_number);

                    continue; // Skip when the reward already released
                }

                // Returned the credit back amount to vendor's wallet
                $order->shop->deposit($reward->amount, [
                    'type' => trans('packages.wallet.reward_credit_reversal'),
                    'description' => trans('packages.wallet.credit_back_for_order', ['order' => $order->order_number]),
                    'fee' => 0,
                    'order_id' => $order->id,
                ], true);

                // Delete the a credit rewards
                $reward->delete();
            }
        }

        return $transection;
    }

    public function refund(Order $order, bool $confirmed = true, array $meta = []) {}

    /**
     * Initiate the creadit back reward and take it from vendor wallet
     *
     * @return void
     */
    public function initiateReward(Order $order, bool $confirmed = true, array $meta = [])
    {
        $reward_amount = get_credit_amount_for_order($order);

        if ($reward_amount > 0) {
            // Withdrawal the credit back amount from vendor's wallet
            $order->shop->forceWithdraw($reward_amount, array_merge([
                'type' => trans('packages.wallet.credit_back'),
                'description' => trans('packages.wallet.credit_back_for_order', ['order' => $order->order_number]),
                'fee' => 0,
                'order_id' => $order->id,
            ], $meta));

            // Initiate a credit reward for the customer
            $order->creditRewards()->create([
                'shop_id' => $order->shop_id,
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'details' => serialize($meta),
                'amount' => $reward_amount,
                'fee' => 0,
                'released' => null,
            ]);
        }
    }

    public function releaseReward(Order $order, bool $confirmed = true, array $meta = []) {}

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
