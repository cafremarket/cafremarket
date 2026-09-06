<?php

use App\Models\EmailLog;
use App\Models\System;
use App\Notifications\SuperAdmin\MailDeliveryFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

if (! function_exists('is_mail_transport_error')) {
    function is_mail_transport_error(\Throwable $e): bool
    {
        $class = $e::class;
        $message = strtolower($e->getMessage());

        if (
            str_contains($class, 'Mailer')
            || str_contains($class, 'Mail')
            || str_contains($class, 'UnexpectedResponseException')
            || str_contains($class, 'TransportException')
            || str_contains($class, 'RfcComplianceException')
        ) {
            return true;
        }

        return str_contains($message, 'mail')
            || str_contains($message, 'smtp')
            || str_contains($message, 'starttls')
            || str_contains($message, 'stream_socket')
            || str_contains($message, 'certificate')
            || str_contains($message, 'connection could not be established')
            || str_contains($message, 'failed to authenticate')
            || str_contains($message, 'recipient address rejected')
            || str_contains($message, 'user unknown')
            || str_contains($message, 'mailbox')
            || str_contains($message, 'expected response code')
            || str_contains($message, '550 ')
            || str_contains($message, '553 ')
            || str_contains($message, '554 ');
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
        try {
            $cacheKey = 'mail_failure_admin_notified_'.md5(substr($errorMessage, 0, 120));

            if (Cache::has($cacheKey)) {
                return;
            }

            Cache::put($cacheKey, 1, now()->addMinutes(30));

            $system = System::select('id')->first();

            if (! $system) {
                return;
            }

            $admin = $system->superAdmin();

            if (! $admin) {
                return;
            }

            $admin->notify(new MailDeliveryFailed($errorMessage, $context, $orderId));
        } catch (\Throwable $e) {
            Log::error('Could not notify super admin about mail failure: '.$e->getMessage());
        }
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
