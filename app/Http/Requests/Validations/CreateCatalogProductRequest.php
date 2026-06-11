<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class CreateCatalogProductRequest extends Request
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
    protected function prepareForValidation()
    {
        $shop = $this->user()->merchantShop();

        $this->merge([
            'shop_id' => $shop?->id,
        ]);
    }

    public function rules()
    {
        return [
            'shop_id' => 'required|exists:shops,id',
            'category_list' => 'required',
            'name' => 'required|unique:products',
            'slug' => 'required|unique:products',
            'description' => 'required',
            'active' => 'required',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:'.$this->min_price ?? 0,
            'images.*' => 'mimes:jpg,jpeg,png,gif,svg',
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
            'category_list.required' => trans('validation.category_list_required'),
        ];
    }
}
