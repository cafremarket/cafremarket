<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Customer;

/**
 * Reachable in two cases: while an order's bank transfer proof is sitting
 * rejected (re-upload a proof, or switch to a different payment method), or
 * — more generally — whenever the order simply isn't paid yet (e.g. a
 * vendor-built chat quote, where the customer never picked a payment
 * method in the first place, or any other still-unpaid order).
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

        return $order->isWireTransferRejected() || ! $order->isPaid();
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
