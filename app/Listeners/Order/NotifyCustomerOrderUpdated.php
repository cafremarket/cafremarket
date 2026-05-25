<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderUpdated;
use App\Notifications\Order\OrderUpdated as OrderUpdatedNotification;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Notifications\Notification;
use Notification;

class NotifyCustomerOrderUpdated implements ShouldQueue
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
    public function handle(OrderUpdated $event)
    {
        // firebase notification send to delivery body
        $deliveryBoy_token = optional($event->order->deliveryBoy)->fcm_token;

        if (! is_null($deliveryBoy_token)) {
            FCMService::send($deliveryBoy_token, [
                'title' => trans('notifications.order_updated.subject', ['order' => $event->order->order_number]),
                'body' => trans('notifications.order_updated.message', ['order' => $event->order->order_number]),
            ]);
        }

        if ($event->notify_customer) {

            $customer_token = optional($event->order->customer)->fcm_token;

            if (! is_null($customer_token)) {
                FCMService::send($customer_token, [
                    'title' => trans('notifications.order_updated.subject', ['order' => $event->order->order_number]),
                    'body' => trans('notifications.order_updated.message', ['order' => $event->order->order_number]),
                ]);
            }

            if (! config('system_settings')) {
                setSystemConfig($event->order->shop_id);
            }

            // Set shop configuration
            if ($event->order->shop_id && ! config('shop_settings')) {
                setSystemConfig($event->order->shop_id);
            }

            if ($event->order->customer_id) {
                safe_notify($event->order->customer, new OrderUpdatedNotification($event->order), 'order updated — customer');
            } elseif ($event->order->email) {
                safe_mail_route_notify($event->order->email, new OrderUpdatedNotification($event->order), 'order updated — guest');
            }
        }
    }
}
