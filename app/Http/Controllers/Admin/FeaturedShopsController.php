<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionAccessRequest;
use App\Models\Shop;
use App\Services\Cache\CatalogCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class FeaturedShopsController extends Controller
{
    /**
     * Homepage Featured Stores settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PromotionAccessRequest $request)
    {
        $shops = Shop::active()->orderBy('name')->pluck('name', 'id');
        $selected = get_from_option_table('featured_shops', []);
        $selected = is_array($selected) ? array_values(array_unique(array_filter(array_map('intval', $selected)))) : [];

        return view('admin.featured_shops.index', compact('shops', 'selected'));
    }

    /**
     * Update homepage Featured Stores.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(PromotionAccessRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'featured' => 'nullable|array',
            'featured.*' => 'integer|exists:shops,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $request->input('featured', [])))));

        update_or_create_option_table_record('featured_shops', $ids);
        forget_option_table_cache('featured_shops');
        Cache::forget('featured_shops');
        CatalogCache::bumpCatalog();

        return back()->with('success', trans('messages.updated', ['model' => trans('app.featured_shops')]));
    }
}
