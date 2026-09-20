<?php

namespace App\Repositories\Category;

use App\Models\Category;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Categories are fully platform/admin-managed — no per-shop scoping here.
 */
class EloquentCategory extends EloquentRepository implements BaseRepository, CategoryRepository
{
    protected $model;

    public function __construct(Category $category)
    {
        $this->model = $category;
    }

    public function all()
    {
        return $this->model->with('featureImage', 'coverImage')
            ->withCount('subCategories')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function trashOnly()
    {
        return $this->model->onlyTrashed()->get();
    }

    public function find($id)
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function findTrash($id)
    {
        return $this->model->onlyTrashed()->findOrFail($id);
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
        $categories = $this->model->withTrashed()->whereIn('id', $ids)->get();

        foreach ($categories as $category) {
            $category->flushImages();
        }

        $result = $this->model->withTrashed()->whereIn('id', $ids)->forceDelete();

        $this->clear_cache($result);

        return $result;
    }

    public function emptyTrash()
    {
        $categories = $this->model->onlyTrashed()->get();

        foreach ($categories as $category) {
            $category->flushImages();
        }

        $result = $this->model->onlyTrashed()->forceDelete();

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
