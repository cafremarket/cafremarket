<?php

namespace App\Notifications\Dispute;

use App\Models\Dispute;
use App\Notifications\Push\HasNotifications;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Closed extends Notification implements ShouldQueue
{
    use Queueable;

    public $dispute;

    public function __construct(Dispute $dispute)
    {
        $this->dispute = $dispute;
    }

    public function via($notifiable)
    {
        $orderNumber = optional($this->dispute->order)->order_number;
        $ticket = $this->dispute->ticketRef();
        $data = [
            'type' => 'dispute',
            'dispute_id' => (int) $this->dispute->id,
            'order_id' => (int) optional($this->dispute->order)->id,
            'order_number' => (string) $orderNumber,
        ];
        $notification = [
            'title' => trans('notifications.dispute_closed.subject', [
                'order_id' => $orderNumber,
                'ticket' => $ticket,
            ]),
            'body' => trans('notifications.dispute_closed.message', [
                'order_id' => $orderNumber,
                'ticket' => $ticket,
            ]),
        ];

        // Listener notifies customer and shop separately — push only the matching audience.
        if ($notifiable instanceof \App\Models\Customer) {
            $customerToken = optional($notifiable)->fcm_token ?: optional($this->dispute->customer)->fcm_token;
            if ($customerToken) {
                FCMService::send($customerToken, $notification, 'customer', $data);
            }
        } else {
            FCMService::sendToShop($this->dispute->shop, $notification, $data);
        }

        if (optional($this->dispute->order)->device_id !== null) {
            HasNotifications::pushNotification(self::toArray($notifiable));
        }

        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from(get_sender_email(), get_sender_name())
            ->subject(trans('notifications.dispute_closed.subject', [
                'order_id' => $this->dispute->order->order_number,
                'ticket' => $this->dispute->ticketRef(),
            ]))
            ->markdown('admin.mail.dispute.closed', [
                'url' => route('admin.support.dispute.show', $this->dispute->id),
                'dispute' => $this->dispute,
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'id' => $this->dispute->id,
            'device_id' => optional($this->dispute->order)->device_id,
            'status' => $this->dispute->statusName(),
            'customer' => $this->dispute->customer->getName(),
            'order_number' => $this->dispute->order->order_number,
            'ticket' => $this->dispute->ticketRef(),
            'subject' => trans('notifications.dispute_closed.subject', [
                'order_id' => $this->dispute->order->order_number,
                'ticket' => $this->dispute->ticketRef(),
            ]),
            'message' => trans('notifications.dispute_closed.message', [
                'order_id' => $this->dispute->order->order_number,
                'ticket' => $this->dispute->ticketRef(),
            ]),
        ];
    }
}
