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

        $rules = [
            'amount' => 'required|numeric|min:'.get_min_withdrawal_limit().'|max:'.$max_withdrawal,
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
