<?php

use App\Models\Order;
use Illuminate\Support\Facades\Cache;

if (! function_exists('is_wallet_credit_reward_enabled')) {
    /**
     * Check if the wallet credit reward system enabled
     */
    function is_wallet_credit_reward_enabled()
    {
        return Cache::rememberForever('wallet_credit_reward_system', function () {
            return get_from_option_table('wallet_credit_reward_system', false);
        });
    }
}

if (! function_exists('get_shop_credit_reward_value')) {
    /**
     * Return shops's global credit reward value
     */
    function get_shop_credit_reward_value($id)
    {
        return Cache::rememberForever("shop_credit_reward_value_{$id}", function () {
            return get_from_option_table('wallet_order_escrow_holding_duration', config('wallet.default.order_amount_escrow_holding_duration', 15));
        });
    }
}

if (! function_exists('get_min_withdrawal_limit')) {
    /**
     * Return min withdrawal limit
     */
    function get_min_withdrawal_limit()
    {
        return Cache::rememberForever('wallet_min_withdrawal_limit', function () {
            return get_from_option_table('wallet_min_withdrawal_limit', config('wallet.default.min_withdrawal_limit', 100));
        });
    }
}

if (! function_exists('get_order_escrow_holding_duration')) {
    /**
     * Return order amount escrow holding duration
     */
    function get_order_escrow_holding_duration()
    {
        return Cache::rememberForever('wallet_order_escrow_holding_duration', function () {
            return get_from_option_table('wallet_order_escrow_holding_duration', config('wallet.default.order_amount_escrow_holding_duration', 15));
        });
    }
}

if (! function_exists('get_reward_credit_holding_duration')) {
    /**
     * Return wallet release credit rewards in days
     */
    function get_reward_credit_holding_duration()
    {
        return Cache::rememberForever('wallet_release_credit_rewards_in_days', function () {
            return get_from_option_table('wallet_release_credit_rewards_in_days', config('wallet.default.credit_back_reward_release_in', 3));
        });
    }
}

if (! function_exists('get_credit_amount_for_order')) {
    /**
     * Calculate and return total reward credits for the given order
     *
     * @return float|int
     */
    function get_credit_amount_for_order(Order $order)
    {
        $credit = 0;

        foreach ($order->items as $item) {
            $credit += $item->credit_back_amount;
        }

        return $credit;
    }
}
