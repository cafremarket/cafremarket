<?php

namespace Incevio\Package\Wallet\Http\Requests;

use App\Common\CanCreateStripeCustomer;
use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class DepositRequest extends Request
{
    use CanCreateStripeCustomer;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (Auth::guard('web')->check() && Auth::user()->isMerchant()) {
            return true;
        }

        return Auth::guard('api')->check() || Auth::guard('customer')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Create Stripe Customer for future use
        if ($this->has('remember_the_card') && $this->input('payment_method') == 'stripe') {
            $this->merge([
                'payee' => $this->createStripeCustomer(),
            ]);
        }

        $rules = [
            'amount' => 'required|numeric|min:1',
            'payment_method' => $this->input('payment_method') == 'saved_card' ? '' : 'required|exists:payment_methods,code',
        ];

        if ($this->input('payment_method') === 'mpesa') {
            $rules['mpesa_number'] = 'required|string|regex:/^[\d\s\+]+$/';
        }

        return $rules;
    }
}
