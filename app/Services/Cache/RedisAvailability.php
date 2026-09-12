<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Prefer Redis when it is reachable. If REDIS_OPTIONAL is true (default) and
 * Redis is down, switch cache/session/queue to local drivers so the app
 * continues without Redis.
 */
class RedisAvailability
{
    public static function ensure(): void
    {
        if (! self::configuredToUseRedis()) {
            config(['performance.redis_available' => false]);

            return;
        }

        if (self::isReachable()) {
            config(['performance.redis_available' => true]);

            return;
        }

        config(['performance.redis_available' => false]);

        if (! filter_var(config('performance.redis_optional', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        self::fallbackToLocal();
    }

    public static function isReachable(): bool
    {
        $host = (string) config('database.redis.cache.host', config('database.redis.default.host', '127.0.0.1'));
        $port = (int) config('database.redis.cache.port', config('database.redis.default.port', 6379));

        try {
            $errno = 0;
            $errstr = '';
            $socket = @stream_socket_client(
                sprintf('tcp://%s:%d', $host, $port),
                $errno,
                $errstr,
                0.2
            );

            if (! is_resource($socket)) {
                return false;
            }

            fclose($socket);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function fallbackToLocal(): void
    {
        $cacheFallback = app()->environment('testing') ? 'array' : 'file';
        $sessionFallback = app()->environment('testing') ? 'array' : 'file';

        if (config('cache.default') === 'redis') {
            config(['cache.default' => $cacheFallback]);
        }

        if (config('session.driver') === 'redis') {
            config(['session.driver' => $sessionFallback]);
        }

        if (config('queue.default') === 'redis') {
            config(['queue.default' => 'sync']);
        }

        config(['performance.redis_available' => false]);

        self::forgetResolvedStores();

        Log::warning('Redis is unreachable — using local file/array drivers instead.');
    }

    private static function configuredToUseRedis(): bool
    {
        return config('cache.default') === 'redis'
            || config('session.driver') === 'redis'
            || config('queue.default') === 'redis';
    }

    private static function forgetResolvedStores(): void
    {
        foreach (['cache', 'cache.store', 'redis', 'session', 'session.store', 'queue'] as $abstract) {
            if (app()->bound($abstract)) {
                app()->forgetInstance($abstract);
            }
        }
    }
}
