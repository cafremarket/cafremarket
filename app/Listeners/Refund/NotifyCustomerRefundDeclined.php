<?php

namespace App\Listeners\Refund;

use App\Events\Refund\RefundDeclined;
use App\Notifications\Refund\Declined as RefundDeclinedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerRefundDeclined implements ShouldQueue
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
    public function handle(RefundDeclined $event)
    {
        if (! config('system_settings')) {
            setSystemConfig($event->refund->shop_id);
        }
        // Set shop configuration
        if ($event->refund->shop_id && ! config('shop_settings')) {
            setSystemConfig($event->refund->shop_id);
        }

        $notification = new RefundDeclinedNotification($event->refund);

        if ($event->refund->customer_id) {
            safe_notify($event->refund->order->customer, $notification, 'refund declined');
        } elseif ($event->refund->order->email) {  // Customer is a guest
            safe_mail_route_notify($event->refund->order->email, $notification, 'refund declined guest');
        }
    }
}
