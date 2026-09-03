<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CreateAttributeRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() && $this->user()->isFromMerchant() && $this->user()->merchantId();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'shop_id' => $this->user()->merchantId(),
            'attribute_type_id' => resolve_attribute_type_id($this->input('name'), (int) $this->input('attribute_type_id') ?: null),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $shopId = $this->user()->merchantId();

        return [
            'shop_id' => 'required|integer',
            'attribute_type_id' => 'required|integer',
            'name' => [
                'bail',
                'required',
                Rule::unique('attributes', 'name')->where(function ($query) use ($shopId) {
                    return $query->where('shop_id', $shopId)->whereNull('deleted_at');
                }),
            ],
            'order' => 'integer|nullable',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
