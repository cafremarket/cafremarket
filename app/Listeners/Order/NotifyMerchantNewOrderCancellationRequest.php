<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderCancellationRequestCreated;
use App\Notifications\Order\MerchantNewOrderCancellation as OrderCancellationRequest;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyMerchantNewOrderCancellationRequest implements ShouldQueue
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
    public function handle(OrderCancellationRequestCreated $event)
    {
        // if (! config('shop_settings')) {
        //     setShopConfig($event->order->shop_id);
        // }

        // if (config('shop_settings.notify_order_cancellation_request')) {
        $event->order->shop->notify(new OrderCancellationRequest($event->order));
        // }
    }
}
