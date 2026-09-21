<?php

namespace Incevio\Package\Affiliate\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Incevio\Package\Affiliate\Models\AffiliateLink;

class AffiliateAttributionService
{
    public const SESSION_KEY = 'affiliate_last_click';

    public const CONVERTED_SESSION_KEY = 'affiliate_converted_products';

    /**
     * Remember the last clicked affiliate product link.
     * A later click from any other affiliate overwrites this (last click wins).
     * Persists via session/cookie (web) and cache (customer/vendor apps).
     */
    public function remember(AffiliateLink $link, ?Request $request = null): void
    {
        $request = $request ?: request();

        $payload = [
            'affiliate_id' => (int) $link->affiliate_id,
            'affiliate_link_id' => (int) $link->id,
            'inventory_id' => (int) $link->inventory_id,
            'clicked_at' => now()->toIso8601String(),
        ];

        Session::put(self::SESSION_KEY, $payload);
        Session::put('affiliate_marketer_id', $payload['affiliate_id']);

        Cookie::queue(
            cookie(
                $this->cookieName(),
                json_encode($payload),
                $this->cookieMinutes()
            )
        );

        $this->storeInCache($payload, $request);
    }

    /**
     * Capture a click from a short code / ref query on cart or checkout.
     */
    public function captureFromRequest(?Request $request = null): void
    {
        $request = $request ?: request();
        $code = $request->input('affiliate_code')
            ?? $request->input('ref')
            ?? $request->query('affiliate_code')
            ?? $request->query('ref')
            ?? $request->header('X-Affiliate-Code');

        if (! $code) {
            return;
        }

        $link = AffiliateLink::where('slug', $code)->first();

        if ($link) {
            $this->remember($link, $request);
        }
    }

    /**
     * Last clicked affiliate link, if still inside the attribution window.
     *
     * @return array{affiliate_id:int,affiliate_link_id:int,inventory_id:int,clicked_at:string}|null
     */
    public function current(?Request $request = null): ?array
    {
        $request = $request ?: request();
        $payload = Session::get(self::SESSION_KEY);

        if (! is_array($payload)) {
            $payload = $this->payloadFromCookie();
        }

        if (! is_array($payload)) {
            $payload = $this->payloadFromCache($request);
        }

        if (! $this->isValid($payload)) {
            $this->forget($request);

            return null;
        }

        Session::put(self::SESSION_KEY, $payload);
        Session::put('affiliate_marketer_id', (int) $payload['affiliate_id']);
        $this->storeInCache($payload, $request);

        return $payload;
    }

    /**
     * Valid only when the click is not older than the attribution window.
     */
    public function isValid(?array $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        $affiliateId = (int) ($payload['affiliate_id'] ?? 0);
        $linkId = (int) ($payload['affiliate_link_id'] ?? 0);
        $inventoryId = (int) ($payload['inventory_id'] ?? 0);
        $clickedAt = $payload['clicked_at'] ?? null;

        if ($affiliateId < 1 || $linkId < 1 || $inventoryId < 1 || ! $clickedAt) {
            return false;
        }

        try {
            $clicked = Carbon::parse($clickedAt);
        } catch (\Throwable $e) {
            return false;
        }

        $expiresAt = $clicked->copy()->addDays($this->attributionDays());

        return now()->lte($expiresAt);
    }

    /**
     * A click earns again only after the previous attribution window has ended.
     */
    public function canEarnForClick(array $click): bool
    {
        if (! $this->isValid($click)) {
            return false;
        }

        $windowEnd = $this->conversionWindowEnd((int) ($click['inventory_id'] ?? 0));

        if (! $windowEnd) {
            return true;
        }

        try {
            $clickedAt = Carbon::parse($click['clicked_at']);
        } catch (\Throwable $e) {
            return false;
        }

        return $clickedAt->gte($windowEnd);
    }

