<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiRateLimiterTest extends TestCase
{
    public function test_guest_api_traffic_is_not_capped_at_sixty_per_minute()
    {
        $limit = $this->limitFor(Request::create('/api/sliders', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
        ]));

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertGreaterThanOrEqual(300, $limit->maxAttempts);
        $this->assertSame('ip:203.0.113.10', $limit->key);
    }

    public function test_authenticated_api_traffic_is_keyed_by_token_not_shared_ip()
    {
        $request = Request::create('/api/vendor/latest_orders', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
        ]);
        $request->headers->set('Authorization', 'Bearer test-jwt-token');

        $limit = $this->limitFor($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertGreaterThanOrEqual(600, $limit->maxAttempts);
        $this->assertSame('token:'.sha1('test-jwt-token'), $limit->key);
    }

    public function test_login_attempts_stay_strictly_limited()
    {
        $request = Request::create('/api/auth/login', 'POST', [
            'email' => 'buyer@example.com',
        ], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
        ]);

        $limit = $this->limitFor($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(20, $limit->maxAttempts);
        $this->assertSame('auth:203.0.113.10|buyer@example.com', $limit->key);
    }

    public function test_payment_webhooks_are_not_rate_limited()
    {
        $limit = $this->limitFor(Request::create('/api/emola/callback', 'POST'));

        $this->assertInstanceOf(Unlimited::class, $limit);
    }

    private function limitFor(Request $request): Limit
    {
        $limiter = RateLimiter::limiter('api');

        $this->assertIsCallable($limiter);

        $limit = $limiter($request);

        $this->assertInstanceOf(Limit::class, $limit);

        return $limit;
    }
}
