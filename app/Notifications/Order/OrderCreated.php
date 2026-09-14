<?php

namespace App\Notifications\Order;

use App\Channels\SmsChannel;
use App\Models\Customer;
use App\Models\Order;
use App\Notifications\Push\HasNotifications;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = ['mail'];

        $orderNumber = $this->order->order_number;
        $data = [
            'type' => 'order',
            'order_id' => (int) $this->order->id,
            'order_number' => (string) $orderNumber,
            'status' => (string) $this->order->orderStatus(true),
        ];

        // Pickup orders get their OTP front-and-center in the push/SMS body
        // right away, since there's no later "assign courier" step to attach it to.
        if ($this->order->pickup() && ! empty($this->order->otp)) {
            $notification = [
                'title' => trans('notifications.pickup_order_created.subject', ['order' => $orderNumber]),
                'body' => trans('notifications.pickup_order_created.message', ['order' => $orderNumber, 'otp' => $this->order->otp]),
            ];
        } else {
            $notification = [
                'title' => trans('notifications.order_created.subject', ['order' => $orderNumber]),
                'body' => trans('notifications.order_created.message', ['order' => $orderNumber]),
            ];
        }

        // Customer app push (merchant push is handled by MerchantOrderCreatedNotification)
        $customer_token = optional($this->order->customer)->fcm_token;
        if (! is_null($customer_token)) {
            FCMService::send($customer_token, $notification, 'customer', $data);
        }

        if ($this->order->device_id !== null) {
            HasNotifications::pushNotification(self::toArray($notifiable));
        }

        if ($notifiable instanceof Customer) {
            $channels[] = 'database';
        }

        // Add SMS channel for customers with phone numbers and configured SMS gateway
        if ($notifiable instanceof Customer && $notifiable->phone && $notifiable->smsGateway) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = trans('notifications.order_created.subject', ['order' => $this->order->order_number]);

        return (new MailMessage)
            ->from(get_sender_email(), get_sender_name())
            ->subject($subject)
            ->markdown('admin.mail.order.created', [
                'url' => get_shop_url($this->order->shop),
                'order' => $this->order,
            ]);
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    public function toSms($notifiable)
    {
        if ($this->order->pickup() && ! empty($this->order->otp)) {
            $notification_message = trans('notifications.pickup_order_created.message', ['order' => $this->order->order_number, 'otp' => $this->order->otp]);
        } else {
            $notification_message = trans('notifications.order_created.message', ['order' => $this->order->order_number]);
        }

        return $notification_message."\n".get_shop_url($this->order->shop);
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
            'order' => $this->order->order_number,
            'device_id' => $this->order->device_id,
            'subject' => trans('notifications.order_created.subject', ['order' => $this->order->order_number]),
            'message' => trans('notifications.order_created.message', ['order' => $this->order->order_number]),
            'status' => $this->order->orderStatus(true),
        ];
    }
}
