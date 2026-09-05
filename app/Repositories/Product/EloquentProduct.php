<?php

namespace App\Repositories\Product;

use App\Models\Product;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EloquentProduct extends EloquentRepository implements BaseRepository, ProductRepository
{
    protected $model;

    public function __construct(Product $product)
    {
        $this->model = $product;
    }

    public function all()
    {
        if (Auth::user()->isFromPlatform()) {
            return $this->model->with('categories', 'shop.logo', 'featureImage', 'image')
                ->withCount('inventories')->get();
        }

        return $this->model->mine()->with('categories', 'featureImage', 'image')
            ->withCount('inventories')->get();
    }

    public function find($id)
    {
        $query = $this->model->with([
            'inventories.shop',
            'manufacturer',
            'categories',
            'images',
            'tags',
        ]);

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        return $query->findOrFail($id);
    }

    public function trashOnly()
    {
        if (Auth::user()->isFromPlatform()) {
            return $this->model->onlyTrashed()->with('categories', 'featureImage')->get();
        }

        return $this->model->mine()->onlyTrashed()->with('categories', 'featureImage')->get();
    }

    public function store(Request $request)
    {
        $request->request->remove('video_path');

        if (! $request->filled('shop_id') && Auth::check()) {
            $shopId = Auth::user()->merchantId();

            if ($shopId) {
                $request->merge(['shop_id' => $shopId]);
            }
        }

        $product = parent::store($request);

        if ($request->has('category_list')) {
            $product->categories()->sync($request->input('category_list'));
        }

        if ($request->has('tag_list')) {
            $product->syncTags($product, $request->input('tag_list'));
        }

        $this->syncProductVideo($request, $product);

        return $product;
    }

    public function update(Request $request, $id)
    {
        $request->request->remove('video_path');

        $product = parent::update($request, $id);

        $product->categories()->sync($request->input('category_list', []));

        $product->syncTags($product, $request->input('tag_list', []));

        $this->syncProductVideo($request, $product);

        return $product;
    }

    protected function syncProductVideo(Request $request, Product $product): void
    {
        if ($request->boolean('delete_video')) {
            $product->deleteProductVideo();
        }

        if ($request->hasFile('video')) {
            $product->saveProductVideo($request->file('video'));
        }
    }

    public function destroy($product)
    {
        if (! $product instanceof Product) {
            $product = parent::findTrash($product);
        }

        $product->detachTags($product->id, 'product');

        $product->deleteProductVideo();
        $product->flushImages();

        if ($product->hasFeedbacks()) {
            $product->flushFeedbacks();
        }

        return $product->forceDelete();
    }

    public function massDestroy($ids)
    {
        $products = Product::onlyTrashed()->whereIn('id', $ids)->get();

        foreach ($products as $product) {
            $product->detachTags($product->id, 'product');

            $product->deleteProductVideo();
            $product->flushImages();

            if ($product->hasFeedbacks()) {
                $product->flushFeedbacks();
            }
        }

        return parent::massDestroy($ids);
    }

    public function emptyTrash()
    {
        $products = Product::onlyTrashed()->get();

        foreach ($products as $product) {
            $product->detachTags($product->id, 'product');

            $product->deleteProductVideo();
            $product->flushImages();

            if ($product->hasFeedbacks()) {
                $product->flushFeedbacks();
            }
        }

        return parent::emptyTrash();
    }
}
