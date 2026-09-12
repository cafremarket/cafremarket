<?php

namespace App\Services\Api;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;

class ApiRequestLog
{
    public const CHANNEL = 'api';

    /** Account/session endpoints that differ per customer and break the mobile app. */
    private const USER_SCOPED_PATHS = [
        'api/dashboard',
        'api/account',
        'api/addresses',
        'api/wallet',
        'api/orders',
        'api/carts',
        'api/checkout',
        'api/auth',
    ];

    public static function requestId(Request $request): string
    {
        $existing = $request->headers->get('X-Request-Id')
            ?: $request->attributes->get('api_request_id');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        return (string) Str::uuid();
    }

    public static function bind(Request $request, string $requestId): void
    {
        $request->headers->set('X-Request-Id', $requestId);
        $request->attributes->set('api_request_id', $requestId);
    }

    public static function context(Request $request, array $extra = []): array
    {
        $user = $request->user('api')
            ?? $request->user('vendor_api')
            ?? $request->user();

        $shopId = null;
        if ($user && method_exists($user, 'merchantId')) {
            try {
                $shopId = $user->merchantId();
            } catch (Throwable $e) {
                $shopId = null;
            }
        } elseif ($user && isset($user->shop_id)) {
            $shopId = $user->shop_id;
        }

        $guard = null;
        if ($user instanceof Customer) {
            $guard = 'customer';
        } elseif ($user) {
            $guard = 'user';
        }

        return array_filter([
            'request_id' => $request->attributes->get('api_request_id') ?: self::requestId($request),
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'query' => $request->query() ?: null,
            'ip' => $request->ip(),
            'user_id' => $user->id ?? null,
            'user_email' => $user->email ?? null,
            'guard' => $guard,
            'shop_id' => $shopId,
            'locale' => $request->header('Accept-Language'),
            'has_bearer' => $request->bearerToken() ? true : null,
            'has_buyer_location' => ($request->header('X-Buyer-Latitude') && $request->header('X-Buyer-Longitude')) ? true : null,
        ] + $extra, static function ($value) {
            return $value !== null && $value !== [];
        });
    }

    public static function debug(string $message, Request $request, array $extra = []): void
    {
        self::write('debug', $message, $request, $extra);
    }

    public static function info(string $message, Request $request, array $extra = []): void
    {
        self::write('info', $message, $request, $extra);
    }

    public static function warning(string $message, Request $request, array $extra = []): void
    {
        self::write('warning', $message, $request, $extra);
    }

    public static function error(string $message, Request $request, array $extra = []): void
    {
        self::write('error', $message, $request, $extra);
    }

    public static function exception(Throwable $exception, Request $request): void
    {
        if ($request->attributes->get('api_exception_logged')) {
            return;
        }

        $request->attributes->set('api_exception_logged', true);

        self::error('API exception', $request, [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile().':'.$exception->getLine(),
        ]);
    }

    public static function isUserScoped(Request $request): bool
    {
        $path = strtolower($request->path());

        foreach (self::USER_SCOPED_PATHS as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    public static function payloadShape(?string $json): ?array
    {
        if (! is_string($json) || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return ['body_type' => gettype($decoded)];
        }

        $data = $decoded['data'] ?? $decoded;

        if (! is_array($data)) {
            return ['data_type' => $data === null ? 'null' : gettype($data)];
        }

        if (self::isList($data)) {
            $first = $data[0] ?? null;

            return [
                'list_count' => count($data),
                'item' => is_array($first) ? self::typesOf($first) : gettype($first),
            ];
        }

        return self::typesOf($data);
    }

    private static function write(string $level, string $message, Request $request, array $extra = []): void
    {
        try {
            self::logger()->{$level}($message, self::context($request, $extra));
        } catch (Throwable $e) {
            try {
                Log::error('API log failed: '.$e->getMessage(), [
                    'original_message' => $message,
                    'path' => '/'.$request->path(),
                ]);
            } catch (Throwable $ignored) {
                // Logging must never take down an API request.
            }
        }
    }

    private static function logger(): LoggerInterface
    {
        try {
            return Log::channel(self::CHANNEL);
        } catch (Throwable $e) {
            return Log::channel(config('logging.default', 'stack'));
        }
    }

    private static function typesOf(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $out[$key] = self::isList($value) ? 'array['.count($value).']' : 'object';
            } else {
                $out[$key] = $value === null ? 'null' : gettype($value);
            }
        }

        return $out;
    }

    private static function isList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }
}
