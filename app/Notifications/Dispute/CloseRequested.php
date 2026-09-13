<?php

namespace App\Notifications\Dispute;

use App\Models\Dispute;
use App\Notifications\Push\HasNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CloseRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public $dispute;

    public function __construct(Dispute $dispute)
    {
        $this->dispute = $dispute;
    }

    public function via($notifiable)
    {
        if (optional($this->dispute->order)->device_id !== null) {
            HasNotifications::pushNotification(self::toArray($notifiable));
        }

        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from(get_sender_email(), get_sender_name())
            ->subject(trans('notifications.dispute_close_requested.subject', [
                'order_id' => $this->dispute->order->order_number,
                'ticket' => $this->dispute->ticketRef(),
            ]))
            ->markdown('admin.mail.dispute.close_requested', [
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
            'subject' => trans('notifications.dispute_close_requested.subject', [
                'order_id' => $this->dispute->order->order_number,
                'ticket' => $this->dispute->ticketRef(),
            ]),
            'message' => trans('notifications.dispute_close_requested.message', [
                'order_id' => $this->dispute->order->order_number,
                'ticket' => $this->dispute->ticketRef(),
            ]),
        ];
    }
}
