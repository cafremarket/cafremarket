<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Customer;

/**
 * Only reachable while an order's bank transfer proof is sitting rejected —
 * lets the customer either re-upload a proof (payment_method stays 'wire')
 * or switch to a different payment method entirely.
 */
class ChangeOrderPaymentMethodRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $order = $this->route('order');

        if (! $order || ! ($this->user() instanceof Customer)) {
            return false;
        }

        if ((int) $order->customer_id !== (int) $this->user()->id) {
            return false;
        }

        return $order->isWireTransferRejected();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'payment_method' => ['required', 'exists:payment_methods,code,enabled,1'],
        ];

        $mimes = 'mimes:jpg,jpeg,png,pdf';

        if ($this->input('payment_method') === 'wire') {
            $rules['wire_transfer_proof'] = ['required', $mimes];
        }

        if ($this->input('payment_method') === 'mpesa') {
            $rules['mpesa_number'] = ['required', 'string', 'regex:/^[\d\s\+]+$/'];
        }

        if ($this->input('payment_method') === 'emola') {
            $rules['emola_number'] = ['required', 'string', 'regex:/^(86|87)\d{7}$/'];
        }

        return $rules;
    }
}
