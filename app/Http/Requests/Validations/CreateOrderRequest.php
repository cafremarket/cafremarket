<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Address;
use App\Models\Customer;

class CreateOrderRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        Request::merge(['order_number' => get_formated_order_number($this->user()->merchantId())]); // Set order number

        // The order will ship to the selected customer address (when given as an ID)
        if (is_numeric($this->input('shipping_address'))) {
            Request::merge(['ship_to' => (int) $this->input('shipping_address')]);
        }

        return [
            'cart.*.inventory_id' => 'required',
            'cart.*.item_description' => 'required',
            'cart.*.quantity' => 'required',
            'cart.*.unit_price' => 'required',
            'customer_id' => 'required',
            'payment_method_id' => 'required',
            'payment_status' => 'required',
            'billing_address' => 'required',
        ];
    }

    /**
     * Make sure selected address IDs belong to the order's customer.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach (['shipping_address', 'billing_address'] as $field) {
                $value = $this->input($field);

                if (! is_numeric($value)) {
                    continue;
                }

                $belongsToCustomer = Address::where('id', $value)
                    ->where('addressable_type', Customer::class)
                    ->where('addressable_id', $this->input('customer_id'))
                    ->exists();

                if (! $belongsToCustomer) {
                    $validator->errors()->add($field, trans('validation.exists', ['attribute' => str_replace('_', ' ', $field)]));
                }
            }
        });
    }
}
