<?php

namespace App\Http\Requests\Validations;

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
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'nice_name' => 'nullable|string|max:50',
            // Email only needs to be unique within the store, not across the whole system —
            // different stores may each have their own delivery boy using the same email.
            'email' => 'required|email|max:255|composite_unique:delivery_boys,shop_id',
            'password' => 'required|confirmed|min:6',
            'dob' => 'nullable|date',
            'status' => 'required',
            'phone_number' => 'required|string|max:50',
            'image' => 'mimes:jpg,jpeg,png,svg',
        ];
    }
}
