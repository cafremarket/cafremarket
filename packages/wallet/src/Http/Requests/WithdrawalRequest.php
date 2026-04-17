<?php

namespace Incevio\Package\Wallet\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawalRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::guard('affiliate')->check() || Auth::user()->isMerchant();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $max_withdrawal = Auth::guard('affiliate')->check()
            ? Auth::guard('affiliate')->user()->wallet->balance
            : Auth::user()->shop->balance;

        return [
            'amount' => 'required|numeric|min:'.get_min_withdrawal_limit().'|max:'.$max_withdrawal,
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            // 'amount.min' => trans('packages.wallet.composite_unique'),
        ];
    }
}
