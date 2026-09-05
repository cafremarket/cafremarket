<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Support\Str;

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

        // Inventory fields are optional in the unified editor.
        // Provide safe defaults so storage/model layer won't fail.
        $salePrice = $this->input('sale_price');
        $stockQty = $this->input('stock_quantity');
        $sku = trim((string) $this->input('sku', ''));

        $this->merge([
            'sale_price' => filled(trim((string) $salePrice)) ? $salePrice : 0,
            'stock_quantity' => filled(trim((string) $stockQty)) ? $stockQty : 1,
            'condition' => filled(trim((string) $this->input('condition', ''))) ? $this->input('condition') : 'New',
            'available_from' => filled(trim((string) $this->input('available_from', '')))
                ? $this->input('available_from')
                : now()->subDay()->format('Y-m-d H:i:s'),
            'active' => $this->filled('active') ? (int) $this->input('active') : 1,
        ]);

        if ($sku === '') {
            $name = trim((string) ($this->input('name') ?: $this->input('shop_name') ?: 'product'));
            $generated = Str::upper(Str::slug($name, '_'));
            $sku = ($generated !== '' ? $generated : 'SKU') . '-' . Str::upper(Str::random(6));
            $this->merge(['sku' => $sku]);
        }

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
            'offer_start' => 'nullable|date|required_with:offer_price',
            'offer_end' => 'nullable|date|required_with:offer_price|after:offer_start',
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
            'offer_start.required_with' => trans('validation.offer_start_required'),
            'offer_start.after_or_equal' => trans('validation.offer_start_after'),
            'offer_end.required_with' => trans('validation.offer_end_required'),
            'offer_end.after' => trans('validation.offer_end_after'),
        ];
    }
}
