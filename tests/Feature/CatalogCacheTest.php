<?php

namespace Tests\Feature;

use App\Services\Cache\AppCache;
use App\Services\Cache\CatalogCache;
use Tests\TestCase;

class CatalogCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['performance.api_cache' => true]);
    }

    public function test_remember_json_skips_callback_on_cache_hit()
    {
        $hits = 0;

        $first = CatalogCache::rememberJson('unit-test-hit', function () use (&$hits) {
            $hits++;

            return ['hello' => 'world'];
        }, 60);

        $second = CatalogCache::rememberJson('unit-test-hit', function () use (&$hits) {
            $hits++;

            return ['hello' => 'miss'];
        }, 60);

        $this->assertSame(1, $hits);
        $this->assertSame(['hello' => 'world'], $first->getData(true));
        $this->assertSame(['hello' => 'world'], $second->getData(true));
    }

    public function test_bump_catalog_invalidates_cached_payload()
    {
        CatalogCache::rememberJson('unit-test-bump', fn () => ['n' => 1], 60);
        CatalogCache::bumpCatalog();

        $hits = 0;
        $fresh = CatalogCache::rememberJson('unit-test-bump', function () use (&$hits) {
            $hits++;

            return ['n' => 2];
        }, 60);

        $this->assertSame(1, $hits);
        $this->assertSame(['n' => 2], $fresh->getData(true));
    }

    public function test_app_cache_remember_returns_callback_value()
    {
        $value = AppCache::remember('unit-app-cache', 60, fn () => 'cached');

        $this->assertSame('cached', $value);
        $this->assertSame('cached', AppCache::remember('unit-app-cache', 60, fn () => 'other'));
    }
}
