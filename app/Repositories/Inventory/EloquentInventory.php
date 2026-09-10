<?php

namespace App\Repositories\Inventory;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Image;
use App\Models\Inventory;
use App\Models\Product;
use App\Repositories\BaseRepository;
use App\Repositories\Concerns\ScopesMerchantShop;
use App\Repositories\EloquentRepository;
use App\Services\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EloquentInventory extends EloquentRepository implements BaseRepository, InventoryRepository
{
    use ScopesMerchantShop;

    protected $model;

    public function __construct(Inventory $inventory)
    {
        $this->model = $inventory;
    }

    public function all($status = null)
    {
        $inventory = $this->model->with('product', 'image');

        switch ($status) {
            case 'active':
                $inventory = $inventory->where('active', Inventory::ACTIVE)
                    ->where('stock_quantity', '>', 0);
                break;

            case 'inactive':
                $inventory = $inventory->where(function ($query) {
                    $query->where('active', '!=', Inventory::ACTIVE)
                        ->orWhereNull('active');
                });
                break;

            case 'outOfStock':
                $inventory = $inventory->stockOut();
                break;
        }

        if (! Auth::user()->isFromPlatform()) {
            return $inventory->mine()->get();
        }

        return $inventory->get();
    }

    public function trashOnly()
    {
        if (! Auth::user()->isFromPlatform()) {
            return $this->model->mine()->onlyTrashed()->with('product', 'image')->get();
        }

        return $this->model->onlyTrashed()->with('product', 'image')->get();
    }

    public function checkInventoryExist($productId)
    {
        return $this->model->mine()->where('product_id', $productId)->first();
    }

    public function store(Request $request)
    {
        $inventory = parent::store($request);

        $this->setAttributes($inventory, $request->input('variants'));

        if (is_incevio_package_loaded('packaging') && $request->input('packaging_list')) {
            $inventory->packagings()->sync($request->input('packaging_list'));
        }

        if ($request->input('tag_list')) {
            $inventory->syncTags($inventory, $request->input('tag_list'));
        }

        if ($request->hasFile('image')) {
            $inventory->saveImage($request->file('image'));
        }

        if ($request->hasFile('digital_file')) {
            $inventory->saveAttachments($request->file('digital_file'));
        }

        if (is_incevio_package_loaded('wholesale') && $request->has('wholesale')) {
            $wholesale_data = array_filter($request->wholesale, function ($value) {
                return ! is_null($value['min_quantity']) && ! is_null($value['wholesale_price']);
            });

            if (! empty($wholesale_data)) {
                $inventory->wholeSalePrices()->createMany($wholesale_data);
            }
        }

        if (is_incevio_package_loaded('buyerGroup') && $request->input('buyer_group')) {
            foreach ($request->input('buyer_group') as $buyer_group_id => $data) {
                $inventory->buyerGroupDetails()->updateOrCreate(['buyer_group_id' => $buyer_group_id], $data);
            }
        }

        $this->syncProductShopId($inventory);

        $this->syncImagesFromProductIfEmpty($inventory);

        $this->syncStockFromRequest($inventory, $request);

        return $inventory;
    }

    /**
     * Copy product images onto a new inventory listing when none were uploaded directly.
     */
    private function syncImagesFromProductIfEmpty(Inventory $inventory): void
    {
        if (! $inventory->product_id || $inventory->image()->exists()) {
            return;
        }

        $product = Product::with(['featureImage', 'image', 'images'])->find($inventory->product_id);
        if (! $product) {
            return;
        }

        $sourceImages = collect();
        if ($product->featureImage) {
            $sourceImages->push($product->featureImage);
        }
        if ($product->image && $sourceImages->where('id', $product->image->id)->isEmpty()) {
            $sourceImages->push($product->image);
        }
        foreach ($product->images as $img) {
            if ($sourceImages->where('id', $img->id)->isEmpty()) {
                $sourceImages->push($img);
            }
        }

        foreach ($sourceImages->values() as $order => $img) {
            Image::create([
                'name' => $img->name,
                'type' => $img->type,
                'path' => $img->path,
                'extension' => $img->extension,
                'size' => $img->size,
                'order' => $order,
                'featured' => $img->featured,
                'imageable_id' => $inventory->id,
                'imageable_type' => Inventory::class,
            ]);
        }
    }

    /**
     * Keep product.shop_id aligned with inventory when the catalog row was saved without it.
     */
    private function syncProductShopId(Inventory $inventory): void
    {
        if (! $inventory->product_id || ! $inventory->shop_id) {
            return;
        }

        Product::query()
            ->whereKey($inventory->product_id)
            ->where(function ($query) use ($inventory) {
                $query->whereNull('shop_id')
                    ->orWhere('shop_id', '!=', $inventory->shop_id);
            })
            ->update(['shop_id' => $inventory->shop_id]);
    }

    public function storeWithVariant(Request $request)
    {
        $product = json_decode($request->input('product'));

        if (! is_object($product) || empty($product->id)) {
            throw new \InvalidArgumentException(trans('responses.invalid_data'));
        }

        // Common information
        $commonInfo = [
            'user_id' => $request->user()->id, // Set user_id
            'shop_id' => $request->user()->merchantId(), // Set shop_id
            'title' => $request->has('title') ? $request->input('title') : ($product->name ?? null),
            'product_id' => $product->id,
            'brand' => $product->brand ?? null,
            'warehouse_id' => $request->input('warehouse_id'),
            'supplier_id' => $request->input('supplier_id'),
            'shipping_width' => $request->input('shipping_width'),
            'shipping_height' => $request->input('shipping_height'),
            'shipping_depth' => $request->input('shipping_depth'),
            'shipping_weight' => $request->input('shipping_weight'),
            'available_from' => $request->input('available_from'),
            'active' => $request->input('active'),
            'tax_id' => $request->input('tax_id'),
            'min_order_quantity' => $request->input('min_order_quantity'),
            'alert_quantity' => $request->input('alert_quantity'),
            'description' => $request->input('description'),
            'condition_note' => $request->input('condition_note'),
            'key_features' => $request->input('key_features'),
            'linked_items' => $request->input('linked_items'),
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
        ];

        // Arrays
        $skus = $request->input('sku', []);
        $conditions = $request->input('condition', []);
        $stock_quantities = $request->input('stock_quantity', []);
        $purchase_prices = $request->input('purchase_price', []);
        $sale_prices = $request->input('sale_price', []);
        $offer_prices = $request->input('offer_price', []);
        $images = $request->file('image');

        if (! is_array($skus) || count($skus) === 0) {
            throw new \InvalidArgumentException(trans('validation.variants_required'));
        }

        // Relations
        $tag_lists = $request->input('tag_list');
        $variants = $request->input('variants', []);
        if (is_incevio_package_loaded('packaging')) {
            $packaging_lists = $request->input('packaging_list');
        }

        $isFirst = true;
        $parent_id = null;
        $defaultKey = $request->input('default_variant');
        if ($defaultKey === null || ! array_key_exists($defaultKey, $skus)) {
            $defaultKey = array_key_first($skus);
        }

        // Create the default variant first so it becomes the parent listing SKU.
        $orderedKeys = array_keys($skus);
        usort($orderedKeys, function ($a, $b) use ($defaultKey) {
            if ((string) $a === (string) $defaultKey) {
                return -1;
            }
            if ((string) $b === (string) $defaultKey) {
                return 1;
            }

            return $a <=> $b;
        });

        // Preparing data and insert records.
        $dynamicInfo = [];
        foreach ($orderedKeys as $key) {
            $sku = $skus[$key];
            $dynamicInfo = [
                'sku' => $sku,
                'stock_quantity' => $stock_quantities[$key] ?? 0,
                'purchase_price' => $purchase_prices[$key] ?? null,
                'sale_price' => $sale_prices[$key] ?? 0,
                'offer_price' => ! empty($offer_prices[$key]) ? $offer_prices[$key] : null,
                'slug' => Str::slug($request->input('slug').' '.$sku, '-'),
                'parent_id' => $parent_id,
            ];

            if (config('system_settings.show_item_conditions')) {
                $dynamicInfo['condition'] = $conditions[$key] ?? null;
            }

            // Merge the common info and dynamic info to data array
            $data = array_merge($dynamicInfo, $commonInfo);

            // Insert the record
            $inventory = Inventory::create($data);

            if ($isFirst) {
                $parent_id = $inventory->id;
                $isFirst = false;
            }

            // Sync Attributes
            if (! empty($variants[$key])) {
                $this->setAttributes($inventory, $variants[$key]);
            }

            // Sync packaging
            if (is_incevio_package_loaded('packaging') && ! empty($packaging_lists)) {
                $inventory->packagings()->sync($packaging_lists);
            }

            // Sync tags
            if ($tag_lists) {
                $inventory->syncTags($inventory, $tag_lists);
            }

            // Save Images
            if (isset($images[$key])) {
                $inventory->saveImage($images[$key]);
            }

            $this->syncStockFromRequest($inventory, $request, (int) ($stock_quantities[$key] ?? 0));
        }

        return true;
    }

    public function updateQtt(Request $request, $id)
    {
        $inventory = parent::find($id);
        $stockService = app(StockService::class);

        if ($request->filled('warehouse_stocks') && is_array($request->input('warehouse_stocks'))) {
            $stockService->syncWarehouseStocks(
                $inventory,
                $request->input('warehouse_stocks', []),
                $request->input('reorder_levels', []),
                $request->input('damaged_quantities', []),
                $request->input('notes')
            );

            return true;
        }

        if ($request->filled('warehouse_id') && $request->has('stock_quantity')) {
            $stockService->setStock(
                $inventory,
                (int) $request->input('warehouse_id'),
                (int) $request->input('stock_quantity'),
                \App\Models\StockMovement::TYPE_ADJUST,
                $request->input('notes') ?? 'Quick stock update'
            );

            return true;
        }

        $warehouseId = $stockService->resolvePrimaryWarehouseId($inventory);
        if ($warehouseId) {
            $stockService->setStock(
                $inventory,
                $warehouseId,
                (int) $request->input('stock_quantity', 0),
                \App\Models\StockMovement::TYPE_ADJUST,
                $request->input('notes') ?? 'Quick stock update'
            );

            return true;
        }

        $inventory->stock_quantity = $request->input('stock_quantity');

        return $inventory->save();
    }

    public function update(Request $request, $id)
    {
        foreach (['length', 'width', 'height'] as $dimension) {
            if ($request->exists($dimension) && ! $request->filled($dimension)) {
                $request->merge([$dimension => null]);
            }
        }

        if ($request->exists('distance_unit') && ! $request->filled('distance_unit')) {
            $request->merge(['distance_unit' => 'cm']);
        }

        $inventory = parent::update($request, $id);

        $this->setAttributes($inventory, $request->input('variants'));

        if (is_incevio_package_loaded('packaging')) {
            $inventory->packagings()->sync($request->input('packaging_list', []));
        }

        $inventory->syncTags($inventory, $request->input('tag_list', []));

        if ($request->hasFile('image') || ($request->input('delete_image') == 1)) {
            $inventory->deleteImage();
        }

        if ($request->hasFile('image')) {
            $inventory->saveImage($request->file('image'));
        }

        if (is_incevio_package_loaded('wholesale')) {
            $inventory->wholeSalePrices()->delete();

            if ($request->has('wholesale')) {
                $wholesale_data = array_filter($request->wholesale, function ($value) {
                    return ! is_null($value['min_quantity']) && ! is_null($value['wholesale_price']);
                });

                if (! empty($wholesale_data)) {
                    $inventory->wholeSalePrices()->createMany($wholesale_data);
                }
            }
        }

        if (is_incevio_package_loaded('buyerGroup') && $request->input('buyer_group')) {
            foreach ($request->input('buyer_group') as $buyer_group_id => $data) {
                $inventory->buyerGroupDetails()->updateOrCreate(['buyer_group_id' => $buyer_group_id], $data);
            }
        }

        $this->syncStockFromRequest($inventory, $request);

        return $inventory;
    }

    /**
     * Persist warehouse stock matrix / legacy flat qty into inventory_stocks.
     */
    protected function syncStockFromRequest(Inventory $inventory, Request $request, ?int $fallbackQty = null): void
    {
        $stockService = app(StockService::class);
        $warehouseStocks = $request->input('warehouse_stocks');

        if (is_array($warehouseStocks) && count($warehouseStocks)) {
            $stockService->syncWarehouseStocks(
                $inventory,
                $warehouseStocks,
                $request->input('reorder_levels', []),
                $request->input('damaged_quantities', []),
                'Inventory form sync'
            );

            return;
        }

        $warehouseIds = $request->input('warehouse_id');
        if (! is_array($warehouseIds)) {
            $warehouseIds = $warehouseIds ? [$warehouseIds] : [];
        }
        $warehouseIds = array_values(array_filter(array_map('intval', $warehouseIds)));

        $qty = $fallbackQty;
        if ($qty === null) {
            $qty = $request->has('stock_quantity')
                ? (int) $request->input('stock_quantity')
                : (int) $inventory->stock_quantity;
        }

        if (! empty($warehouseIds)) {
            $map = [];
            foreach ($warehouseIds as $index => $warehouseId) {
                $map[$warehouseId] = $index === 0 ? $qty : 0;
            }
            $stockService->syncWarehouseStocks($inventory, $map, [], [], 'Inventory warehouse sync');

            return;
        }

        try {
            $stockService->ensurePrimaryStock($inventory, null, $qty);
        } catch (\InvalidArgumentException $e) {
            // Digital / shops without warehouses — keep flat stock_quantity only.
            $inventory->stock_quantity = $qty;
            $inventory->saveQuietly();
        }
    }

    public function destroy($inventory)
    {
        if (! $inventory instanceof Inventory) {
            $inventory = parent::findTrash($inventory);
        }

        $inventory->detachTags($inventory->id, 'inventory');

        $inventory->flushImages();

        $inventory->flushAttachments();

        return $inventory->forceDelete();
    }

    public function massDestroy($ids)
    {
        $inventories = $this->model->withTrashed()->whereIn('id', $ids)->get();

        foreach ($inventories as $inventory) {
            $inventory->detachTags($inventory->id, 'inventory');
            $inventory->flushImages();
            $inventory->flushAttachments();
        }

        return parent::massDestroy($ids);
    }

    public function emptyTrash()
    {
        $inventories = $this->model->onlyTrashed()->get();

        foreach ($inventories as $inventory) {
            $inventory->detachTags($inventory->id, 'inventory');
            $inventory->flushImages();
            $inventory->flushAttachments();
        }

        return parent::emptyTrash();
    }

    // public function findProduct($id)
    // {
    //     return Product::findOrFail($id);
    // }

    /**
     * Set attribute pivot table for the product variants like color, size and more
     *
     * @param  obj  $inventory
     * @param  array  $attributes
     */
    public function setAttributes($inventory, $attributes)
    {
        $attributes = array_filter($attributes ?? []);        // remove empty elements

        $temp = [];
        foreach ($attributes as $attribute_id => $attribute_value_id) {
            $temp[$attribute_id] = ['attribute_value_id' => $attribute_value_id];
        }

        if (! empty($temp)) {
            $inventory->attributes()->sync($temp);
        }

        return true;
    }

    // public function getAttributeList(array $variants)
    // {
    //     return Attribute::find($variants)->pluck('name', 'id');
    // }

    /**
     * Check the list of attribute values and add new if need
     *
     * @param  [type] $attribute
     * @param  array  $values
     * @return array
     */
    public function confirmAttributes($attributeWithValues)
    {
        $results = [];

        foreach ($attributeWithValues as $attribute => $values) {
            foreach ($values as $value) {
                $oldValueId = AttributeValue::find($value);

                $oldValueName = AttributeValue::where('value', $value)->where('attribute_id', $attribute)->first();

                if ($oldValueId || $oldValueName) {
                    $results[$attribute][($oldValueId) ? $oldValueId->id : $oldValueName->id] = ($oldValueId) ? $oldValueId->value : $oldValueName->value;
                } else {
                    // if the value not numeric thats meaning that its new value and we need to create it
                    $newID = AttributeValue::insertGetId(['attribute_id' => $attribute, 'value' => $value]);

                    $newAttrValue = AttributeValue::find($newID);

                    $results[$attribute][$newAttrValue->id] = $newAttrValue->value;
                }
            }
        }

        return $results;
    }
}
