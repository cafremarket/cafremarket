<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderCreated;
use App\Notifications\Order\OrderCreated as OrderCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;

class NotifyCustomerOrderPlaced implements ShouldQueue
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
    public function handle(OrderCreated $event)
    {
        if (! config('system_settings')) {
            setSystemConfig($event->order->shop_id);
        }

        // Set shop configuration
        if ($event->order->shop_id && ! config('shop_settings')) {
            setSystemConfig($event->order->shop_id);
        }

        if ($event->order->customer_id) {
            safe_notify($event->order->customer, new OrderCreatedNotification($event->order), 'order placed — customer');
        } elseif ($event->order->email) {
            safe_mail_route_notify($event->order->email, new OrderCreatedNotification($event->order), 'order placed — guest');
        }
    }
}
