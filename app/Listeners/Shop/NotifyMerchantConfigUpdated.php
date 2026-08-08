<?php

namespace App\Listeners\Shop;

use App\Events\Shop\ConfigUpdated;
use App\Notifications\Shop\ShopConfigUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyMerchantConfigUpdated implements ShouldQueue
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
    public function handle(ConfigUpdated $event)
    {
        safe_notify($event->shop->owner, new ShopConfigUpdated($event->shop, $event->user), 'shop config updated');
    }
}
