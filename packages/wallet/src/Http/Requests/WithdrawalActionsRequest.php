<?php

namespace Incevio\Package\Wallet\Http\Requests;

use App\Http\Requests\Request;

class WithdrawalActionsRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->route()->getName() == 'admin.payout.decline') {
            return [];
        }

        return [
            'payout_payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'payout_payment_proof.required' => trans('packages.wallet.payout_payment_proof_required'),
            'payout_payment_proof.mimes' => trans('packages.wallet.payout_payment_proof_mimes'),
        ];
    }
}
