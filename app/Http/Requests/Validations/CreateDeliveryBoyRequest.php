<?php

namespace App\Http\Requests\Validations;

use App\Models\DeliveryBoy;
use Illuminate\Foundation\Http\FormRequest;

class CreateDeliveryBoyRequest extends FormRequest
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
        // A password is only required for the first store that registers this
        // email — every later store reuses that rider's existing password
        // (see EloquentDeliveryBoy::store()), so the field is optional here.
        // (Computed directly rather than via a closure rule: Laravel skips
        // closure rules entirely for empty/absent fields unless the rule is
        // "implicit" like `required`, so the required-ness has to be baked
        // into the rule string itself.)
        $emailAlreadyRegistered = DeliveryBoy::where('email', $this->input('email'))->exists();

        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'nice_name' => 'nullable|string|max:50',
            // Email only needs to be unique within the store, not across the whole system —
            // different stores may each have their own delivery boy using the same email.
            'email' => 'required|email|max:255|composite_unique:delivery_boys,shop_id',
            'password' => ($emailAlreadyRegistered ? 'nullable' : 'required').'|confirmed|min:6',
            'dob' => 'nullable|date',
            'status' => 'required',
            'phone_number' => 'required|string|max:50',
            'image' => 'mimes:jpg,jpeg,png,svg',
        ];
    }
}