    public function markProductConverted(int $inventoryId, ?string $clickedAt = null, ?Request $request = null): void
    {
        $request = $request ?: request();

        try {
            $start = $clickedAt ? Carbon::parse($clickedAt) : now();
        } catch (\Throwable $e) {
            $start = now();
        }

        $windows = $this->conversionWindows();
        $windows[(string) $inventoryId] = $start->copy()->addDays($this->attributionDays())->toIso8601String();

        Session::put(self::CONVERTED_SESSION_KEY, $windows);
        Cookie::queue(
            cookie(
                $this->convertedCookieName(),
                json_encode($windows),
                365 * 24 * 60
            )
        );

        $cacheKey = $this->convertedCacheKey($request);
        if ($cacheKey) {
            Cache::put($cacheKey, $windows, now()->addDays(365));
        }

        $click = Session::get(self::SESSION_KEY) ?: $this->payloadFromCache($request);
        if (is_array($click) && (int) ($click['inventory_id'] ?? 0) === $inventoryId) {
            $this->forget($request);
        }
    }

    public function forget(?Request $request = null): void
    {
        $request = $request ?: request();

        Session::forget(self::SESSION_KEY);
        Session::forget('affiliate_marketer_id');
        Cookie::queue(Cookie::forget($this->cookieName()));

        $cacheKey = $this->cacheKey($request);
        if ($cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    public function attributionDays(): int
    {
        return max(1, (int) config('affiliate.attribution_days', 7));
    }

    protected function storeInCache(array $payload, Request $request): void
    {
        $cacheKey = $this->cacheKey($request);

        if (! $cacheKey) {
            return;
        }

        Cache::put($cacheKey, $payload, now()->addDays($this->attributionDays()));
    }

    protected function payloadFromCache(Request $request): ?array
    {
        $cacheKey = $this->cacheKey($request);

        if (! $cacheKey) {
            return null;
        }

        $payload = Cache::get($cacheKey);

        return is_array($payload) ? $payload : null;
    }

    /**
     * Prefer authenticated customer; fall back to device id for guest app sessions.
     */
    protected function cacheKey(Request $request): ?string
    {
        $customer = Auth::guard('api')->user()
            ?: Auth::guard('customer')->user();

        if ($customer && isset($customer->id)) {
            return 'affiliate_attr:customer:'.$customer->id;
        }

        $device = $request->header('X-Device-Id')
            ?: $request->input('device_id')
            ?: $request->header('X-Guest-Id');

        if (is_string($device) && $device !== '') {
            return 'affiliate_attr:device:'.sha1($device);
        }

        return null;
    }

    protected function convertedCacheKey(Request $request): ?string
    {
        $base = $this->cacheKey($request);

        return $base ? $base.':converted' : null;
    }

    protected function conversionWindowEnd(int $inventoryId): ?Carbon
    {
        $endsAt = $this->conversionWindows()[(string) $inventoryId] ?? null;

        if (! is_string($endsAt) || $endsAt === '') {
            return null;
        }

        try {
            return Carbon::parse($endsAt);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    protected function conversionWindows(): array
    {
        $windows = Session::get(self::CONVERTED_SESSION_KEY);

        if (! is_array($windows)) {
            $raw = request()->cookie($this->convertedCookieName());
            $windows = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        }

        if (! is_array($windows) || array_is_list($windows)) {
            $cacheKey = $this->convertedCacheKey(request());
            if ($cacheKey) {
                $cached = Cache::get($cacheKey);
                $windows = is_array($cached) ? $cached : [];
            }
        }

        if (! is_array($windows) || array_is_list($windows)) {
            return [];
        }

        $normalized = [];
        foreach ($windows as $inventoryId => $endsAt) {
            if (is_string($endsAt) && $endsAt !== '') {
                $normalized[(string) $inventoryId] = $endsAt;
            }
        }

        return $normalized;
    }

    protected function payloadFromCookie(): ?array
    {
        $raw = request()->cookie($this->cookieName());

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function cookieName(): string
    {
        return (string) config('affiliate.cookie_name', 'affiliate_last_click');
    }

    protected function convertedCookieName(): string
    {
        return (string) config('affiliate.converted_cookie_name', 'affiliate_converted_products');
    }

    protected function cookieMinutes(): int
    {
        return $this->attributionDays() * 24 * 60;
    }
}
