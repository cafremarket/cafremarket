<?php

namespace App\Http\Requests\Validations;

use Incevio\Package\Wallet\Http\Requests\DepositRequest;
use Illuminate\Support\Facades\Auth;

class VendorWalletDepositRequest extends DepositRequest
{
    public function authorize()
    {
        $user = Auth::guard('vendor_api')->user();

        return $user !== null && (int) $user->merchantId() > 0;
    }
}
