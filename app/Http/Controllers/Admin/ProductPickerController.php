<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionAccessRequest;
use App\Models\Inventory;
use App\Models\Shop;
use Illuminate\Http\Request;

class ProductPickerController extends Controller
{
    /**
     * Approved stores for the product picker.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function shops(PromotionAccessRequest $request)
    {
        $shops = Shop::approved()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($shop) {
                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                ];
            })
            ->values();

        return response()->json(['data' => $shops]);
    }

    /**
     * Listings for a store — same visibility rules as merchant Active Products
     * (active flag), without storefront-only filters that hide admin-visible items.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(PromotionAccessRequest $request)
    {
        $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
            'q' => 'nullable|string|max:120',
        ]);

        $shopId = (int) $request->shop_id;
        $term = trim((string) $request->get('q', ''));

        $shop = Shop::query()->find($shopId);

        // Match merchant stock list: main listings only, active = on.
        // Do NOT filter by available_from / expiry here — those are storefront
        // rules and hide products that sellers still see as Active.
        $query = Inventory::query()
            ->with([
                'product:id,name',
                'shop:id,name',
                'image:path,imageable_id,imageable_type',
            ])
            ->where('shop_id', $shopId)
            ->where('active', Inventory::ACTIVE)
            ->whereNull('parent_id')
            ->orderBy('title');

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'LIKE', $like)
                    ->orWhere('sku', 'LIKE', $like)
                    ->orWhereHas('product', function ($pq) use ($like) {
                        $pq->where('name', 'LIKE', $like);
                    });
            });
        }

        $rows = $query->limit(300)->get();

        // If nothing matched (e.g. only variant rows / edge cases), fall back
        // to any active inventory for the shop so admin can still pick.
        if ($rows->isEmpty()) {
            $fallback = Inventory::query()
                ->with([
                    'product:id,name',
                    'shop:id,name',
                    'image:path,imageable_id,imageable_type',
                ])
                ->where('shop_id', $shopId)
                ->where('active', Inventory::ACTIVE)
                ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                ->orderBy('title')
                ->limit(300)
                ->get();
            $rows = $fallback;
        }

        $seen = [];
        $data = [];
        foreach ($rows as $item) {
            $key = (string) ($item->product_id ?: $item->id);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $data[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'shop_id' => (int) $item->shop_id,
                'shop' => $item->shop->name ?? ($shop->name ?? ''),
                'title' => $item->product->name ?? $item->title,
                'sku' => $item->sku,
                'stock' => (int) $item->stock_quantity,
                'price' => get_formated_currency($item->current_sale_price()),
                'image' => get_inventory_img_src($item, 'tiny'),
            ];
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'shop_id' => $shopId,
                'shop_name' => $shop->name ?? '',
                'count' => count($data),
            ],
        ]);
    }
}
