<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;

class CreateInventoryWithVariantRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Oversized POST (post_max_size) leaves $_POST empty — fail with a clear message.
        if ($this->isPostSizeExceeded()) {
            return false;
        }

        $productPayload = $this->resolvedProductPayload();

        if ($productPayload && ! empty($productPayload->id)) {
            return (bool) $this->user()?->shop?->canAddThisInventory($productPayload);
        }

        if ($this->filled('product')) {
            \Log::error('Invalid product payload for storeWithVariant', [
                'product' => \Illuminate\Support\Str::limit((string) $this->input('product'), 500),
            ]);
        }

        return false;
    }

    /**
     * @throws AuthorizationException
     */
    protected function failedAuthorization()
    {
        if ($this->isPostSizeExceeded()) {
            throw new AuthorizationException(trans('responses.post_too_large'));
        }

        throw new AuthorizationException(trans('responses.denied'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user = $this->user(); // Get current user
        Request::merge([
            'shop_id' => $user->shop_id,
            'user_id' => $user->id,
        ]); // Set user_id

        if ($this->listing_type == 'auction') {
            $this->merge([
                'auctionable' => 1,
                'sale_price' => $this->base_price,
                'auction_status' => \Incevio\Package\Auction\Enums\AuctionStatusEnum::RUNNING,
            ]);
        }

        $rules = [
            'title' => 'required',
            'variants.*' => 'required',
            'sku.*' => 'required|distinct|unique:inventories,sku',
            'sale_price.*' => 'bail|required|numeric|min:0',
            'stock_quantity.*' => 'bail|required|integer',
            'offer_price.*' => 'sometimes|nullable|numeric',
            'available_from' => 'nullable|date',
            'image.*' => 'mimes:jpg,jpeg,png,gif,svg,webp',
        ];

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
        $messages = [
            'variants.*.required' => trans('validation.variants_required'),
        ];

        foreach ($this->input('sku', []) as $key => $val) {
            $messages['sku.'.$key.'.unique'] = trans('validation.sku-unique', ['attribute' => $key + 1, 'value' => $val]);
            $messages['sku.'.$key.'.distinct'] = trans('validation.sku-distinct', ['attribute' => $key + 1]);
        }

        foreach ($this->input('offer_price', []) as $key => $val) {
            $messages['offer_price.'.$key.'.numeric'] = $val.' '.trans('validation.offer_price-numeric');
        }

        return $messages;
    }

    /**
     * Resolve compact product object used by authorize / repository.
     */
    public function resolvedProductPayload(): ?object
    {
        $raw = $this->input('product');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw);

            if (! is_object($decoded)) {
                $decoded = json_decode($this->makeStringJsonCompatible($raw));
            }

            if (is_object($decoded) && ! empty($decoded->id)) {
                // Refresh name/brand from DB when payload is compact.
                $product = Product::query()->select('id', 'name', 'brand', 'shop_id')->find($decoded->id);
                if ($product) {
                    return (object) [
                        'id' => $product->id,
                        'name' => $product->name,
                        'brand' => $product->brand,
                        'shop_id' => $product->shop_id,
                    ];
                }

                return $decoded;
            }
        }

        if ($this->filled('product_id')) {
            $product = Product::query()->select('id', 'name', 'brand', 'shop_id')->find($this->input('product_id'));
            if ($product) {
                return (object) [
                    'id' => $product->id,
                    'name' => $product->name,
                    'brand' => $product->brand,
                    'shop_id' => $product->shop_id,
                ];
            }
        }

        return null;
    }

    private function isPostSizeExceeded(): bool
    {
        $contentLength = (int) $this->server('CONTENT_LENGTH', 0);
        if ($contentLength <= 0) {
            return false;
        }

        $postMax = $this->phpSizeToBytes((string) ini_get('post_max_size'));

        // Empty request body with a large Content-Length usually means PHP discarded the POST.
        return $postMax > 0
            && $contentLength > $postMax
            && empty($this->all())
            && empty($this->allFiles());
    }

    private function phpSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (float) $value,
        };
    }

    /**
     * Make string json compatible by removing any unescaped double quotes in the passed json string.
     *
     * @return string
     */
    private function makeStringJsonCompatible($string)
    {
        $regex = '/font-family: (.*?);/';

        return preg_replace_callback($regex, function ($matches) {
            $font_family = $matches[1];
            $font_family_escaped = str_replace('"', '"', $font_family);

            return "font-family: $font_family_escaped;";
        }, $string);
    }
}
