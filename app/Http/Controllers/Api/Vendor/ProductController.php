<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateProductRequest;
use App\Http\Requests\Validations\UpdateProductRequest;
use App\Http\Resources\ProductLightResource;
use App\Http\Resources\ProductResource;
use App\Models\Image;
use App\Models\Inventory;
use App\Models\Product;
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Product\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    private $product;

    private $inventory;

    public function __construct(ProductRepository $product, InventoryRepository $inventory)
    {
        parent::__construct();
        $this->product = $product;
        $this->inventory = $inventory;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter');
        $search = trim((string) $request->get('q', ''));

        if ($filter == 'trash') {
            $products = Product::mine()->onlyTrashed()
                ->with('featureImage', 'image', 'subCategories');
        } else {
            $products = Product::mine()->with('featureImage', 'image', 'subCategories');
        }

        if ($search !== '') {
            $products = $products->vendorSearch($search);
        }

        $products = $products->latest('id')->paginate();

        return ProductLightResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProductRequest $request)
    {
        $storedProduct = null;

        try {
            DB::transaction(function () use ($request, &$storedProduct) {
                $inventoryRepo = $this->inventory;
                $storedProduct = $this->product->store($request);

                if ($request->shop_id && (int) $storedProduct->shop_id !== (int) $request->shop_id) {
                    $storedProduct->forceFill(['shop_id' => $request->shop_id])->saveQuietly();
                }

                $inventoryData = [
                    'title' => $request->name,
                    'brand' => $request->brand,
                    'sku' => $request->sku,
                    'description' => $request->description,
                    'condition' => $request->input('condition', 'New'),
                    'condition_note' => $request->condition_note,
                    'stock_quantity' => $request->input('stock_quantity', 1),
                    'min_order_quantity' => $request->input('min_order_quantity', 1),
                    'sale_price' => $request->sale_price,
                    'offer_price' => $request->offer_price,
                    'offer_start' => $request->offer_start,
                    'offer_end' => $request->offer_end,
                    'free_shipping' => $request->input('free_shipping', 0),
                    'shipping_weight' => $request->filled('shipping_weight') ? $request->shipping_weight : null,
                    'active' => $request->active,
                    'available_from' => $request->input('available_from', now()->format('Y-m-d h:i a')),
                    'slug' => $request->slug,
                    'user_id' => $request->user_id,
                    'shop_id' => $request->shop_id,
                    'product_id' => $storedProduct->id,
                ];

                if ($request->filled('warehouse_id')) {
                    $inventoryData['warehouse_id'] = $request->warehouse_id;
                }

                if ($request->filled('supplier_id')) {
                    $inventoryData['supplier_id'] = $request->supplier_id;
                }

                if ($request->filled('purchase_price')) {
                    $inventoryData['purchase_price'] = $request->purchase_price;
                }

                if ($request->filled('key_features')) {
                    $inventoryData['key_features'] = $request->key_features;
                }

                foreach ([
                    'shipping_type',
                    'shipping_fixed_rate',
                    'shipping_base_fee',
                    'shipping_per_km_rate',
                ] as $field) {
                    if ($request->filled($field)) {
                        $inventoryData[$field] = $request->input($field);
                    }
                }

                // SEO meta is derived from name/description in the form request.
                $inventoryData['meta_title'] = $request->input('meta_title');
                $inventoryData['meta_description'] = $request->input('meta_description');

                // DB dimension columns are NOT NULL — default blank to 0.
                $inventoryData['length'] = $request->filled('length') ? $request->input('length') : 0;
                $inventoryData['width'] = $request->filled('width') ? $request->input('width') : 0;
                $inventoryData['height'] = $request->filled('height') ? $request->input('height') : 0;
                $inventoryData['distance_unit'] = $request->input('distance_unit', 'cm') ?: 'cm';

                // Variable product: create attributed variant inventories only
                // (default becomes parent). Do not also create a shell SKU.
                if ($request->filled('variants') && $request->filled('skus')) {
                    $skus = $request->input('skus');
                    $stockQuantities = $request->input('stock_quantities', []);
                    $salePrices = $request->input('sale_prices', []);
                    $offerPrices = $request->input('offer_prices', []);
                    $variants = $request->input('variants', []);
                    $images = $request->file('variant_images');
                    $tagLists = $request->input('tag_list');

                    $defaultKey = $request->input('default_variant');
                    if ($defaultKey === null || ! array_key_exists($defaultKey, $skus)) {
                        $defaultKey = array_key_first($skus);
                    }

                    $commonInfo = [
                        'user_id' => $request->user_id,
                        'shop_id' => $request->shop_id,
                        'title' => $request->name,
                        'product_id' => $storedProduct->id,
                        'brand' => $request->brand,
                        'condition' => $request->input('condition', 'New'),
                        'condition_note' => $request->condition_note,
                        'warehouse_id' => $request->input('warehouse_id'),
                        'supplier_id' => $request->input('supplier_id'),
                        'purchase_price' => $request->input('purchase_price'),
                        'shipping_weight' => $request->filled('shipping_weight')
                            ? $request->input('shipping_weight')
                            : null,
                        // DB columns are NOT NULL decimals — empty means 0.
                        'length' => $request->filled('length') ? $request->input('length') : 0,
                        'width' => $request->filled('width') ? $request->input('width') : 0,
                        'height' => $request->filled('height') ? $request->input('height') : 0,
                        'distance_unit' => $request->input('distance_unit', 'cm') ?: 'cm',
                        'free_shipping' => $request->input('free_shipping', 0),
                        'shipping_type' => $request->input('shipping_type'),
                        'shipping_fixed_rate' => $request->input('shipping_fixed_rate'),
                        'shipping_per_km_rate' => $request->input('shipping_per_km_rate'),
                        'shipping_base_fee' => $request->input('shipping_base_fee'),
                        'available_from' => $request->input('available_from', now()->format('Y-m-d h:i a')),
                        'active' => $request->active,
                        'min_order_quantity' => $request->input('min_order_quantity', 1),
                        'description' => $request->description,
                        'key_features' => $request->input('key_features'),
                        'meta_title' => $request->input('meta_title'),
                        'meta_description' => $request->input('meta_description'),
                    ];

                    $createVariant = function ($key, $parentId) use (
                        $skus,
                        $stockQuantities,
                        $salePrices,
                        $offerPrices,
                        $images,
                        $variants,
                        $commonInfo,
                        $request,
                        $tagLists,
                        $inventoryRepo
                    ) {
                        $data = array_merge($commonInfo, [
                            'parent_id' => $parentId,
                            'sku' => $skus[$key],
                            'stock_quantity' => $stockQuantities[$key] ?? 0,
                            'sale_price' => $salePrices[$key] ?? 0,
                            'offer_price' => ! empty($offerPrices[$key]) ? $offerPrices[$key] : null,
                            'slug' => generate_unique_listing_slug($request->input('slug').' '.$skus[$key]),
                        ]);

                        $inventory = Inventory::create($data);

                        if (! empty($variants[$key])) {
                            $inventoryRepo->setAttributes($inventory, $variants[$key]);
                        }

                        if ($tagLists) {
                            $inventory->syncTags($inventory, $tagLists);
                        }

                        if (isset($images[$key])) {
                            $inventory->saveImage($images[$key]);
                        }

                        try {
                            app(\App\Services\Inventory\StockService::class)->ensurePrimaryStock(
                                $inventory,
                                is_numeric($commonInfo['warehouse_id'] ?? null)
                                    ? (int) $commonInfo['warehouse_id']
                                    : null,
                                (int) ($data['stock_quantity'] ?? 0)
                            );
                        } catch (\InvalidArgumentException $e) {
                            // No warehouse configured yet.
                        }

                        return $inventory;
                    };

                    $parent = $createVariant($defaultKey, null);

                    foreach ($skus as $key => $sku) {
                        if ((string) $key === (string) $defaultKey) {
                            continue;
                        }
                        $createVariant($key, $parent->id);
                    }
                } else {
                    $inventoryRepo->store(new Request($inventoryData));
                }
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $this->friendlyProductError($e)], 400);
        }

        return response()->json([
            'message' => trans('api.product_created_successfully'),
            'product_id' => $storedProduct?->id,
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new ProductResource($this->product->find($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $this->product->update($request, $id);

            $inventory = \App\Models\Inventory::query()
                ->where('product_id', $id)
                ->where('shop_id', $request->shop_id)
                ->first();

            if ($inventory) {
                $inventory->fill([
                    'title' => $request->name,
                    'brand' => $request->brand,
                    'sku' => $request->sku,
                    'description' => $request->description,
                    'condition' => $request->input('condition', $inventory->condition),
                    'condition_note' => $request->condition_note,
                    'min_order_quantity' => $request->input('min_order_quantity', $inventory->min_order_quantity),
                    'sale_price' => $request->sale_price,
                    'offer_price' => $request->offer_price,
                    'offer_start' => $request->input('offer_start', $inventory->offer_start),
                    'offer_end' => $request->input('offer_end', $inventory->offer_end),
                    'free_shipping' => $request->input('free_shipping', $inventory->free_shipping),
                    'shipping_weight' => $request->input('shipping_weight', $inventory->shipping_weight),
                    'active' => $request->active,
                    'available_from' => $request->input('available_from', $inventory->available_from),
                    'slug' => $request->slug,
                    'supplier_id' => $request->input('supplier_id', $inventory->supplier_id),
                    'purchase_price' => $request->input('purchase_price', $inventory->purchase_price),
                    'key_features' => $request->input('key_features', $inventory->key_features),
                    'shipping_type' => $request->input('shipping_type', $inventory->shipping_type),
                    'shipping_fixed_rate' => $request->input('shipping_fixed_rate', $inventory->shipping_fixed_rate),
                    'shipping_base_fee' => $request->input('shipping_base_fee', $inventory->shipping_base_fee),
                    'shipping_per_km_rate' => $request->input('shipping_per_km_rate', $inventory->shipping_per_km_rate),
                    'length' => $request->input('length', $inventory->length),
                    'width' => $request->input('width', $inventory->width),
                    'height' => $request->input('height', $inventory->height),
                    'meta_title' => $request->input('meta_title'),
                    'meta_description' => $request->input('meta_description'),
                ])->save();

                if ($request->filled('warehouse_id')) {
                    $inventory->warehouse_id = $request->warehouse_id;
                    $inventory->saveQuietly();
                }

                if ($request->has('stock_quantity')) {
                    try {
                        app(\App\Services\Inventory\StockService::class)->ensurePrimaryStock(
                            $inventory,
                            $request->filled('warehouse_id') ? (int) $request->warehouse_id : null,
                            (int) $request->input('stock_quantity')
                        );
                    } catch (\InvalidArgumentException $e) {
                        $inventory->stock_quantity = (int) $request->input('stock_quantity');
                        $inventory->saveQuietly();
                    }
                }
            }

            // Delete images for app
            if ($request->input('delete_images')) {
                $models = Image::whereIn('id', $request->input('delete_images'))->get();

                foreach ($models as $model) {
                    $model->delete();
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $this->friendlyProductError($e)], 400);
        }

        return response()->json(['message' => trans('api.product_updated_successfully')], 200);
    }

    /**
     * trash product
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function trash($id)
    {
        try {
            $this->product->trash($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.product_trashed_successfully')], 200);
    }

    /**
     * restore product
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        try {
            $this->product->restore($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.product_restored_successfully')], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $this->product->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.product_deleted_successfully')], 200);
    }

    /**
     * Display the translation of a product in the specified language.
     *
     * @param  Product  $product  The product instance.
     * @param  string  $language  The language code for the translation.
     * @return \Illuminate\Http\Response
     */
    public function showTranslation(Product $product, string $language)
    {
        $product_translation = $product->translations()->where('lang', $language)->firstOrNew([
            'product_id' => $product->id,
            'lang' => $language,
        ]);

        $translation = $product_translation->translation;

        return response([
            'name' => $translation['name'] ?? null,
            'description' => $translation['description'] ?? null,
            'brand' => $translation['brand'] ?? null,
            'lang' => $language,
        ]);
    }

    /**
     * Store the translation for a product in the specified language.
     *
     * @param  Product  $product  The product for which to store the translation.
     * @param  string  $language  The language in which to store the translation.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the success of the translation storage.
     */
    public function storeTranslation(Product $product, string $language)
    {
        $product_translation = $product->translations()->where('lang', $language)->firstOrNew([
            'product_id' => $product->id,
            'lang' => $language,
        ]);

        $product_translation->translation = [
            'name' => request('name'),
            'brand' => request('brand'),
            'description' => request('description'),
        ];

        $product_translation->save();

        return response()->json(['message' => trans('api.model_translation_saved_successfully', ['model' => 'Product'])]);
    }

    /**
     * Map technical exceptions to short, user-facing API messages.
     */
    private function friendlyProductError(\Throwable $e): string
    {
        $raw = (string) $e->getMessage();
        $lower = strtolower($raw);

        if (str_contains($lower, 'duplicate') && str_contains($lower, 'sku')) {
            return trans('api.sku_already_taken', [], 'en') !== 'api.sku_already_taken'
                ? trans('api.sku_already_taken')
                : 'This SKU is already in use. Please choose a different SKU.';
        }

        if (
            str_contains($lower, 'sqlstate')
            || str_contains($lower, 'integrity constraint')
            || str_contains($lower, 'queryexception')
        ) {
            return 'Could not save the product. Please check the details and try again.';
        }

        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($raw)) ?? '');
        if ($clean === '' || strlen($clean) > 160) {
            return 'Could not save the product. Please check the details and try again.';
        }

        return $clean;
    }
}
