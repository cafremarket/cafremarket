<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPaymentFailed;
use App\Notifications\Order\PaymentFailed as PaymentFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Notifications\Notification;
use Notification;

class NotifyCustomerPaymentFailed implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(OrderPaymentFailed $event)
    {
        if (! config('system_settings')) {
            setSystemConfig($event->order->shop_id);
        }

        // Set shop configuration
        if ($event->order->shop_id && ! config('shop_settings')) {
            setSystemConfig($event->order->shop_id);
        }

        if ($event->order->customer_id) {
            safe_notify($event->order->customer, new PaymentFailedNotification($event->order), 'payment failed — customer');
        } elseif ($event->order->email) {
            safe_mail_route_notify($event->order->email, new PaymentFailedNotification($event->order), 'payment failed — guest');
        }
    }
}
