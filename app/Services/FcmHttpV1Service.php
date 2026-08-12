<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Modern FCM HTTP v1 sender (iOS + Android).
 * Uses Google service-account JWT → OAuth access token.
 */
class FcmHttpV1Service
{
    /**
     * @param  string  $token  Device FCM registration token
     * @param  array  $notification  ['title' => ..., 'body' => ..., 'image' => optional]
     * @param  string  $audience  customer|vendor|delivery
     * @param  array  $data  Custom data map (string values)
     * @return bool
     */
    public static function send(string $token, array $notification, string $audience = 'customer', array $data = []): bool
    {
        $token = FCMService::normalizeToken($token);
        if ($token === '') {
            return false;
        }

        $cfg = self::configForAudience($audience);
        if (! $cfg) {
            return false;
        }

        $accessToken = self::accessToken($cfg['credentials'], $audience);
        if ($accessToken === '') {
            return false;
        }

        $message = [
            'token' => $token,
            'notification' => array_filter([
                'title' => (string) ($notification['title'] ?? ''),
                'body' => (string) ($notification['body'] ?? ''),
                'image' => $notification['image'] ?? null,
            ], static fn ($v) => $v !== null && $v !== ''),
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'channel_id' => 'caf_messages',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                    'apns-push-type' => 'alert',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                        'content-available' => 1,
                    ],
                ],
            ],
        ];

        if (! empty($data)) {
            $message['data'] = [];
            foreach ($data as $k => $v) {
                $message['data'][(string) $k] = is_scalar($v) || $v === null
                    ? (string) $v
                    : json_encode($v);
            }
        }

        $url = 'https://fcm.googleapis.com/v1/projects/'.$cfg['project_id'].'/messages:send';

        try {
            $client = self::httpClient()->withToken($accessToken);
            $response = $client->post($url, ['message' => $message]);

            if ($response->failed()) {
                Log::warning('FCM v1 send failed', [
                    'audience' => $audience,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM v1 exception: '.$e->getMessage(), ['audience' => $audience]);

            return false;
        }
    }

    public static function isConfigured(string $audience = 'customer'): bool
    {
        $cfg = self::configForAudience($audience);

        return $cfg !== null;
    }

    protected static function configForAudience(string $audience): ?array
    {
        $audience = strtolower($audience);
        if (in_array($audience, ['vendor', 'merchant'], true)) {
            $key = 'vendor';
        } elseif ($audience === 'delivery') {
            $key = 'delivery';
        } else {
            $key = 'customer';
        }

        $projectId = (string) config("fcm.{$key}.project_id");
        $credentials = self::resolveCredentialsPath((string) config("fcm.{$key}.credentials"));

        if ($projectId === '' || $credentials === '' || ! is_readable($credentials)) {
            return null;
        }

        return [
            'project_id' => $projectId,
            'credentials' => $credentials,
        ];
    }

    protected static function resolveCredentialsPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (is_file($path)) {
            return $path;
        }
        if (is_file(base_path($path))) {
            return base_path($path);
        }
        if (is_file(storage_path($path))) {
            return storage_path($path);
        }

        return $path;
    }

    protected static function accessToken(string $credentialsPath, string $audience): string
    {
        $cacheKey = 'fcm_v1_access_token_'.md5($credentialsPath);

        return (string) Cache::remember($cacheKey, now()->addMinutes(45), function () use ($credentialsPath) {
            $json = json_decode((string) file_get_contents($credentialsPath), true);
            if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
                Log::warning('FCM v1 credentials invalid', ['path' => $credentialsPath]);

                return '';
            }

            $now = time();
            $jwtHeader = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $jwtClaim = self::base64UrlEncode(json_encode([
                'iss' => $json['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $unsigned = $jwtHeader.'.'.$jwtClaim;
            $signature = '';
            $ok = openssl_sign($unsigned, $signature, $json['private_key'], OPENSSL_ALGO_SHA256);
            if (! $ok) {
                Log::warning('FCM v1 JWT sign failed');

                return '';
            }

            $jwt = $unsigned.'.'.self::base64UrlEncode($signature);

            $response = self::httpClient()->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                Log::warning('FCM v1 OAuth failed', ['body' => $response->body()]);

                return '';
            }

            return (string) ($response->json('access_token') ?? '');
        });
    }

    protected static function httpClient()
    {
        $verify = (bool) config('fcm.verify_ssl', true);
        $caBundle = config('fcm.ca_bundle');
        $client = Http::acceptJson();
        if (! empty($caBundle)) {
            $client = $client->withOptions(['verify' => $caBundle]);
        } else {
            $client = $client->withOptions(['verify' => $verify]);
        }

        return $client;
    }

    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
