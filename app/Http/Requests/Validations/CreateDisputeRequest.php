<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Customer;

class CreateDisputeRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user() instanceof Customer) {
            return $this->route('order')->customer_id == $this->user()->id;
        }

        return $this->route('order')->shop_id == $this->user()->merchantId();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $order = $this->route('order');

        $this->merge(['refund_amount' => get_system_currency_value($this->input('refund_amount'), $order->currency_id)]);

        $max = $order->grand_total;

        Request::merge([
            'order_id' => $order->id,
            'shop_id' => $order->shop_id,
            'customer_id' => $order->customer_id,
            'raised_by' => $this->user() instanceof Customer
                ? \App\Models\Dispute::RAISED_BY_CUSTOMER
                : \App\Models\Dispute::RAISED_BY_VENDOR,
        ]);

        return [
            'dispute_type_id' => 'required',
            'order_received' => 'required',
            'description' => 'required',
            'product_id' => 'nullable',
            'refund_amount' => 'required|numeric|max:'.$max,
        ];
    }

    /**
     * Customers can only ask for a refund/return on received goods while the
     * items' refund period is open (vendors raising disputes are not limited).
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->user() instanceof Customer || $validator->errors()->isNotEmpty()) {
                return;
            }

            $error = \App\Services\Orders\RefundWindow::customerRequestError(
                $this->route('order'),
                filter_var($this->input('order_received'), FILTER_VALIDATE_BOOLEAN),
                $this->input('product_id')
            );

            if ($error) {
                $validator->errors()->add($this->filled('product_id') ? 'product_id' : 'order_received', $error);
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'dispute_type_id.required' => trans('theme.validation.dispute_type_id_required'),
            'product_id.required_with' => trans('theme.validation.dispute_product_id_required_with'),
        ];
    }
}
