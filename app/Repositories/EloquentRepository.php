<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

abstract class EloquentRepository
{
    /** Shared between shops: readable by every merchant, changeable only by the owning shop. */
    protected const SHARED_CATALOG_MODELS = [\App\Models\Product::class, \App\Models\Manufacturer::class];

    /** @var array<string, bool> */
    private static $tablesWithShopId = [];

    /**
     * Base query limited to the merchant's own shop for merchant panel users (platform staff and
     * non-panel callers are unrestricted). Without this, ids in URLs reach other shops' records.
     */
    protected function scopedQuery(bool $forWrite = false)
    {
        $query = $this->model->newQuery();
        $user = Auth::user();

        if (! $user instanceof \App\Models\User || $user->isFromPlatform()) {
            return $query;
        }

        if (! $forWrite && in_array(get_class($this->model), self::SHARED_CATALOG_MODELS, true)) {
            return $query;
        }

        $table = $this->model->getTable();
        self::$tablesWithShopId[$table] ??= Schema::hasColumn($table, 'shop_id');

        if (self::$tablesWithShopId[$table]) {
            $column = $this->model->qualifyColumn('shop_id');
            $shopId = (int) $user->merchantId();

            // Platform-wide rows (shop_id NULL) are readable, never writable, by merchants.
            $forWrite
                ? $query->where($column, $shopId)
                : $query->where(fn ($q) => $q->where($column, $shopId)->orWhereNull($column));
        }

        return $query;
    }

    public function all()
    {
        return $this->model->get();
    }

    public function trashOnly()
    {
        return $this->model->onlyTrashed()->get();
    }

    public function find($id)
    {
        return $this->scopedQuery()->findOrFail($id);
    }

    public function findTrash($id)
    {
        return $this->scopedQuery()->onlyTrashed()->findOrFail($id);
    }

    public function findBy($filed, $value)
    {
        return $this->model->where($filed, $value)->first();
    }

    public function recent($limit)
    {
        return $this->model->take($limit)->get();
    }

    public function store(Request $request)
    {
        $model = $this->model->create($request->all());

        // Can have multiple images
        if ($request->hasFile('images')) {
            foreach ($request->images as $type => $file) {
                $model->saveImage($file, $type);
            }
        }

        // When got a single image
        if ($request->hasFile('image')) {
            $model->saveImage($request->image);
        }

        return $model;
    }

    public function update(Request $request, $model)
    {
        $model = is_numeric($model) ? $this->scopedQuery(true)->findOrFail($model) : $model;

        if ($request->hasFile('digital_file')) {
            $model->flushAttachments();
            $model->saveAttachments($request->file('digital_file'));
        }

        $model->update($request->all());

        if ($request->input('delete_image')) {
            if (is_array($request->delete_image)) {
                foreach ($request->delete_image as $type => $value) {
                    $model->deleteImageTypeOf($type);
                }
            } else {
                $model->deleteImage();
            }
        }

        // Can have multiple images
        if ($request->hasFile('images')) {
            foreach ($request->images as $type => $file) {
                $model->updateImage($file, $type);
            }
        }

        // When got a single image
        if ($request->hasFile('image')) {
            $model->updateImage($request->image);
        }

        return $model;
    }

    public function trash($id)
    {
        return $this->scopedQuery(true)->findOrFail($id)->delete();
    }

    public function restore($id)
    {
        return $this->scopedQuery(true)->onlyTrashed()->findOrFail($id)->restore();
    }

    public function destroy($id)
    {
        $model = $this->scopedQuery(true)->onlyTrashed()->findOrFail($id);

        // $model->flushImages();

        return $model->forceDelete();
    }

    public function massTrash($ids)
    {
        return $this->scopedQuery(true)->whereIn($this->model->qualifyColumn('id'), $ids)->delete();
    }

    public function massRestore($ids)
    {
        return $this->scopedQuery(true)->onlyTrashed()->whereIn($this->model->qualifyColumn('id'), $ids)->restore();
    }

    public function massDestroy($ids)
    {
        return $this->scopedQuery(true)->withTrashed()->whereIn($this->model->qualifyColumn('id'), $ids)->forceDelete();
    }

    public function emptyTrash()
    {
        // Scoped: a merchant empties only their own shop's trash.
        return $this->scopedQuery(true)->onlyTrashed()->forceDelete();
    }

    public function saveAdrress(array $address, $model)
    {
        $model->addresses()->create($address);
    }
}
