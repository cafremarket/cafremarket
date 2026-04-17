<?php

namespace Incevio\Package\Wallet\Traits;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

trait HasTransaction
{
    /**
     * Get the wallet based on user type
     *
     * @return Shop | Customer
     */
    public static function getWallet($user = 'customer', $email = null, $recipient_type = null)
    {
        if ($email) {
            if (isset($recipient_type)) {
                return $recipient_type == 'customer'
                        ? Customer::where('email', $email)->first()
                        : Shop::where('email', $email)->first();
            }

            if ($user == 'customer') {
                return Customer::where('email', $email)->first();
            }

            return Shop::where('email', $email)->first();     // Merchant
        }

        if (Auth::guard('customer')->check()) {           // Return logged in user's wallet
            return Auth::guard('customer')->user();
        }

        return Auth::guard('web')->user()->shop;
    }

    /**
     * Get the route name based on user type
     *
     * @return string
     */
    public static function getRouteName(string $to = 'deposit')
    {
        if ($to == 'wallet') {                                  // On success route to the wallet page
            return Auth::guard('customer')->check() ? 'customer.account.wallet' : 'merchant.wallet';
        }

        if (Auth::guard('customer')->check()) {                 // Customer deposit route
            return 'customer.account.wallet.deposit.form';
        }

        return 'merchant.wallet.deposit.form';                  // Merchant deposit route
    }
}
