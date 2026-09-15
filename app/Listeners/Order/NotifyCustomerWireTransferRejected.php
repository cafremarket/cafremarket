<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderWireTransferRejected;
use App\Notifications\Order\WireTransferRejected as WireTransferRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Notification;

class NotifyCustomerWireTransferRejected implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(OrderWireTransferRejected $event)
    {
        if (! config('system_settings')) {
            setSystemConfig($event->order->shop_id);
        }

        if ($event->order->shop_id && ! config('shop_settings')) {
            setSystemConfig($event->order->shop_id);
        }

        if ($event->order->customer_id) {
            safe_notify($event->order->customer, new WireTransferRejectedNotification($event->order), 'wire transfer rejected — customer');
        } elseif ($event->order->email) {
            safe_mail_route_notify($event->order->email, new WireTransferRejectedNotification($event->order), 'wire transfer rejected — guest');
        }
    }
}
