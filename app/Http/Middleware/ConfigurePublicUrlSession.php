<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Align APP URL, session cookies, and JWT cookie flags with the actual public host.
 * Must run after TrustProxies and before StartSession (mobile + ngrok CSRF fix).
 */
class ConfigurePublicUrlSession
{
    public function handle(Request $request, Closure $next)
    {
        $host = strtolower($request->getHost());
        $isNgrok = $this->isNgrokHost($host);
        $isLocalIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
        $isHttps = $request->isSecure();

        if ($isNgrok) {
            URL::forceRootUrl('https://'.$request->getHttpHost());
            URL::forceScheme('https');

            config([
                'app.url' => 'https://'.$request->getHttpHost(),
                'session.secure' => true,
                'session.same_site' => 'lax',
                'session.domain' => null,
                'session.http_only' => true,
                'jwt.secure' => true,
                'jwt.same_site' => 'lax',
                'jwt.cookie_domain' => null,
            ]);
        } elseif (app()->environment('local', 'development') && ($isLocalIp || ! $isHttps)) {
            // Phone on LAN: http://192.168.x.x:8000 — Secure cookies would never stick.
            config([
                'session.secure' => false,
                'session.same_site' => 'lax',
                'session.domain' => null,
                'jwt.secure' => false,
                'jwt.same_site' => 'lax',
                'jwt.cookie_domain' => null,
            ]);
        } elseif ($isHttps) {
            config([
                'session.secure' => true,
                'jwt.secure' => true,
            ]);
        }

        return $next($request);
    }

    private function isNgrokHost(string $host): bool
    {
        return str_contains($host, 'ngrok-free.app')
            || str_contains($host, 'ngrok-free.dev')
            || str_contains($host, 'ngrok.io')
            || str_contains($host, 'ngrok.app');
    }
}
