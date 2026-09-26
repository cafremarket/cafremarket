<?php

namespace App\Rules;

use App\Services\Auth\VerificationHealth;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Rejects addresses that cannot receive mail: disposable/throwaway
 * providers and domains with no MX (or A) record.
 */
class RealEmail implements ValidationRule
{
    private const BLOCKLIST = 'resources/data/disposable_email_domains.txt';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = Str::lower(trim(Str::afterLast((string) $value, '@')));

        if ($domain === '' || ! str_contains((string) $value, '@')) {
            return; // Format errors are reported by the 'email' rule.
        }

        try {
            if (self::isDisposable($domain)) {
                $fail(trans('validation.email_disposable'));

                return;
            }

            if (! self::domainAcceptsMail($domain)) {
                $fail(trans('validation.email_domain_invalid'));
            }
        } catch (\Throwable $e) {
            // Broken check (DNS/cache): let the signup through, log it.
            VerificationHealth::logIssue('email-check', 'Email check skipped for '.$domain.': '.$e->getMessage());
        }
    }

    public static function isDisposable(string $domain): bool
    {
        $load = function () {
            $path = base_path(self::BLOCKLIST);

            if (! is_readable($path)) {
                return [];
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            return array_fill_keys(array_map('strtolower', array_map('trim', $lines)), true);
        };

        static $memo = null;
        if ($memo === null) {
            try {
                $memo = Cache::rememberForever('disposable_email_domains', $load);
            } catch (\Throwable $e) {
                $memo = $load();
            }
        }
        $blocked = $memo;

        // Match the domain and every parent domain (e.g. x.mailinator.com).
        $parts = explode('.', $domain);
        while (count($parts) >= 2) {
            if (isset($blocked[implode('.', $parts)])) {
                return true;
            }
            array_shift($parts);
        }

        return false;
    }

    public static function domainAcceptsMail(string $domain): bool
    {
        $key = 'email_domain_mx:'.$domain;

        try {
            $cached = Cache::get($key);
        } catch (\Throwable $e) {
            $cached = null;
        }

        if ($cached !== null) {
            return (bool) $cached;
        }

        $ascii = function_exists('idn_to_ascii') ? (idn_to_ascii($domain) ?: $domain) : $domain;
        $ok = checkdnsrr($ascii.'.', 'MX') || checkdnsrr($ascii.'.', 'A') || checkdnsrr($ascii.'.', 'AAAA');

        // If even a known domain doesn't resolve, the server's DNS is broken:
        // don't reject real people for it, just log it.
        if (! $ok && ! checkdnsrr('gmail.com.', 'MX')) {
            VerificationHealth::logIssue('dns', 'Server DNS lookups are failing; email domain check skipped for '.$domain);

            return true;
        }

        // Misses are cached briefly so a transient DNS failure doesn't lock a real domain out for long.
        try {
            Cache::put($key, $ok ? 1 : 0, $ok ? now()->addDay() : now()->addMinutes(10));
        } catch (\Throwable $e) {
        }

        return $ok;
    }
}
