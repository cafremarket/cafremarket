<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class CreateTaxRequest extends Request
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
        Request::merge(['shop_id' => Request::user()->merchantId()]); // Set shop_id

        return [
            'name' => 'required',
            'type' => 'required|in:percent,fixed',
            'taxrate' => 'required|numeric|min:0',
            'country_id' => 'required',
        ];
    }
}
