<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Inventory;
use Illuminate\Support\Str;

class UpdateProductRequest extends Request
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

    protected function prepareForValidation()
    {
        // Inventory fields are optional in the unified editor.
        // On update, if merchant leaves them blank, keep existing inventory values.
        $productId = $this->route('product');
        $inventory = $productId
            ? Inventory::where('product_id', $productId)->pluck('id')->first()
            : null;
        $inventory = $inventory ? Inventory::find($inventory) : null;

        $salePriceInput = $this->input('sale_price');
        $stockQtyInput = $this->input('stock_quantity');
        $skuInput = trim((string) $this->input('sku', ''));
        $conditionInput = trim((string) $this->input('condition', ''));

        $this->merge([
            'sale_price' => filled(trim((string) $salePriceInput)) ? $salePriceInput : ($inventory?->sale_price ?? 0),
            'stock_quantity' => filled(trim((string) $stockQtyInput)) ? $stockQtyInput : ($inventory?->stock_quantity ?? 1),
            'condition' => $conditionInput !== '' ? $conditionInput : ($inventory?->condition ?? 'New'),
        ]);

        if ($skuInput === '') {
            $sku = $inventory?->sku;
            if (! $sku) {
                $name = trim((string) ($this->input('name') ?: 'product'));
                $generated = Str::upper(Str::slug($name, '_'));
                $sku = ($generated !== '' ? $generated : 'SKU') . '-' . Str::upper(Str::random(6));
            }
            $this->merge(['sku' => $sku]);
        }

        $desiredSlug = trim((string) ($this->input('slug') ?: $this->input('name') ?: 'product'));
        $this->merge([
            'slug' => generate_unique_listing_slug(
                $desiredSlug,
                $productId ? (int) $productId : null,
                $inventory?->id
            ),
        ]);
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

        $rules = [
            'category_list' => 'required',
            'name' => 'required',
            'sale_price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric',
            'available_from' => 'nullable|date',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:'.$this->min_price ?? 0,
            'offer_start' => 'nullable|date|required_with:offer_price',
            'offer_end' => 'nullable|date|required_with:offer_price|after:offer_start',
            'image' => 'mimes:jpg,jpeg,png,gif,svg',
        ];

        $rules['sku'] = 'bail|nullable|composite_unique:inventories,sku,shop_id:'.$shop_id.','.$inventoryId;
        $rules['slug'] = 'bail|required|alpha_dash';

        if (is_incevio_package_loaded('pharmacy')) {
            $expiry_date_required = get_from_option_table('pharmacy_expiry_date_required', 1);
            $rules['expiry_date'] = (bool) $expiry_date_required ? 'required|date' : 'nullable|date';
        }

        return $rules;
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
            'offer_start.after_or_equal' => trans('validation.offer_start_after'),
            'required_with.required' => trans('validation.offer_end_required'),
            'offer_end.after' => trans('validation.offer_end_after'),
        ];
    }
}
