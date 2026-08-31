<?php

namespace App\Services\Hyperlocal;

use App\Models\Inventory;
use App\Models\Shop;
use App\Services\Shop\NearbyShopService;
use Illuminate\Support\Collection;

class HyperlocalCatalogService
{
    protected ?array $cachedShopIds = null;

    public function __construct(
        private BuyerLocationService $buyerLocation,
        private NearbyShopService $nearbyShops
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) config('hyperlocal.enabled', true);
    }

    public function requiresLocationForBrowse(): bool
    {
        return $this->isEnabled() && (bool) config('hyperlocal.require_location_for_browse', true);
    }

    /**
     * Deliverable shop IDs for the current buyer location.
     */
    public function deliverableShopIds(?float $latitude = null, ?float $longitude = null): array
    {
        if ($this->cachedShopIds !== null) {
            return $this->cachedShopIds;
        }

        $lat = $latitude ?? $this->buyerLocation->latitude();
        $lng = $longitude ?? $this->buyerLocation->longitude();

        if (! $lat || ! $lng) {
            return $this->cachedShopIds = [];
        }

        return $this->cachedShopIds = $this->nearbyShops
            ->find($lat, $lng)
            ->pluck('shop.id')
            ->filter()
            ->values()
            ->all();
    }

    public function isShopDeliverable(int $shopId): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        $lat = $this->buyerLocation->latitude();
        $lng = $this->buyerLocation->longitude();

        if (! $lat || ! $lng) {
            return false;
        }

        $shop = Shop::find($shopId);

        return $shop && $this->nearbyShops->isShopDeliverableTo($shop, $lat, $lng);
    }

    /**
     * Filter a collection of inventories to deliverable shops only.
     */
    public function filterInventories(Collection $items): Collection
    {
        if (! $this->isEnabled()) {
            return $items;
        }

        $shopIds = $this->deliverableShopIds();

        if (empty($shopIds)) {
            return collect();
        }

        return $items->filter(fn ($item) => in_array((int) $item->shop_id, $shopIds, true));
    }

    /**
     * Scope an inventory query to deliverable shops.
     */
    public function scopeInventoryQuery($query)
    {
        if (! $this->isEnabled()) {
            return $query;
        }

        $shopIds = $this->deliverableShopIds();

        if (empty($shopIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('shop_id', $shopIds);
    }

    public function nearbyFeaturedItems(int $limit = 5): Collection
    {
        $shopIds = $this->deliverableShopIds();

        if (empty($shopIds)) {
            return collect();
        }

        return get_nearby_featured_items($shopIds, $limit);
    }

    public function nearbyShopsWithDistance(): Collection
    {
        $lat = $this->buyerLocation->latitude();
        $lng = $this->buyerLocation->longitude();

        if (! $lat || ! $lng) {
            return collect();
        }

        return $this->nearbyShops->find($lat, $lng);
    }
}
