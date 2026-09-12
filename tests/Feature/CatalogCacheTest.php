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

    public function test_falls_back_to_local_drivers_when_redis_is_unreachable()
    {
        config([
            'cache.default' => 'redis',
            'session.driver' => 'redis',
            'queue.default' => 'redis',
            'performance.redis_optional' => true,
            'database.redis.cache.host' => '127.0.0.1',
            'database.redis.cache.port' => 1,
        ]);

        \App\Services\Cache\RedisAvailability::ensure();

        $this->assertFalse(config('performance.redis_available'));
        $this->assertNotSame('redis', config('cache.default'));
        $this->assertNotSame('redis', config('session.driver'));
        $this->assertSame('sync', config('queue.default'));
        $this->assertSame('ok', \Illuminate\Support\Facades\Cache::remember('redis-fallback-key', 60, fn () => 'ok'));
    }

    public function test_keeps_redis_when_optional_is_disabled()
    {
        config([
            'cache.default' => 'redis',
            'session.driver' => 'redis',
            'performance.redis_optional' => false,
            'database.redis.cache.host' => '127.0.0.1',
            'database.redis.cache.port' => 1,
        ]);

        \App\Services\Cache\RedisAvailability::ensure();

        $this->assertFalse(config('performance.redis_available'));
        $this->assertSame('redis', config('cache.default'));
        $this->assertSame('redis', config('session.driver'));
    }
}
