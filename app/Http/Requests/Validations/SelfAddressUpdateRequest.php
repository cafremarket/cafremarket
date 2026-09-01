<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Customer;

class SelfAddressUpdateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user() instanceof Customer) {
            return $this->route('address')->addressable_id == $this->user()->id
                && $this->route('address')->addressable_type == Customer::class;
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
            'address_type' => 'bail|required|composite_unique:addresses,addressable_id,addressable_type,'.$this->route('address')->id,
            'address_title' => 'required|string|max:255',
            'address_line_1' => 'required|string',
            'address_line_2' => 'nullable|string',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string',
            'state_id' => 'nullable|integer',
            'zip_code' => 'nullable|string',
            'country_id' => 'required|integer',
            'phone' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'address_type.composite_unique' => trans('validation.composite_unique'),
        ];
    }
}
