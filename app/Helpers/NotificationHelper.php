<?php

use App\Models\System;
use App\Notifications\SuperAdmin\MailDeliveryFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

if (! function_exists('is_mail_transport_error')) {
    function is_mail_transport_error(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'mail')
            || str_contains($message, 'smtp')
            || str_contains($message, 'starttls')
            || str_contains($message, 'stream_socket')
            || str_contains($message, 'certificate')
            || str_contains($message, 'connection could not be established')
            || str_contains($message, 'failed to authenticate');
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
            $notifiable->notify($notification);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Notification failed ('.$context.'): '.$e->getMessage(), [
                'notifiable' => is_object($notifiable) ? $notifiable::class : gettype($notifiable),
            ]);

            if (is_mail_transport_error($e)) {
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
                notify_super_admin_mail_failure($e->getMessage(), $context, $orderId);
            }
        }
    }
}
