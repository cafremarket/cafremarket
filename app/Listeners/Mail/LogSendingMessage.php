<?php

namespace App\Listeners\Mail;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class LogSendingMessage
{
    /**
     * Handle the event.
     */
    public function handle(MessageSending $event): void
    {
        $to = $this->addressesToString($event->message->getTo());
        $cc = $this->addressesToString($event->message->getCc());
        $bcc = $this->addressesToString($event->message->getBcc());
        $subject = $event->message instanceof Email ? $event->message->getSubject() : null;

        Log::channel('mail')->info('Message sending', compact('to', 'subject'));

        if (! function_exists('log_email_event')) {
            return;
        }

        $log = log_email_event([
            'to' => $to,
            'cc' => $cc ?: null,
            'bcc' => $bcc ?: null,
            'subject' => $subject,
            'notification' => $event->data['__laravel_notification'] ?? ($event->data['notification'] ?? null),
            'status' => EmailLog::STATUS_PENDING,
            'context' => 'MessageSending',
            'meta' => [
                'view' => $event->data['__laravel_notification'] ?? null,
            ],
        ]);

        if ($log && $event->message instanceof Email) {
            $event->message->getHeaders()->addTextHeader('X-Cafre-Email-Log-Id', (string) $log->id);
        }
    }

    /**
     * @param  Address[]  $addresses
     */
    protected function addressesToString(array $addresses): string
    {
        return implode(', ', array_map(static function ($address) {
            if ($address instanceof Address) {
                return $address->getAddress();
            }

            return (string) $address;
        }, $addresses));
    }
}
