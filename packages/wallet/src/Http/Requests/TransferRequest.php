<?php

namespace Incevio\Package\Wallet\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class TransferRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
        // return Auth::guard('customer')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'amount' => 'required|numeric',
        ];

        if ($this->has('email')) {
            $rules['email'] = 'required|email';
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
        return [
            // 'amount.min' => trans('packages.wallet.composite_unique'),
        ];
    }
}
