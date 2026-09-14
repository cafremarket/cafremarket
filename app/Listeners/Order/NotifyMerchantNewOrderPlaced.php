<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderCreated;
use App\Notifications\Order\MerchantOrderCreatedNotification as OrderCreatedNotification;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyMerchantNewOrderPlaced implements ShouldQueue
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
    public function handle(OrderCreated $event)
    {
        if (! config('shop_settings')) {
            setShopConfig($event->order->shop_id);
        }

        $order = $event->order;
        $order->loadMissing(['shop', 'warehouse.manager']);
        $orderNumber = $order->order_number;
        $data = [
            'type' => 'order',
            'order_id' => (int) $order->id,
            'order_number' => (string) $orderNumber,
            'status' => (string) $order->orderStatus(true),
        ];
        $notification = [
            'title' => trans('notifications.merchant_order_created_notification.subject', [
                'order' => $orderNumber,
            ]),
            'body' => trans('notifications.merchant_order_created_notification.message', [
                'order' => $orderNumber,
            ]),
        ];

        // Always push to vendor app (independent of email shop setting)
        FCMService::sendToShop($order->shop, $notification, $data);

        $warehouseToken = ! is_null($order->warehouse)
            ? optional($order->warehouse->manager)->fcm_token
            : null;
        if (! is_null($warehouseToken)) {
            FCMService::send($warehouseToken, $notification, 'vendor', $data);
        }

        if (config('shop_settings.notify_new_order')) {
            safe_notify($order->shop, new OrderCreatedNotification($order), 'order placed — merchant');
        }
    }
}
