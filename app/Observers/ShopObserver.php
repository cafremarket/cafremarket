<?php

namespace App\Observers;

use App\Models\Shop;
use App\Models\User;
use App\Notifications\ShopCreated;

class ShopObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Listen to the Shop created event.
     *
     * @return void
     */
    public function created(Shop $shop)
    {
        // $user = User::find($shop->owner_id);
        // $user->shop_id = $shop->id;
        // $user->save();

        // $user->notify(new ShopCreated($shop));
    }

    /**
     * Listen to the Shop deleting event.
     *
     * @return void
     */
    public function deleting(Shop $shop)
    {
        $shop->owner()->delete();
        $shop->staffs()->delete();
    }

    /**
     * Listen to the Shop restored event.
     *
     * @return void
     */
    public function restored(Shop $shop)
    {
        $shop->owner()->restore();
        $shop->staffs()->restore();
    }
}
