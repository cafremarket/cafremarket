<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Services\Cache\CatalogCache;
use Symfony\Component\HttpFoundation\Response;

trait CachesApiResponses
{
    protected function rememberApi(string $key, callable $callback, ?int $ttl = null, string $bucket = 'catalog'): Response
    {
        return CatalogCache::rememberJson($key, $callback, $ttl, $bucket);
    }
}
