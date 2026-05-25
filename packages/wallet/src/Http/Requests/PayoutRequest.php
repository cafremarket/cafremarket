<?php

namespace Incevio\Package\Wallet\Http\Requests;

use App\Http\Requests\Request;

class PayoutRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'shop_id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'payout_payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
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
            'payout_payment_proof.required' => trans('packages.wallet.payout_payment_proof_required'),
            'payout_payment_proof.mimes' => trans('packages.wallet.payout_payment_proof_mimes'),
        ];
    }
}
