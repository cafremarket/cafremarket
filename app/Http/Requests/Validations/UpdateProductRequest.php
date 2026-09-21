<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Concerns\ValidatesProductCategories;
use App\Http\Requests\Request;
use App\Models\Inventory;

class UpdateProductRequest extends Request
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

    protected function prepareForValidation()
    {
        // Price/stock default to 0 when left blank. SKU is optional but never auto-generated.
        $productId = $this->route('product');
        $inventory = $productId
            ? Inventory::where('product_id', $productId)->pluck('id')->first()
            : null;
        $inventory = $inventory ? Inventory::find($inventory) : null;

        $salePriceInput = $this->input('sale_price');
        $stockQtyInput = $this->input('stock_quantity');
        $conditionInput = trim((string) $this->input('condition', ''));

        $this->merge([
            'sale_price' => filled(trim((string) $salePriceInput)) ? $salePriceInput : 0,
            'stock_quantity' => filled(trim((string) $stockQtyInput)) ? $stockQtyInput : 0,
            'condition' => $conditionInput !== '' ? $conditionInput : ($inventory?->condition ?? 'New'),
            // The `sku` DB column is NOT NULL. Laravel's ConvertEmptyStringsToNull
            // middleware turns a blank input into null before we get here, so
            // re-coerce it back to '' (not a generated value) to satisfy the column.
            'sku' => (string) $this->input('sku', ''),
        ]);

        $name = trim((string) $this->input('name', ''));
        $plainDescription = trim(preg_replace(
            '/\s+/',
            ' ',
            strip_tags((string) $this->input('description', ''))
        ) ?? '');
        $metaDescLimit = (int) config('seo.meta.description_character_limit', 160);

        // Slug + SEO meta are derived from name/description (no customer input).
        $this->merge([
            'slug' => generate_unique_listing_slug(
                $name !== '' ? $name : 'product',
                $productId ? (int) $productId : null,
                $inventory?->id
            ),
            'meta_title' => $name !== '' ? $name : null,
            'meta_description' => $plainDescription !== ''
                ? \Illuminate\Support\Str::limit($plainDescription, $metaDescLimit, '')
                : null,
        ]);

        $this->prepareProductCategories();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route('product');
        $shop_id = $this->user()->merchantId(); // Get current user's shop_id

        Request::merge([
            'shop_id' => $shop_id,
            'user_id' => $this->user()->id,
        ]);

        if (! $this->input('key_features')) {
            $this->merge(['key_features' => null]);
        }

        if (! $this->input('linked_items')) {
            $this->merge(['linked_items' => null]);
        }

        $inventoryId = Inventory::where('product_id', $id)->pluck('id')->first();

        $rules = array_merge($this->productCategoryRules(), [
            'name' => 'required',
            'sale_price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric',
            'available_from' => 'nullable|date',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:'.$this->min_price ?? 0,
            'variant_offer_prices.*' => 'nullable|numeric|min:0',
            'offer_prices.*' => 'nullable|numeric|min:0',
            'image' => 'mimes:jpg,jpeg,png,gif,svg',
            'video' => ['nullable', 'file', new \App\Rules\ProductVideoFile],
            'delete_video' => 'nullable|boolean',
        ]);

        $rules['sku'] = 'bail|nullable|composite_unique:inventories,sku,shop_id:'.$shop_id.','.$inventoryId;
        $rules['slug'] = 'bail|required|alpha_dash';

        if (is_incevio_package_loaded('pharmacy')) {
            $expiry_date_required = get_from_option_table('pharmacy_expiry_date_required', 1);
            $rules['expiry_date'] = (bool) $expiry_date_required ? 'required|date' : 'nullable|date';
        }

        return $rules;
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
