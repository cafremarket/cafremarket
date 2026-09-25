<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPaid;
use App\Notifications\Order\MerchantOrderPaid;
use App\Services\FCMService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Tells the seller (vendor app push + in-app notification list) that an
 * order's payment was confirmed, showing the order number as in chat.
 */
class NotifyMerchantOrderPaid implements ShouldQueue
{
    public $tries = 5;

    public function handle(OrderPaid $event)
    {
        $order = $event->order;
        $order->loadMissing(['shop', 'customer']);

        if (! $order->shop) {
            return;
        }

        if (! config('shop_settings')) {
            setShopConfig($order->shop_id);
        }

        FCMService::sendToShop($order->shop, [
            'title' => MerchantOrderPaid::subject($order),
            'body' => MerchantOrderPaid::message($order),
        ], [
            'type' => 'order_paid',
            'order_id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
        ]);

        safe_notify($order->shop, new MerchantOrderPaid($order), 'order paid — merchant');
    }
}
