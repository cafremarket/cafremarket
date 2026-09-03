<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WellKnownController extends Controller
{
    /**
     * Android App Links verification.
     *
     * Play App Signing: add the SHA-256 from Play Console → App integrity
     * via env APP_LINK_SHA256 (colon-separated or colon-free).
     */
    public function assetLinks(): JsonResponse
    {
        $fingerprints = array_values(array_unique(array_filter([
            '90:8C:7F:15:22:A4:B2:B8:BB:99:75:37:CE:1C:DF:6B:34:07:3F:9D:FA:39:4D:0E:3A:DE:E5:FB:9C:8F:4F:01',
            'BE:16:1A:73:8B:02:97:D4:31:3E:85:C6:88:A6:70:57:77:3C:9D:4D:50:94:1A:79:06:DE:C1:34:7B:00:E1:6D',
            $this->normalizeFingerprint(env('APP_LINK_SHA256')),
        ])));

        return response()
            ->json([
                [
                    'relation' => ['delegate_permission/common.handle_all_urls'],
                    'target' => [
                        'namespace' => 'android_app',
                        'package_name' => 'com.daimone.cafremarket',
                        'sha256_cert_fingerprints' => $fingerprints,
                    ],
                ],
            ])
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * iOS Universal Links verification.
     */
    public function appleAppSiteAssociation(): Response
    {
        $payload = [
            'applinks' => [
                'apps' => [],
                'details' => [
                    [
                        'appID' => 'W8MMGGU484.com.daimone.cafremarket',
                        'paths' => [
                            '/product/*',
                            '/listing/*',
                            '/shop/*',
                            '/category/*',
                            '/categories/*',
                            '/categorygrp/*',
                            '/blog/*',
                        ],
                        'components' => [
                            ['/' => '/product/*'],
                            ['/' => '/listing/*'],
                            ['/' => '/shop/*'],
                            ['/' => '/category/*'],
                            ['/' => '/categories/*'],
                            ['/' => '/categorygrp/*'],
                            ['/' => '/blog/*'],
                        ],
                    ],
                ],
            ],
        ];

        return response()
            ->json($payload, 200, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'public, max-age=3600',
            ], JSON_UNESCAPED_SLASHES);
    }

    private function normalizeFingerprint(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $value) ?? '');
        if (strlen($clean) !== 64) {
            return null;
        }

        return implode(':', str_split($clean, 2));
    }
}
