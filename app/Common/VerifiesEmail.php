<?php

namespace App\Common;

use App\Notifications\Auth\SendVerificationEmail;
use App\Services\Auth\VerificationHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Email ownership verification for Customer and User.
 *
 * An account is verified when verification_token is null. Unverified
 * accounts get both a link (the token) and a 6-digit code by email; the
 * code is what the mobile apps use.
 */
trait VerifiesEmail
{
    public static int $emailCodeTtlMinutes = 30;

    public static int $emailCodeMaxAttempts = 5;

    public static int $emailCodeResendSeconds = 60;

    /**
     * Set while this trait itself changes verification_token, so the
     * guard below can tell a legitimate change from a mass-assigned one.
     */
    private bool $verificationWriteAllowed = false;

    private bool $emailChangedNeedsVerification = false;

    public static function bootVerifiesEmail(): void
    {
        static::updating(function ($model) {
            if ($model->verificationWriteAllowed) {
                return;
            }

            // Profile endpoints mass-assign request input; never let them
            // clear or pick the token (which would self-verify the account).
            if ($model->isDirty('verification_token')) {
                $model->verification_token = $model->getOriginal('verification_token');
            }

            // A new address must be proven again.
            if ($model->isDirty('email') && $model->getOriginal('email') !== null
                && Str::lower((string) $model->email) !== Str::lower((string) $model->getOriginal('email'))) {
                $model->verification_token = Str::random(40);
                $model->emailChangedNeedsVerification = true;
            }
        });

        static::updated(function ($model) {
            if ($model->emailChangedNeedsVerification) {
                $model->emailChangedNeedsVerification = false;
                $model->forgetEmailVerificationCode();
                $model->sendEmailVerification();
            }
        });
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->verification_token === null;
    }

    /**
     * True when this account must verify before ordering/reviewing/selling.
     */
    public function needsEmailVerification(): bool
    {
        return ! $this->hasVerifiedEmail() && VerificationHealth::emailVerificationEnforced();
    }

    public function markEmailAsVerified(): bool
    {
        return $this->writeVerification(function () {
            $this->verification_token = null;

            if (array_key_exists('email_verified_at', $this->getAttributes())) {
                $this->email_verified_at = now();
            }

            $this->forgetEmailVerificationCode();

            return $this->save();
        });
    }

    public const EMAIL_CODE_NOT_SENT = -1;

    /**
     * Issue a fresh link token + code and email them.
     *
     * @return int seconds the caller must wait before a resend, 0 when sent,
     *             EMAIL_CODE_NOT_SENT when mail is unavailable (logged, never shown)
     */
    public function sendEmailVerification(bool $force = false): int
    {
        // Without a mail transport the code can't be delivered; the admin
        // verification report shows this instead of the user seeing an error.
        if ($this->hasVerifiedEmail()) {
            return 0;
        }

        if (! VerificationHealth::mailConfigured()) {
            VerificationHealth::logIssue('mail', 'Verification email not sent (no mail server configured)', ['email' => $this->email]);

            return self::EMAIL_CODE_NOT_SENT;
        }

        try {
            return $this->issueEmailVerificationCode($force);
        } catch (\Throwable $e) {
            VerificationHealth::logIssue('email-code', 'Could not issue verification code: '.$e->getMessage(), ['email' => $this->email]);

            return self::EMAIL_CODE_NOT_SENT;
        }
    }

    private function issueEmailVerificationCode(bool $force): int
    {
        $throttleKey = $this->emailVerificationCacheKey('resend');

        if (! $force && ($until = Cache::get($throttleKey))) {
            $wait = $until - now()->getTimestamp();
            if ($wait > 0) {
                return $wait;
            }
        }

        $code = (string) random_int(100000, 999999);

        $this->writeVerification(function () {
            $this->verification_token = Str::random(40);
            $this->saveQuietly();
        });

        Cache::put($this->emailVerificationCacheKey('code'), [
            'hash' => hash_hmac('sha256', $code, config('app.key')),
            'attempts' => 0,
        ], now()->addMinutes(static::$emailCodeTtlMinutes));

        Cache::put($throttleKey, now()->addSeconds(static::$emailCodeResendSeconds)->getTimestamp(), static::$emailCodeResendSeconds);

        if (! safe_notify($this, new SendVerificationEmail($this, $code), 'email verification')) {
            Cache::forget($throttleKey);
            VerificationHealth::logIssue('mail', 'Verification email failed to send (see mail.log / Email logs)', ['email' => $this->email]);

            return self::EMAIL_CODE_NOT_SENT;
        }

        return 0;
    }

    /**
     * @return string one of: verified, invalid, expired, too_many_attempts
     */
    public function verifyEmailCode(string $code): string
    {
        if ($this->hasVerifiedEmail()) {
            return 'verified';
        }

        $key = $this->emailVerificationCacheKey('code');

        try {
            $entry = Cache::get($key);
        } catch (\Throwable $e) {
            VerificationHealth::logIssue('cache', 'Could not read verification code: '.$e->getMessage(), ['email' => $this->email]);

            return 'expired';
        }

        if (! $entry) {
            return 'expired';
        }

        if ($entry['attempts'] >= static::$emailCodeMaxAttempts) {
            return 'too_many_attempts';
        }

        $given = hash_hmac('sha256', preg_replace('/\D+/', '', $code), config('app.key'));

        if (! hash_equals($entry['hash'], $given)) {
            $entry['attempts']++;

            try {
                Cache::put($key, $entry, now()->addMinutes(static::$emailCodeTtlMinutes));
            } catch (\Throwable $e) {
                VerificationHealth::logIssue('cache', 'Could not record failed code attempt: '.$e->getMessage());
            }

            return $entry['attempts'] >= static::$emailCodeMaxAttempts ? 'too_many_attempts' : 'invalid';
        }

        $this->markEmailAsVerified();

        return 'verified';
    }

    public function forgetEmailVerificationCode(): void
    {
        try {
            Cache::forget($this->emailVerificationCacheKey('code'));
        } catch (\Throwable $e) {
            // Expires on its own.
        }
    }

    private function emailVerificationCacheKey(string $suffix): string
    {
        return 'email_verify:'.$this->getTable().':'.$this->getKey().':'.$suffix;
    }

    private function writeVerification(callable $callback)
    {
        $this->verificationWriteAllowed = true;

        try {
            return $callback();
        } finally {
            $this->verificationWriteAllowed = false;
        }
    }
}
