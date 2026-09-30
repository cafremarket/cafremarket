<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Proxies whose X-Forwarded-* headers are believed: the local nginx / cloudflared / ngrok hop by
     * default. Trusting '*' lets any client forge its IP and scheme. Override with TRUSTED_PROXIES
     * (comma-separated IPs/CIDRs) when the proxy runs on another host.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];

    protected function proxies()
    {
        if (static::$alwaysTrustProxies) {
            return static::$alwaysTrustProxies;
        }

        $configured = trim((string) config('app.trusted_proxies', ''));

        if ($configured === '') {
            return $this->proxies;
        }

        return array_values(array_filter(array_map('trim', explode(',', $configured))));
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB;
}
