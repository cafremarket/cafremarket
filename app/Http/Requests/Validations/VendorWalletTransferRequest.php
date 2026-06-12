<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class VendorWalletTransferRequest extends Request
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
        $maxAmount = $shop ? (float) $shop->balance : 0;

        return [
            'amount' => 'required|numeric|min:1|max:'.$maxAmount,
            'email' => 'required|email',
            'recipient_type' => 'required|in:customer,vendor',
        ];
    }
}
