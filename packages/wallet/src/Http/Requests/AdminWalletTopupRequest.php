<?php

namespace Incevio\Package\Wallet\Http\Requests;

use App\Http\Requests\Request;

class AdminWalletTopupRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user() && \Auth::user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'user_type' => 'required|in:customer,merchant',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ];
    }
}
