<?php

namespace App\Repositories\Category;

use App\Models\Category;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class EloquentCategory extends EloquentRepository implements BaseRepository, CategoryRepository
{
    protected $model;

    public function __construct(Category $category)
    {
        $this->model = $category;
    }

    public function all()
    {
        $query = $this->model->with(
            'subGroup:id,name,category_group_id,deleted_at',
            'subGroup.group:id,name,deleted_at',
            'featureImage',
            'coverImage'
        )->withCount('products', 'listings');

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        return $query->get();
    }

    public function trashOnly()
    {
        $query = $this->model->with(
            'subGroup:id,name,category_group_id,deleted_at',
            'subGroup.group:id,name,deleted_at'
        )->onlyTrashed();

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        return $query->get();
    }

    public function find($id)
    {
        $query = $this->model->newQuery();

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        return $query->findOrFail($id);
    }

    public function findTrash($id)
    {
        $query = $this->model->onlyTrashed();

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        return $query->findOrFail($id);
    }

    public function store(Request $request)
    {
        $result = parent::store($request);

        $this->clear_cache($result);

        return $result;
    }

    public function update(Request $request, $id)
    {
        $result = parent::update($request, $id);

        $this->clear_cache($result);

        return $result;
    }

    public function destroy($id)
    {
        $category = $this->findTrash($id);

        $category->flushImages();

        $result = $category->forceDelete();

        $this->clear_cache($result);

        return $result;
    }

    public function massDestroy($ids)
    {
        $query = $this->model->withTrashed()->whereIn('id', $ids);

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        $categories = $query->get();

        foreach ($categories as $category) {
            $category->flushImages();
        }

        $result = $query->forceDelete();

        $this->clear_cache($result);

        return $result;
    }

    public function emptyTrash()
    {
        $query = $this->model->onlyTrashed();

        if (! Auth::user()->isFromPlatform()) {
            $query->mine();
        }

        $categories = $query->get();

        foreach ($categories as $category) {
            $category->flushImages();
        }

        $result = $query->forceDelete();

        $this->clear_cache($result);

        return $result;
    }

    private function clear_cache($result = false)
    {
        if ($result) {
            Cache::forget('all_categories');
            Cache::forget('category_list_for_form');
            Cache::forget('category_list_for_form_shop_'.(Auth::user()?->merchantId() ?? 'platform'));
        }

        return $result;
    }
}
