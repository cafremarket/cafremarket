<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionAccessRequest;
use App\Models\Category;
use App\Services\Cache\CatalogCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class FeaturedCategoriesController extends Controller
{
    /**
     * Homepage Featured Categories settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PromotionAccessRequest $request)
    {
        $categories = Category::active()->orderBy('name')->pluck('name', 'id');
        $selected = Category::active()->featured()->pluck('id')->toArray();

        return view('admin.featured_categories.index', compact('categories', 'selected'));
    }

    /**
     * Update homepage Featured Categories.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(PromotionAccessRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'featured' => 'nullable|array',
            'featured.*' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $request->input('featured', [])))));

        Category::query()->update(['featured' => false]);
        Category::whereIn('id', $ids)->update(['featured' => true]);

        Cache::forget('all_categories');
        Cache::forget('all_categories_v2');
        Cache::forget('featured_categories_web');
        CatalogCache::bumpCatalog();

        return back()->with('success', trans('messages.updated', ['model' => trans('app.featured_categories')]));
    }
}
