<?php

namespace App\Notifications\Dispute;

use App\Models\Dispute;
use App\Notifications\Push\HasNotifications;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Solved extends Notification implements ShouldQueue
{
    use Queueable;

    public $dispute;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Dispute $dispute)
    {
        $this->dispute = $dispute;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $orderNumber = optional($this->dispute->order)->order_number;
        $data = [
            'type' => 'dispute',
            'dispute_id' => (int) $this->dispute->id,
            'order_id' => (int) optional($this->dispute->order)->id,
            'order_number' => (string) $orderNumber,
        ];
        $notification = [
            'title' => trans('notifications.dispute_solved.subject', ['order_id' => $orderNumber]),
            'body' => trans('notifications.dispute_solved.message', ['order_id' => $orderNumber]),
        ];

        $customerToken = optional($this->dispute->customer)->fcm_token;
        if ($customerToken) {
            FCMService::send($customerToken, $notification, 'customer', $data);
        }

        FCMService::sendToShop($this->dispute->shop, $notification, $data);

        if (optional($this->dispute->order)->device_id !== null) {
            HasNotifications::pushNotification(self::toArray($notifiable));
        }

        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(trans('notifications.dispute_solved.subject', ['order_id' => $this->dispute->order->order_number]))
            ->markdown('admin.mail.dispute.solved', [
                'url' => route('admin.support.dispute.show', $this->dispute->id),
                'dispute' => $this->dispute,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'id' => $this->dispute->id,
            'device_id' => $this->dispute->order->device_id,
            'status' => $this->dispute->statusName(),
            'customer' => $this->dispute->customer->getName(),
            'category' => $this->dispute->dispute_type->detail,
            'order_number' => $this->dispute->order->order_number,
            'subject' => trans('notifications.dispute_solved.subject', [
                'order_id' => $this->dispute->order->order_number,
            ]),
            'message' => trans('notifications.dispute_solved.message', [
                'order_id' => $this->dispute->order->order_number,
            ]),
        ];
    }
}
