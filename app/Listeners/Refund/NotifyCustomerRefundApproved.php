<?php

namespace App\Listeners\Refund;

use App\Events\Refund\RefundApproved;
use App\Notifications\Refund\Approved as RefundApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerRefundApproved implements ShouldQueue
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
    public function handle(RefundApproved $event)
    {
        if (! config('system_settings')) {
            setSystemConfig($event->refund->shop_id);
        }

        // Set shop configuration
        if ($event->refund->shop_id && ! config('shop_settings')) {
            setSystemConfig($event->refund->shop_id);
        }

        $notification = new RefundApprovedNotification($event->refund);

        if ($event->refund->customer_id) {
            safe_notify($event->refund->order->customer, $notification, 'refund approved');
        } elseif ($event->refund->order->email) { // Customer is a guest
            safe_mail_route_notify($event->refund->order->email, $notification, 'refund approved guest');
        }
    }
}
