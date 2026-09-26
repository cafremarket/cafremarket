<?php

namespace App\Services\Auth;

use App\Models\EmailLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Decides which anti-fake-account checks can actually run right now.
 *
 * Email verification is only enforced while mail is configured and the most
 * recent delivery attempt succeeded; otherwise users would be asked for a
 * code that never arrives. The admin report shows why a check is off.
 */
class VerificationHealth
{
    private const MAIL_CACHE_KEY = 'verification_health:mail';

    private const MAIL_TRANSPORTS = ['smtp', 'ses', 'ses-v2', 'mailgun', 'postmark', 'sendmail', 'resend', 'failover', 'roundrobin'];

    public static function mailConfigured(): bool
    {
        $mailer = (string) config('mail.default');

        if (! in_array($mailer, self::MAIL_TRANSPORTS, true)) {
            return false;
        }

        return $mailer !== 'smtp' || filled(config('mail.mailers.smtp.host'));
    }

    /**
     * status is one of: working, failing, not_configured.
     *
     * @return array{status: string, last_failure: ?EmailLog, last_sent: ?EmailLog}
     */
    public static function mail(): array
    {
        if (! self::mailConfigured()) {
            return ['status' => 'not_configured', 'last_failure' => null, 'last_sent' => null];
        }

        if (! email_logs_ready()) {
            return ['status' => 'working', 'last_failure' => null, 'last_sent' => null];
        }

        $lastFailure = EmailLog::where('status', EmailLog::STATUS_FAILED)->latest('id')->first();
        $lastSent = EmailLog::where('status', EmailLog::STATUS_SENT)->latest('id')->first();

        // Failing = the latest finished attempt in the last day failed.
        $failing = $lastFailure
            && $lastFailure->created_at?->gt(now()->subDay())
            && (! $lastSent || $lastSent->id < $lastFailure->id);

        return [
            'status' => $failing ? 'failing' : 'working',
            'last_failure' => $lastFailure,
            'last_sent' => $lastSent,
        ];
    }

    public static function mailWorking(): bool
    {
        $check = function () {
            try {
                return self::mail()['status'] === 'working';
            } catch (\Throwable $e) {
                self::logIssue('mail', 'Mail health check failed: '.$e->getMessage());

                return false;
            }
        };

        try {
            return Cache::remember(self::MAIL_CACHE_KEY, now()->addMinutes(2), $check);
        } catch (\Throwable $e) {
            // Cache store down: decide without it.
            self::logIssue('cache', 'Cache unavailable for mail health: '.$e->getMessage());

            return $check();
        }
    }

    public static function emailVerificationEnforced(): bool
    {
        return self::mailWorking();
    }

    /**
     * No SMS gateway is wired up yet; phone numbers are only format-checked.
     */
    public static function smsConfigured(): bool
    {
        return false;
    }

    public static function recaptchaConfigured(): bool
    {
        return filled(config('services.recaptcha.key')) && filled(config('services.recaptcha.secret'));
    }

    /**
     * Record that Google's reCAPTCHA API could not be reached (shown in the report).
     */
    public static function recordRecaptchaFailure(string $error): void
    {
        self::logIssue('recaptcha', $error);

        try {
            Cache::put('verification_health:recaptcha_failure', [
                'error' => $error,
                'at' => now()->toDateTimeString(),
            ], now()->addDays(7));
        } catch (\Throwable $e) {
            // Already logged above.
        }
    }

    public static function lastRecaptchaFailure(): ?array
    {
        try {
            return Cache::get('verification_health:recaptcha_failure');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Record a problem in storage/logs/verification.log (and the admin
     * report's recent-issues list). Never throws: a broken check must not
     * turn into an error page for the user.
     */
    public static function logIssue(string $area, string $message, array $context = []): void
    {
        try {
            Log::channel('verification')->warning("[{$area}] {$message}", $context);
        } catch (\Throwable $e) {
            try {
                Log::warning("[verification:{$area}] {$message}", $context);
            } catch (\Throwable $e) {
            }
        }

        try {
            $issues = Cache::get('verification_health:issues', []);
            array_unshift($issues, ['area' => $area, 'message' => $message, 'at' => now()->toDateTimeString()]);
            Cache::put('verification_health:issues', array_slice($issues, 0, 20), now()->addDays(7));
        } catch (\Throwable $e) {
        }
    }

    public static function recentIssues(): array
    {
        try {
            return Cache::get('verification_health:issues', []);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
