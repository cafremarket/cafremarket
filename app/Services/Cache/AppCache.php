<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Redis-first cache helper. Failures fall through to the callback so a down
 * Redis node never takes the API offline.
 */
class AppCache
{
    public static function remember(string $key, int $ttl, callable $callback)
    {
        if ($ttl <= 0) {
            return $callback();
        }

        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (Throwable $e) {
            Log::warning('AppCache remember failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    public static function forget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (Throwable $e) {
            Log::warning('AppCache forget failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function increment(string $key, int $amount = 1): int
    {
        try {
            Cache::add($key, 1);

            return (int) Cache::increment($key, $amount);
        } catch (Throwable $e) {
            Log::warning('AppCache increment failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    public static function version(string $key): int
    {
        try {
            return (int) Cache::get($key, 1);
        } catch (Throwable $e) {
            return 1;
        }
    }
}
