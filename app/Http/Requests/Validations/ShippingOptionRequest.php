<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class ShippingOptionRequest extends Request
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
            'cart' => 'nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            // legacy clients may still send zone — ignored for pricing
            'zone' => 'nullable',
        ];
    }
}
