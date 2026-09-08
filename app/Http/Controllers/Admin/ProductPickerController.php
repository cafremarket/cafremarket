<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionAccessRequest;
use App\Models\Inventory;
use App\Models\Shop;
use Carbon\Carbon;
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
     * Active listings for a store (one row per product — prefer main/parent SKU).
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

        $query = Inventory::query()
            ->select('inventories.*')
            ->with(['product:id,name', 'image:path,imageable_id,imageable_type'])
            ->leftJoin('products', 'products.id', '=', 'inventories.product_id')
            ->where('inventories.shop_id', $shopId)
            ->where('inventories.active', 1)
            ->whereNull('inventories.deleted_at')
            ->where(function ($q) {
                $q->whereNull('inventories.available_from')
                    ->orWhere('inventories.available_from', '<=', Carbon::now());
            })
            ->orderByRaw('CASE WHEN inventories.parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('inventories.title');

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like) {
                $q->where('inventories.title', 'LIKE', $like)
                    ->orWhere('inventories.sku', 'LIKE', $like)
                    ->orWhere('products.name', 'LIKE', $like);
            });
        }

        $rows = $query->limit(200)->get();

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
                'title' => $item->product->name ?? $item->title,
                'sku' => $item->sku,
                'price' => get_formated_currency($item->current_sale_price()),
                'image' => get_inventory_img_src($item, 'tiny'),
            ];
        }

        return response()->json(['data' => $data]);
    }
}
