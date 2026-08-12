<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Unified push entry-point for all apps (iOS + Android).
 * Prefers FCM HTTP v1 (service account); falls back to legacy server key.
 */
class FCMService
{
    /**
     * @param  string|null  $token
     * @param  array  $notification  ['title' => ..., 'body' => ..., 'image' => optional]
     * @param  string  $audience  customer|vendor|delivery|merchant
     * @param  array  $data
     * @return bool
     */
    public static function send($token, $notification, string $audience = 'customer', array $data = []): bool
    {
        $token = self::normalizeToken($token);
        if ($token === '') {
            return false;
        }

        $driver = strtolower((string) config('fcm.driver', 'auto'));
        $v1Ready = FcmHttpV1Service::isConfigured($audience);

        if ($driver === 'v1' || ($driver === 'auto' && $v1Ready)) {
            return FcmHttpV1Service::send($token, $notification, $audience, $data);
        }

        return self::sendLegacy($token, $notification, $audience, $data);
    }

    /**
     * Send to many tokens. Returns [sent => n, failed => n].
     */
    public static function sendToMany(iterable $tokens, array $notification, string $audience = 'customer', array $data = []): array
    {
        $sent = 0;
        $failed = 0;
        $seen = [];

        foreach ($tokens as $token) {
            $normalized = self::normalizeToken($token);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;

            if (self::send($normalized, $notification, $audience, $data)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return compact('sent', 'failed');
    }

    public static function normalizeToken($token): string
    {
        if (! is_string($token) && ! is_numeric($token)) {
            return '';
        }
        $token = trim((string) $token);
        $token = trim($token, "\"'");

        return $token;
    }

    public static function driverStatus(): array
    {
        return [
            'driver' => config('fcm.driver', 'auto'),
            'customer_v1' => FcmHttpV1Service::isConfigured('customer'),
            'vendor_v1' => FcmHttpV1Service::isConfigured('vendor'),
            'delivery_v1' => FcmHttpV1Service::isConfigured('delivery'),
            'customer_legacy' => self::serverKeyFor('customer') !== '',
            'vendor_legacy' => self::serverKeyFor('vendor') !== '',
            'delivery_legacy' => self::serverKeyFor('delivery') !== '',
            'customer_project' => config('fcm.customer.project_id'),
            'vendor_project' => config('fcm.vendor.project_id'),
            'delivery_project' => config('fcm.delivery.project_id'),
        ];
    }

    protected static function sendLegacy(string $token, array $notification, string $audience, array $data): bool
    {
        $serverKey = self::serverKeyFor($audience);
        if ($serverKey === '') {
            Log::warning('FCM send skipped: missing credentials', [
                'audience' => $audience,
                'hint' => 'Add service account JSON for FCM v1 or set FCM_TOKEN_* for legacy',
            ]);

            return false;
        }

        $verify = (bool) config('fcm.verify_ssl', true);
        $caBundle = config('fcm.ca_bundle');

        $client = Http::acceptJson()->withHeaders([
            'Authorization' => 'key='.$serverKey,
        ]);

        if (! empty($caBundle)) {
            $client = $client->withOptions(['verify' => $caBundle]);
        } else {
            $client = $client->withOptions(['verify' => $verify]);
        }

        $payload = [
            'to' => $token,
            'priority' => 'high',
            'notification' => array_filter([
                'title' => $notification['title'] ?? '',
                'body' => $notification['body'] ?? '',
                'image' => $notification['image'] ?? null,
                'sound' => 'default',
            ], static fn ($v) => $v !== null && $v !== ''),
        ];

        if (! empty($data)) {
            $payload['data'] = array_map(static function ($v) {
                return is_scalar($v) || $v === null ? (string) $v : json_encode($v);
            }, $data);
        }

        try {
            $response = $client->post('https://fcm.googleapis.com/fcm/send', $payload);
            if ($response->failed()) {
                Log::warning('FCM legacy send HTTP error', [
                    'audience' => $audience,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM legacy send failed: '.$e->getMessage(), [
                'audience' => $audience,
            ]);

            return false;
        }
    }

    protected static function serverKeyFor(string $audience): string
    {
        $audience = strtolower($audience);
        if (in_array($audience, ['vendor', 'merchant'], true)) {
            $key = config('fcm.token_vendor') ?: config('fcm.token');
        } elseif ($audience === 'delivery') {
            $key = config('fcm.token_delivery') ?: config('fcm.token_vendor') ?: config('fcm.token');
        } else {
            $key = config('fcm.token_customer') ?: config('fcm.token');
        }

        return self::normalizeToken($key ?? '');
    }
}
