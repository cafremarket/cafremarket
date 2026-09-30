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

        $limits = $this->limitFor($request);

        $this->assertIsArray($limits);
        $this->assertSame(20, $limits[0]->maxAttempts);
        $this->assertSame('auth:203.0.113.10|buyer@example.com', $limits[0]->key);

        // Per account regardless of IP, so rotating IPs cannot brute-force one account.
        $this->assertSame(10, $limits[1]->maxAttempts);
        $this->assertSame('auth-id:api/auth/login|buyer@example.com', $limits[1]->key);
        $this->assertSame(60, $limits[2]->maxAttempts);
        $this->assertSame(3600, $limits[2]->decaySeconds);
    }

    public function test_payment_webhooks_are_not_rate_limited()
    {
        $limit = $this->limitFor(Request::create('/api/emola/callback', 'POST'));

        $this->assertInstanceOf(Unlimited::class, $limit);
    }

    /**
     * @return Limit|array<int, Limit>
     */
    private function limitFor(Request $request): Limit|array
    {
        $limiter = RateLimiter::limiter('api');

        $this->assertIsCallable($limiter);

        return $limiter($request);
    }
}
