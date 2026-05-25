<?php

namespace App\Notifications\SuperAdmin;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Database-only alert when outbound email (SMTP) is failing.
 */
class MailDeliveryFailed extends Notification
{
    use Queueable;

    public function __construct(
        public string $errorMessage,
        public string $context = '',
        public ?int $orderId = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'error' => $this->errorMessage,
            'context' => $this->context,
            'order_id' => $this->orderId,
        ];
    }
}
