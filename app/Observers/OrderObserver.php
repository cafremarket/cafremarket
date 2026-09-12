<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Listen to the order created event. This will trigger when create and update
     *
     * @param  \App\order  $order
     * @return void
     */
    public function created(Order $order)
    {
        if ($order->shop_id) {
            \App\Services\Cache\CatalogCache::bumpVendor((int) $order->shop_id);
        }
    }

    public function updated(Order $order)
    {
        if ($order->shop_id) {
            \App\Services\Cache\CatalogCache::bumpVendor((int) $order->shop_id);
        }
    }
}
