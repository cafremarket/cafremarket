<?php

namespace Incevio\Package\Wallet\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderWalletService
{
    /**
     * make the order payemnt to vendor
     *
     * @param  array|null  $meta
     * @return void
     */
    public function payVendor(Order $order, bool $confirmed = true, array $meta = [])
    {
        $confirmed = get_order_escrow_holding_duration() == 0 ? true : false;

        $fee = getPlatformFeeForOrder($order);

        return $order->shop->deposit($order->grand_total - $fee, array_merge([
            'type' => trans('app.sale'),
            'description' => trans('app.for_sale_of', ['order' => $order->order_number]),
            'fee' => $fee,
            'order_id' => $order->id,
        ], $meta), $confirmed);
    }

    public function reversal(Order $order, bool $confirmed = true, array $meta = [])
    {
        $fee = getPlatformFeeForOrder($order);

        // Take the order amount from vendor's wallet
        $transection = $order->shop->forceWithdraw($order->grand_total - $fee, array_merge([
            'type' => trans('app.reversal'),
            'description' => trans('app.reversal_for_sale_of', ['order' => $order->order_number]),
            'fee' => $fee,
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
}
