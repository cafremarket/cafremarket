<?php

namespace App\Services\Cache;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class CatalogCache
{
    public const VERSION_CATALOG = 'cache:ver:catalog';

    public const VERSION_SHOPS = 'cache:ver:shops';

    public const VERSION_VENDOR = 'cache:ver:vendor:';

    public static function rememberJson(string $key, callable $callback, ?int $ttl = null, string $bucket = 'catalog'): Response
    {
        if (! config('performance.api_cache')) {
            return self::toResponse($callback());
        }

        $fullKey = self::versionedKey($bucket, $key);
        $seconds = $ttl ?? (int) config('performance.ttl.'.$bucket, 180);

        $payload = AppCache::remember($fullKey, $seconds, function () use ($callback) {
            return self::toArray($callback());
        });

        $status = (int) ($payload['_status'] ?? 200);
        unset($payload['_status']);

        return response()->json($payload, $status);
    }

    public static function bumpCatalog(?int $shopId = null): void
    {
        AppCache::increment(self::VERSION_CATALOG);
        AppCache::increment(self::VERSION_SHOPS);

        if ($shopId) {
            AppCache::increment(self::VERSION_VENDOR.$shopId);
        }
    }

    public static function bumpShops(): void
    {
        AppCache::increment(self::VERSION_SHOPS);
        AppCache::increment(self::VERSION_CATALOG);
    }

    public static function bumpVendor(int $shopId): void
    {
        AppCache::increment(self::VERSION_VENDOR.$shopId);
    }

    public static function versionedKey(string $bucket, string $key): string
    {
        $version = match ($bucket) {
            'shops', 'geo' => AppCache::version(self::VERSION_SHOPS),
            'vendor_stats' => AppCache::version(self::VERSION_VENDOR.(int) explode(':', $key, 2)[0]),
            default => AppCache::version(self::VERSION_CATALOG),
        };

        $suffix = '';
        if (function_exists('hyperlocal_location_cache_suffix') && in_array($bucket, ['catalog', 'listing', 'geo'], true)) {
            $suffix = hyperlocal_location_cache_suffix();
        }

        return 'api:v2:'.$bucket.':v'.$version.':'.$key.$suffix;
    }

    public static function geoKey(float $lat, float $lng): string
    {
        return round($lat, 2).':'.round($lng, 2);
    }

    private static function toArray(mixed $result): array
    {
        if ($result instanceof JsonResponse) {
            $data = $result->getData(true);
            if (! is_array($data)) {
                $data = ['data' => $data];
            }
            $data['_status'] = $result->getStatusCode();

            return $data;
        }

        if ($result instanceof JsonResource) {
            $response = $result->response();
            $data = $response->getData(true);
            if (! is_array($data)) {
                $data = ['data' => $data];
            }
            $data['_status'] = $response->getStatusCode();

            return $data;
        }

        if ($result instanceof Response) {
            $content = json_decode($result->getContent(), true) ?: [];
            $content['_status'] = $result->getStatusCode();

            return $content;
        }

        if (is_array($result)) {
            $result['_status'] = 200;

            return $result;
        }

        return [
            'data' => $result,
            '_status' => 200,
        ];
    }

    private static function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof JsonResource) {
            return $result->response();
        }

        return response()->json($result);
    }
}
