<?php

namespace App\Http\Requests\Validations;

use Incevio\Package\Wallet\Http\Requests\DepositRequest;
use Illuminate\Support\Facades\Auth;

class VendorWalletDepositRequest extends DepositRequest
{
    public function authorize()
    {
        return Auth::guard('vendor_api')->check()
            && Auth::guard('vendor_api')->user()->shop !== null;
    }
}
