<?php

namespace Incevio\Package\Wallet\Http\Requests;

use App\Http\Requests\Request;

class AdminCreateWalletRequest extends Request
{
    public function authorize()
    {
        return \Auth::user() && \Auth::user()->isSuperAdmin();
    }

    public function rules()
    {
        return [
            'email' => 'required|email',
            'user_type' => 'required|in:customer,merchant',
        ];
    }
}
