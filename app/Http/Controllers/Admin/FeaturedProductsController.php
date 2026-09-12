<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionAccessRequest;
use App\Models\Inventory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class FeaturedProductsController extends Controller
{
    /**
     * Homepage Featured Products settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PromotionAccessRequest $request)
    {
        $ids = get_from_option_table('featured_items', []);
        $ids = is_array($ids) ? array_values(array_unique(array_filter(array_map('intval', $ids)))) : [];

        $items = [];
        if (! empty($ids)) {
            $inventories = Inventory::with(['shop:id,name', 'product:id,name'])
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            foreach ($ids as $id) {
                $item = $inventories->get($id);
                if (! $item) {
                    continue;
                }
                $name = $item->product->name ?? $item->title;
                $shop = $item->shop->name ?? '';
                $items[$id] = trim($name.($shop ? ' | '.$shop : '').' | '.$item->sku);
            }
        }

        return view('admin.featured_products.index', compact('items'));
    }

    /**
     * Update homepage Featured Products.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(PromotionAccessRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'featured' => 'nullable|array',
            'featured.*' => 'integer|exists:inventories,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $request->input('featured', [])))));

        update_or_create_option_table_record('featured_items', $ids);
        forget_option_table_cache('featured_items');
        Cache::forget('featured_items');
        \App\Services\Cache\CatalogCache::bumpCatalog();

        return back()->with('success', trans('messages.updated', ['model' => trans('app.featured_items')]));
    }
}
