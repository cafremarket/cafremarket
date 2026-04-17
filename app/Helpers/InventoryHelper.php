<?php

namespace App\Helpers;

use App\Models\Manufacturer;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryHelper
{
    /**
     * Create Product
     *
     * @param  array  $product
     */
    public static function createProduct(array $data): Product
    {
        if (! Auth::check()) {
            exit(trans('responses.login_to_access'));
        }

        if ($data['origin_country']) {
            $origin_country = DB::table('countries')->select('id')
                ->where('iso_code', strtoupper($data['origin_country']))->first();
        }

        if ($data['manufacturer']) {
            $manufacturer = Manufacturer::firstOrCreate(
                ['name' => $data['manufacturer']],
                ['slug' => Str::slug($data['manufacturer'])]
            );
        }

        if (! is_array($data['category_list'])) {
            $cat_ids = explode(',', $data['category_list']);

            $data['category_list'] = DB::table('categories')->whereIn('slug', $cat_ids)->pluck('id')->toArray();
        }

        $name = $data['name'] ?? $data['title'] ?? '';

        $product = Product::where('gtin', $data['gtin'])
            ->where('gtin_type', $data['gtin_type'])->first();

        if (! $product) {      // Create the product
            $product = Product::create([
                'shop_id' => $data['shop_id'] ?? null,
                'name' => $name,
                'slug' => $data['slug'],
                'model_number' => $data['model_number'],
                'description' => $data['description'],
                'gtin' => $data['gtin'],
                'gtin_type' => $data['gtin_type'],
                'mpn' => $data['mpn'] ?? '',
                'brand' => $data['brand'] ?? '',
                'origin_country' => isset($origin_country) ? $origin_country->id : null,
                'manufacturer_id' => isset($manufacturer) ? $manufacturer->id : null,
                'min_price' => (isset($data['minimum_price']) && $data['minimum_price'] > 0) ? $data['minimum_price'] : 0,
                'max_price' => (isset($data['maximum_price']) && $data['maximum_price'] > $data['minimum_price']) ? $data['maximum_price'] : null,
                'requires_shipping' => strtoupper($data['requires_shipping']) == 'TRUE' ? 1 : 0,
                'downloadable' => (isset($data['product_type']) && $data['product_type'] == 'digital') ? true : false,
                'active' => strtoupper($data['active']) == 'TRUE' ? 1 : 0,
            ]);
        }

        // Sync categories
        if ($data['category_list']) {
            $product->categories()->sync($data['category_list']);
        }

        // Upload featured image
        if (isset($data['image_link'])) {
            $product->saveImageFromUrl($data['image_link'], 'feature');
        }

        // When multiple image links present
        if (! isset($data['image_link']) && isset($data['image_links'])) {
            $image_links = explode(',', $data['image_links']);
            if (count($image_links) > 0 && filter_var($image_links[0], FILTER_VALIDATE_URL)) {
                $product->saveImageFromUrl($image_links[0], 'feature');
            }
        }

        // Sync tags
        if ($data['tags']) {
            $product->syncTags($product, explode(',', $data['tags']));
        }

        return $product;
    }
}
