<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class OrderFeedbackCreateRequest extends Request
{
    /**
     * Only the customer who placed the order may leave feedback on it.
     *
     * @return bool
     */
    public function authorize()
    {
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
        } elseif (Auth::guard('api')->check()) {
            $customer = Auth::guard('api')->user();
        }

        if (isset($customer) && $customer instanceof Customer) {
            return $this->route('order')->customer_id == $customer->id;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }
}
