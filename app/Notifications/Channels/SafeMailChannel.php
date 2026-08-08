<?php

namespace App\Notifications\Channels;

use App\Models\EmailLog;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Wrap Laravel's mail notification channel so SMTP / recipient errors
 * never break HTTP requests or order flows. Failures are logged instead.
 */
class SafeMailChannel extends MailChannel
{
    /**
     * Send the given notification via mail, swallowing transport errors.
     *
     * @param  mixed  $notifiable
     * @return mixed|void
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            return parent::send($notifiable, $notification);
        } catch (Throwable $e) {
            $this->recordFailure($notifiable, $notification, $e);

            Log::channel('mail')->warning('Mail notification failed (swallowed): '.$e->getMessage(), [
                'notification' => $notification::class,
                'notifiable' => is_object($notifiable) ? $notifiable::class : gettype($notifiable),
            ]);

            if (function_exists('is_mail_transport_error') && is_mail_transport_error($e)) {
                if (function_exists('notify_super_admin_mail_failure')) {
                    notify_super_admin_mail_failure(
                        $e->getMessage(),
                        $notification::class
                    );
                }
            }

            // Do not rethrow — keep checkout / updates / admin actions working.
        }
    }

    /**
     * @param  mixed  $notifiable
     */
    protected function recordFailure($notifiable, Notification $notification, Throwable $e): void
    {
        try {
            if (! class_exists(EmailLog::class)) {
                return;
            }

            $to = $this->resolveRecipient($notifiable, $notification);
            $toString = is_array($to) ? implode(', ', $to) : (string) $to;
            $subject = $this->resolveSubject($notifiable, $notification);

            $pending = EmailLog::query()
                ->where('status', EmailLog::STATUS_PENDING)
                ->when($toString !== '', function ($q) use ($toString) {
                    $q->where('to', $toString);
                })
                ->where('created_at', '>=', now()->subMinutes(5))
                ->orderByDesc('id')
                ->first();

            $payload = [
                'to' => $toString ?: null,
                'subject' => $subject,
                'notification' => $notification::class,
                'status' => EmailLog::STATUS_FAILED,
                'error' => $e->getMessage(),
                'context' => 'SafeMailChannel',
                'related_type' => is_object($notifiable) ? $notifiable::class : null,
                'related_id' => is_object($notifiable) && isset($notifiable->id) ? $notifiable->id : null,
                'meta' => [
                    'exception' => $e::class,
                ],
            ];

            if ($pending) {
                $pending->update($payload);
            } else {
                EmailLog::create($payload);
            }
        } catch (Throwable $logError) {
            Log::error('Could not write email_logs failure row: '.$logError->getMessage());
        }
    }

    /**
     * @param  mixed  $notifiable
     * @return array|string|null
     */
    protected function resolveRecipient($notifiable, Notification $notification)
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $route = $notifiable->routeNotificationFor('mail', $notification);
            if ($route) {
                return $route;
            }
        }

        if (is_object($notifiable) && isset($notifiable->email)) {
            return $notifiable->email;
        }

        if (is_object($notifiable) && method_exists($notifiable, 'getEmailForVerification')) {
            return $notifiable->getEmailForVerification();
        }

        return null;
    }

    /**
     * @param  mixed  $notifiable
     */
    protected function resolveSubject($notifiable, Notification $notification): ?string
    {
        try {
            if (method_exists($notification, 'toMail')) {
                $mail = $notification->toMail($notifiable);

                return $mail->subject ?? null;
            }
        } catch (Throwable $e) {
            // ignore subject resolution failures
        }

        return null;
    }
}
