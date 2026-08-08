<?php

namespace App\Listeners\Refund;

use App\Events\Refund\RefundInitiated;
use App\Models\Refund;
use App\Notifications\Refund\Approved as RefundApprovedNotification;
use App\Notifications\Refund\Declined as RefundDeclinedNotification;
use App\Notifications\Refund\Initiated as RefundInitiatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerRefundInitiated implements ShouldQueue
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
    public function handle(RefundInitiated $event)
    {
        if ($event->notify_customer) {
            if (! config('system_settings')) {
                setSystemConfig($event->refund->shop_id);
            }

            // Set shop configuration
            if ($event->refund->shop_id && ! config('shop_settings')) {
                setSystemConfig($event->refund->shop_id);
            }

            if ($event->refund->status == Refund::STATUS_APPROVED) {
                $notification = new RefundApprovedNotification($event->refund);
            } elseif ($event->refund->status == Refund::STATUS_DECLINED) {
                $notification = new RefundDeclinedNotification($event->refund);
            } else {
                $notification = new RefundInitiatedNotification($event->refund);
            }

            if ($event->refund->customer_id) {
                safe_notify($event->refund->order->customer, $notification, 'refund initiated');
            } elseif ($event->refund->order->email) {  // Customer is a guest
                safe_mail_route_notify($event->refund->order->email, $notification, 'refund initiated guest');
            }
        }
    }
}
