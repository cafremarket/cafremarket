<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Concerns\ValidatesProductCategories;
use App\Http\Requests\Request;

class CreateCatalogProductRequest extends Request
{
    use ValidatesProductCategories;
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
            'slug' => generate_unique_listing_slug((string) ($this->input('slug') ?: $this->input('name') ?: 'product')),
        ]);

        $this->prepareProductCategories();
    }

    public function rules()
    {
        return array_merge($this->productCategoryRules(), [
            'shop_id' => 'required|exists:shops,id',
            'name' => 'required|unique:products',
            'slug' => 'required|alpha_dash',
            'description' => 'required',
            'active' => 'required',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:'.$this->min_price ?? 0,
            'images.*' => 'mimes:jpg,jpeg,png,gif,svg',
            'video' => ['nullable', 'file', new \App\Rules\ProductVideoFile],
            'delete_video' => 'nullable|boolean',
        ]);
    }

    public function withValidator($validator)
    {
        $this->withProductCategoryValidator($validator);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return $this->productCategoryMessages();
    }
}
