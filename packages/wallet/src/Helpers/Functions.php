<?php

use Illuminate\Support\Facades\Cache;

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
