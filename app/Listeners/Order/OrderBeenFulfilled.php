<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderFulfilled;
use App\Notifications\Order\OrderFulfilled as OrderFulfilledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Notifications\Notification;
use Notification;

class OrderBeenFulfilled implements ShouldQueue
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
    public function handle(OrderFulfilled $event)
    {
        if ($event->notify_customer) {
            if (! config('system_settings')) {
                setSystemConfig($event->order->shop_id);
            }

            // Set shop configuration
            if ($event->order->shop_id && ! config('shop_settings')) {
                setSystemConfig($event->order->shop_id);
            }

            if ($event->order->customer_id) {
                safe_notify($event->order->customer, new OrderFulfilledNotification($event->order), 'order fulfilled — customer');
            } elseif ($event->order->email) {
                safe_mail_route_notify($event->order->email, new OrderFulfilledNotification($event->order), 'order fulfilled — guest');
            }
        }
    }
}
