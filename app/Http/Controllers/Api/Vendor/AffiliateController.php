<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Http\Resources\InventoryLightResource;
use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\Inventory;
use Illuminate\Http\Request;

/**
 * Vendor-app affiliate settings: default rate + per-product commission / enable-disable.
 * Affiliate marketer panel remains web-only.
 */
class AffiliateController extends Controller
{
    use ResolvesVendorShop;

    public function index()
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $config = Config::findOrFail($this->merchantShopId());
        $shopId = (int) $config->shop_id;
        $defaultRate = (float) ($config->default_affiliate_commission_percentage ?? 0);

        $enabledCount = Inventory::query()
            ->where('shop_id', $shopId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('affiliate_enabled')->orWhere('affiliate_enabled', 1);
            })
            ->count();

        $disabledCount = Inventory::query()
            ->where('shop_id', $shopId)
            ->whereNull('deleted_at')
            ->where('affiliate_enabled', 0)
            ->count();

        return response()->json([
            'data' => [
                'enabled' => true,
                'panel' => 'web_only',
                'default_commission_percentage' => $config->default_affiliate_commission_percentage,
                'default_affiliate_commission_percentage' => $config->default_affiliate_commission_percentage,
                'products_enabled' => $enabledCount,
                'products_disabled' => $disabledCount,
                'products_with_custom_commission' => Inventory::query()
                    ->where('shop_id', $shopId)
                    ->whereNull('deleted_at')
                    ->whereNotNull('affiliate_commission_percentage')
                    ->count(),
                'help' => [
                    'default_rate' => 'Shop-wide default when a product has no custom rate.',
                    'per_product' => 'Set commission % and enable/disable per inventory.',
                ],
            ],
        ]);
    }

    /**
     * Paginated product affiliate settings for the vendor app list screen.
     */
    public function products(Request $request)
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $shopId = $this->merchantShopId();
        $query = Inventory::query()
            ->where('shop_id', $shopId)
            ->whereNull('deleted_at')
            ->with('image:path,imageable_id,imageable_type')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        if ($request->filled('affiliate_enabled')) {
            $enabled = filter_var($request->input('affiliate_enabled'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($enabled === true) {
                $query->where(function ($q) {
                    $q->whereNull('affiliate_enabled')->orWhere('affiliate_enabled', 1);
                });
            } elseif ($enabled === false) {
                $query->where('affiliate_enabled', 0);
            }
        }

        $perPage = (int) $request->input('per_page', config('mobile_app.view_listing_per_page', 8));
        $perPage = max(1, min(50, $perPage));

        $inventories = $query->paginate($perPage);

        return InventoryLightResource::collection($inventories)->additional([
            'meta' => [
                'default_commission_percentage' => optional(Config::find($shopId))->default_affiliate_commission_percentage,
            ],
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $config = Config::findOrFail($this->merchantShopId());

        $request->validate([
            'default_affiliate_commission_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($request->has('default_affiliate_commission_percentage')) {
            $config->default_affiliate_commission_percentage = $request->input('default_affiliate_commission_percentage');
            $config->save();
            clearShopConfigCache($config->shop_id);
        }

        return response()->json([
            'message' => trans('api.config_updated_successfully'),
            'data' => [
                'default_commission_percentage' => $config->default_affiliate_commission_percentage,
                'default_affiliate_commission_percentage' => $config->default_affiliate_commission_percentage,
            ],
        ]);
    }

    /**
     * Update affiliate settings for a single inventory (commission + enable/disable).
     */
    public function updateInventory(Request $request, int $inventoryId)
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $request->validate([
            'affiliate_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'affiliate_enabled' => 'nullable|boolean',
        ]);

        $inventory = Inventory::query()
            ->where('shop_id', $this->merchantShopId())
            ->whereKey($inventoryId)
            ->firstOrFail();

        if ($request->exists('affiliate_commission_percentage')) {
            $value = $request->input('affiliate_commission_percentage');
            $inventory->affiliate_commission_percentage = ($value === '' || $value === null)
                ? null
                : $value;
        }

        if ($request->has('affiliate_enabled')) {
            $inventory->affiliate_enabled = $request->boolean('affiliate_enabled');
        }

        $inventory->save();

        return response()->json([
            'message' => trans('api.product_updated_successfully'),
            'data' => $this->inventoryAffiliatePayload($inventory),
        ]);
    }

    /**
     * Quick toggle affiliate marketing on/off for one product.
     */
    public function toggleInventory(Request $request, int $inventoryId)
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $inventory = Inventory::query()
            ->where('shop_id', $this->merchantShopId())
            ->whereKey($inventoryId)
            ->firstOrFail();

        $inventory->affiliate_enabled = ! $inventory->isAffiliateEnabled();
        $inventory->save();

        return response()->json([
            'message' => trans('api.item_updated_successfully'),
            'data' => $this->inventoryAffiliatePayload($inventory),
        ]);
    }

    /**
     * Bulk enable/disable or set commission for multiple inventories.
     */
    public function bulkUpdate(Request $request)
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $request->validate([
            'inventory_ids' => 'required|array|min:1',
            'inventory_ids.*' => 'integer',
            'affiliate_enabled' => 'nullable|boolean',
            'affiliate_commission_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if (! $request->has('affiliate_enabled') && ! $request->exists('affiliate_commission_percentage')) {
            return response()->json([
                'message' => trans('api.affiliate_settings_required'),
            ], 422);
        }

        $shopId = $this->merchantShopId();
        $ids = collect($request->input('inventory_ids'))->map(fn ($id) => (int) $id)->unique()->values();

        $inventories = Inventory::query()
            ->where('shop_id', $shopId)
            ->whereIn('id', $ids)
            ->get();

        foreach ($inventories as $inventory) {
            if ($request->has('affiliate_enabled')) {
                $inventory->affiliate_enabled = $request->boolean('affiliate_enabled');
            }

            if ($request->exists('affiliate_commission_percentage')) {
                $value = $request->input('affiliate_commission_percentage');
                $inventory->affiliate_commission_percentage = ($value === '' || $value === null)
                    ? null
                    : $value;
            }

            $inventory->save();
        }

        return response()->json([
            'message' => trans('api.data_updated_successfully'),
            'data' => [
                'updated' => $inventories->count(),
                'items' => $inventories->map(fn (Inventory $inv) => $this->inventoryAffiliatePayload($inv))->values(),
            ],
        ]);
    }

    protected function inventoryAffiliatePayload(Inventory $inventory): array
    {
        return [
            'id' => $inventory->id,
            'title' => $inventory->title,
            'sku' => $inventory->sku,
            'affiliate_enabled' => $inventory->isAffiliateEnabled(),
            'affiliate_commission_percentage' => $inventory->affiliate_commission_percentage,
            'effective_commission_percentage' => $inventory->affiliates_percentage,
            'effective_affiliate_commission_percentage' => $inventory->affiliates_percentage,
        ];
    }
}
