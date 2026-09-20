<?php

namespace App\Repositories\SubCategory;

use App\Models\SubCategory;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EloquentSubCategory extends EloquentRepository implements BaseRepository, SubCategoryRepository
{
    protected $model;

    public function __construct(SubCategory $subCategory)
    {
        $this->model = $subCategory;
    }

    public function all()
    {
        return $this->model->with(
            'category:id,name,deleted_at',
            'featureImage',
            'coverImage'
        )->withCount('products', 'listings')->get();
    }

    public function trashOnly()
    {
        return $this->model->with('category:id,name,deleted_at')->onlyTrashed()->get();
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
        $subCategory = parent::findTrash($id);

        $subCategory->flushImages();

        $result = $subCategory->forceDelete();

        $this->clear_cache($result);

        return $result;
    }

    public function massDestroy($ids)
    {
        $subCategories = $this->model->withTrashed()->whereIn('id', $ids)->get();

        foreach ($subCategories as $subCategory) {
            $subCategory->flushImages();
        }

        $result = parent::massDestroy($ids);

        $this->clear_cache($result);

        return $result;
    }

    public function emptyTrash()
    {
        $subCategories = $this->model->onlyTrashed()->get();

        foreach ($subCategories as $subCategory) {
            $subCategory->flushImages();
        }

        $result = parent::emptyTrash();

        $this->clear_cache($result);

        return $result;
    }

    private function clear_cache($result = false)
    {
        if ($result) {
            Cache::forget('all_categories');
            Cache::forget('category_list_for_form');
        }

        return $result;
    }
}
