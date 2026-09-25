<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Services\Wallet\ShopPayoutAccount;
use Illuminate\Support\Facades\Auth;

class VendorWalletWithdrawRequest extends Request
{
    public function authorize()
    {
        $user = Auth::guard('vendor_api')->user();

        return $user !== null && (int) $user->merchantId() > 0;
    }

    public function rules()
    {
        $shopId = (int) Auth::guard('vendor_api')->user()->merchantId();
        $shop = \App\Models\Shop::find($shopId);
        $maxWithdrawal = $shop ? (float) $shop->balance : 0;

        $rules = [
            'amount' => 'required|numeric|min:'.get_min_withdrawal_limit().'|max:'.$maxWithdrawal,
        ];

        return array_merge($rules, ShopPayoutAccount::rules($shop, $this));
    }
}
