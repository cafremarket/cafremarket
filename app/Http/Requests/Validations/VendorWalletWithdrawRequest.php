<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
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
            'payout_method' => 'required|in:bank_transfer,mpesa,emola',
        ];

        if ($this->input('payout_method') === 'bank_transfer') {
            $rules['payout_bank_name'] = 'required|string|max:255';
            $rules['payout_account_holder'] = 'required|string|max:255';
            $rules['payout_account_number'] = 'required|string|max:255';
        }

        if (in_array($this->input('payout_method'), ['mpesa', 'emola'], true)) {
            $rules['payout_mobile'] = 'required|string|max:32';
        }

        return $rules;
    }
}
