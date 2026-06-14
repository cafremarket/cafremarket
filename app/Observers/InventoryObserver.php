<?php

namespace App\Observers;

use App\Helpers\ListHelper;
use App\Models\Inventory;

class InventoryObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Inventory "created" event.
     *
     * @return void
     */
    public function created(Inventory $inventory)
    {
        $this->clearListingCaches($inventory);

        if (is_incevio_package_loaded('ebay')) {
            \Incevio\Package\Ebay\Jobs\UpdateInventory::dispatch($inventory);
        }
    }

    /**
     * Handle the Inventory "updated" event.
     *
     * @return void
     */
    public function updated(Inventory $inventory)
    {
        $this->clearListingCaches($inventory);

        if (is_incevio_package_loaded('ebay')) {
            \Incevio\Package\Ebay\Jobs\UpdateInventory::dispatch($inventory);
        }
    }

    /**
     * Handle the Inventory "deleted" event.
     *
     * @return void
     */
    public function deleted(Inventory $inventory)
    {
        $this->clearListingCaches($inventory);
    }

    /**
     * Handle the Inventory "restored" event.
     *
     * @return void
     */
    public function restored(Inventory $inventory)
    {
        //
    }

    /**
     * Handle the Inventory "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Inventory $inventory)
    {
        $this->clearListingCaches($inventory);

        if (is_incevio_package_loaded('ebay')) {
            \Incevio\Package\Ebay\Jobs\DeleteInventory::dispatch($inventory->sku);
        }
    }

    private function clearListingCaches(Inventory $inventory): void
    {
        $shopSlug = $inventory->relationLoaded('shop')
            ? $inventory->shop?->slug
            : $inventory->shop()->value('slug');

        ListHelper::clearLatestItemsCache($inventory->shop_id, $shopSlug);
    }
}
