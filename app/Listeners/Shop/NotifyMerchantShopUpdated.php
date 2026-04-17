<?php

namespace App\Listeners\Shop;

use App\Events\Shop\ShopUpdated;
use App\Notifications\Shop\ShopUpdated as ShopUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyMerchantShopUpdated implements ShouldQueue
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
    public function handle(ShopUpdated $event)
    {
        $event->shop->owner->notify(new ShopUpdatedNotification($event->shop));
    }
}
