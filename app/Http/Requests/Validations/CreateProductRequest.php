<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class CreateProductRequest extends Request
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
        $user = $this->user();
        $shop = $user->merchantShop();

        // Price/stock are optional in the unified editor and default to 0 when left blank.
        // SKU is optional too, but it is never auto-generated on the user's behalf.
        $salePrice = $this->input('sale_price');
        $stockQty = $this->input('stock_quantity');

        $this->merge([
            'sale_price' => filled(trim((string) $salePrice)) ? $salePrice : 0,
            'stock_quantity' => filled(trim((string) $stockQty)) ? $stockQty : 0,
            'condition' => filled(trim((string) $this->input('condition', ''))) ? $this->input('condition') : 'New',
            'available_from' => filled(trim((string) $this->input('available_from', '')))
                ? $this->input('available_from')
                : now()->subDay()->format('Y-m-d H:i:s'),
            'active' => $this->filled('active') ? (int) $this->input('active') : 1,
            // The `sku` DB column is NOT NULL. Laravel's ConvertEmptyStringsToNull
            // middleware turns a blank input into null before we get here, so
            // re-coerce it back to '' (not a generated value) to satisfy the column.
            'sku' => (string) $this->input('sku', ''),
        ]);

        $desiredSlug = trim((string) ($this->input('slug') ?: $this->input('name') ?: 'product'));
        $this->merge([
            'shop_id' => $shop?->id,
            'user_id' => $user->id,
            'slug' => generate_unique_listing_slug($desiredSlug),
        ]);
    }

    public function rules()
    {
        $user = $this->user();

        return [
            'shop_id' => 'required|exists:shops,id',
            'category_list' => 'required',
            'name' => 'required',
            'slug' => 'required|alpha_dash',
            'description' => 'required',
            'active' => 'required',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:'.$this->min_price ?? 0,
            'images.*' => 'mimes:jpg,jpeg,png,gif,svg',
            'video' => ['nullable', 'file', new \App\Rules\ProductVideoFile],
            'delete_video' => 'nullable|boolean',
            'sku' => 'bail|nullable|composite_unique:inventories,sku,shop_id:'.$user->merchantId(),
            'sale_price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric',
            'available_from' => 'nullable|date',
            'offer_prices.*' => 'nullable|numeric|min:0',
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
