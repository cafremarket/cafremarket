<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    public static function send($token, $notification)
    {
        $verify = (bool) config('fcm.verify_ssl', true);
        $caBundle = config('fcm.ca_bundle');

        $client = Http::acceptJson()->withToken(config('fcm.token'));

        // Prefer CA bundle path when provided; otherwise use verify flag.
        if (! empty($caBundle)) {
            $client = $client->withOptions(['verify' => $caBundle]);
        } else {
            $client = $client->withOptions(['verify' => $verify]);
        }

        try {
            $client->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => $notification,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM send failed: '.$e->getMessage());
        }
    }
}
