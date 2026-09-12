<?php

namespace App\Observers;

use App\Services\Cache\CatalogCache;

class CatalogCacheObserver
{
    public $afterCommit = true;

    public function created($model): void
    {
        CatalogCache::bumpCatalog($model->shop_id ?? null);
    }

    public function updated($model): void
    {
        CatalogCache::bumpCatalog($model->shop_id ?? null);
    }

    public function deleted($model): void
    {
        CatalogCache::bumpCatalog($model->shop_id ?? null);
    }
}
