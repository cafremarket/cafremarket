<?php

namespace App\Listeners\Mail;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;

class LogSentMessage
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        Log::channel('mail')->info('Message sent successfully');

        if (! function_exists('email_logs_ready') || ! email_logs_ready()) {
            return;
        }

        try {
            $message = $event->message;
            $logId = null;

            if ($message instanceof Email && $message->getHeaders()->has('X-Cafre-Email-Log-Id')) {
                $logId = $message->getHeaders()->get('X-Cafre-Email-Log-Id')->getBody();
            }

            if ($logId) {
                EmailLog::where('id', $logId)->update([
                    'status' => EmailLog::STATUS_SENT,
                    'error' => null,
                ]);

                return;
            }

            // Fallback: create a sent row if pending header was missing
            if (function_exists('log_email_event')) {
                $to = '';
                $subject = null;
                if ($message instanceof Email) {
                    $to = implode(', ', array_map(fn ($a) => $a->getAddress(), $message->getTo()));
                    $subject = $message->getSubject();
                }

                log_email_event([
                    'to' => $to,
                    'subject' => $subject,
                    'status' => EmailLog::STATUS_SENT,
                    'context' => 'MessageSent',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Could not mark email_logs as sent: '.$e->getMessage());
        }
    }
}
