<?php

use App\Models\EmailLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

if (! function_exists('is_mail_transport_error')) {
    function is_mail_transport_error(\Throwable $e): bool
    {
        // Never swallow auth / validation / DB uniqueness errors as "mail" failures.
        if ($e instanceof \Illuminate\Validation\ValidationException
            || $e instanceof \Illuminate\Auth\AuthenticationException
            || $e instanceof \Illuminate\Database\QueryException
            || (function_exists('is_unique_constraint_violation') && is_unique_constraint_violation($e))) {
            return false;
        }

        $class = $e::class;

        if (
            str_contains($class, 'Symfony\\Component\\Mailer')
            || str_contains($class, 'Swift_')
            || str_contains($class, 'TransportException')
            || str_contains($class, 'RfcComplianceException')
        ) {
            return true;
        }

        $message = strtolower($e->getMessage());

        // Strip common auth wording that contains "mail" / "email" so messages like
        // "This email is already registered" are never treated as SMTP failures.
        $message = str_replace(['email', 'already registered', 'already has an account'], '', $message);

        return str_contains($message, 'smtp')
            || str_contains($message, 'starttls')
            || str_contains($message, 'stream_socket')
            || str_contains($message, 'failed to authenticate')
            || str_contains($message, 'recipient address rejected')
            || str_contains($message, 'expected response code')
            || str_contains($message, 'mailbox');
    }
}

if (! function_exists('email_logs_ready')) {
    function email_logs_ready(): bool
    {
        try {
            return class_exists(EmailLog::class) && Schema::hasTable('email_logs');
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('log_email_event')) {
    /**
     * Persist a row in email_logs (sent / failed / pending).
     */
    function log_email_event(array $attributes): ?EmailLog
    {
        if (! email_logs_ready()) {
            return null;
        }

        try {
            return EmailLog::create($attributes);
        } catch (\Throwable $e) {
            Log::error('email_logs write failed: '.$e->getMessage());

            return null;
        }
    }
}

if (! function_exists('notify_super_admin_mail_failure')) {
    /**
     * In-app alert for super admin (database channel only — no email).
     */
    function notify_super_admin_mail_failure(string $errorMessage, string $context = '', ?int $orderId = null): void
    {
        Log::channel('mail')->warning('Mail delivery failed.', [
            'context' => $context,
            'order_id' => $orderId,
            'error' => $errorMessage,
        ]);
    }
}

if (! function_exists('safe_notify')) {
    /**
     * Send a notification without breaking checkout / order flows.
     */
    function safe_notify($notifiable, $notification, string $context = ''): bool
    {
        if (! $notifiable) {
            return false;
        }

        try {
            // Prefer immediate send so transport errors are caught here and never
            // escape as HTTP responses (queued sync drivers can bubble otherwise).
            if (method_exists($notifiable, 'notifyNow')) {
                $notifiable->notifyNow($notification);
            } else {
                $notifiable->notify($notification);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Notification failed ('.$context.'): '.$e->getMessage(), [
                'notifiable' => is_object($notifiable) ? $notifiable::class : gettype($notifiable),
            ]);

            if (is_mail_transport_error($e)) {
                log_email_event([
                    'to' => is_object($notifiable) ? ($notifiable->email ?? null) : null,
                    'notification' => is_object($notification) ? $notification::class : null,
                    'status' => EmailLog::STATUS_FAILED,
                    'error' => $e->getMessage(),
                    'context' => $context ?: 'safe_notify',
                    'related_type' => is_object($notifiable) ? $notifiable::class : null,
                    'related_id' => is_object($notifiable) && isset($notifiable->id) ? $notifiable->id : null,
                ]);

                notify_super_admin_mail_failure($e->getMessage(), $context);
            }

            return false;
        }
    }
}

if (! function_exists('safe_mail_route_notify')) {
    function safe_mail_route_notify(string $email, $notification, string $context = ''): bool
    {
        if (! $email) {
            return false;
        }

        try {
            Notification::route('mail', $email)->notify($notification);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Mail route notification failed ('.$context.'): '.$e->getMessage());

            if (is_mail_transport_error($e)) {
                log_email_event([
                    'to' => $email,
                    'notification' => is_object($notification) ? $notification::class : null,
                    'status' => EmailLog::STATUS_FAILED,
                    'error' => $e->getMessage(),
                    'context' => $context ?: 'safe_mail_route_notify',
                ]);

                notify_super_admin_mail_failure($e->getMessage(), $context);
            }

            return false;
        }
    }
}

if (! function_exists('safe_dispatch_order_event')) {
    function safe_dispatch_order_event(object $event, string $context = ''): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            Log::warning($context.' event failed: '.$e->getMessage());

            if (is_mail_transport_error($e)) {
                $orderId = $event->order->id ?? null;
                log_email_event([
                    'status' => EmailLog::STATUS_FAILED,
                    'error' => $e->getMessage(),
                    'context' => $context ?: 'safe_dispatch_order_event',
                    'related_type' => isset($event->order) ? $event->order::class : null,
                    'related_id' => $orderId,
                ]);
                notify_super_admin_mail_failure($e->getMessage(), $context, $orderId);
            }
        }
    }
}
