<?php

namespace App\Services\Emola;

use App\Exceptions\PaymentFailedException;
use Illuminate\Support\Facades\Cache;

/**
 * Movitel per-customer (MSISDN) daily payment cap — tracked in cache until end of day.
 */
final class EmolaDailyLimit
{
    private const CACHE_PREFIX = 'emola_daily_total_';

    public static function todayTotal(string $msisdn): int
    {
        return (int) Cache::get(self::cacheKey($msisdn), 0);
    }

    public static function assertCanPay(string $msisdn, int $amountMzn): void
    {
        $msisdn = EmolaSpec::normalizeMsisdn($msisdn);
        $amountMzn = max(0, $amountMzn);
        $dailyMax = (int) config('emola.limits.customer_daily_max', 500_000);

        if ($dailyMax <= 0) {
            return;
        }

        $projected = self::todayTotal($msisdn) + $amountMzn;

        if ($projected > $dailyMax) {
            throw new PaymentFailedException(
                trans('theme.emola_daily_limit', [
                    'max' => number_format($dailyMax, 0, '.', ','),
                    'used' => number_format(self::todayTotal($msisdn), 0, '.', ','),
                    'amount' => number_format($amountMzn, 0, '.', ','),
                ])
            );
        }
    }

    /** Record an accepted USSD push against the customer's daily total. */
    public static function recordAcceptedPush(string $msisdn, int $amountMzn): void
    {
        if ((int) config('emola.limits.customer_daily_max', 0) <= 0) {
            return;
        }

        $msisdn = EmolaSpec::normalizeMsisdn($msisdn);
        $amountMzn = max(0, $amountMzn);

        if ($amountMzn === 0) {
            return;
        }

        $key = self::cacheKey($msisdn);
        $ttl = now()->endOfDay()->diffInSeconds(now());

        if ($ttl < 1) {
            $ttl = 3600;
        }

        Cache::put($key, self::todayTotal($msisdn) + $amountMzn, $ttl);
    }

    private static function cacheKey(string $msisdn): string
    {
        return self::CACHE_PREFIX.EmolaSpec::normalizeMsisdn($msisdn).'_'.now()->format('Ymd');
    }
}
