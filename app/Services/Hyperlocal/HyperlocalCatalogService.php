<?php

namespace App\Services\Hyperlocal;

use App\Models\Inventory;
use App\Models\Shop;
use App\Services\Shop\NearbyShopService;
use Illuminate\Pagination\LengthAwarePaginator;
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

        return $this->sortByShopDistance(get_nearby_featured_items($shopIds))
            ->take($limit)
            ->values();
    }

    /**
     * Distance (km) of each shop from the buyer, keyed by shop id.
     */
    public function shopDistances(): Collection
    {
        return $this->nearbyShopsWithDistance()->pluck('distance_km', 'shop.id');
    }

    /**
     * Distance (km) from the buyer to a single shop. Cheaper than shopDistances()
     * for single-item pages (product page, quick view) that only need one shop —
     * it does not fetch/sort every nearby shop.
     */
    public function shopDistance(int $shopId): ?float
    {
        $lat = $this->buyerLocation->latitude();
        $lng = $this->buyerLocation->longitude();

        if (! $lat || ! $lng) {
            return null;
        }

        $shop = Shop::find($shopId);

        if (! $shop) {
            return null;
        }

        $address = $shop->storeAddress();

        if (! $address || ! $address->latitude || ! $address->longitude) {
            return null;
        }

        return app(\App\Services\Geo\DistanceService::class)->distanceKm(
            $lat,
            $lng,
            (float) $address->latitude,
            (float) $address->longitude
        );
    }

    /**
     * Order a collection of inventories by their shop's distance from the buyer:
     * nearest store's products first, farthest store's products last (or the
     * reverse, with $descending). Items whose shop has no known distance are
     * always pushed to the end, in their original order.
     */
    public function sortByShopDistance(Collection $items, bool $descending = false): Collection
    {
        $distances = $this->shopDistances();

        if ($distances->isEmpty()) {
            return $items->values();
        }

        // Stable sort (PHP 8+ sort functions are stable) keeps items with an
        // unknown distance — or equal distances — in their original relative order.
        // Unknown-distance items sort last regardless of direction, by ranking
        // them past every real distance on both ends of the sortBy comparator.
        return $items
            ->values()
            ->sortBy(function ($item) use ($distances, $descending) {
                $distance = $distances->get($item->shop_id);

                if ($distance === null) {
                    return PHP_FLOAT_MAX;
                }

                return $descending ? -$distance : $distance;
            })
            ->values();
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

    /**
     * Paginated nearby shops for homepage / inline store listings.
     */
    public function nearbyShopsPaginated(?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) config('hyperlocal.home_stores_per_page', 8);
        $results = $this->nearbyShopsWithDistance();
        $page = max(1, (int) request()->get('stores_page', 1));
        $items = $results->forPage($page, $perPage)->values();

        return (new LengthAwarePaginator(
            $items,
            $results->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'stores_page',
            ]
        ))->withQueryString()->fragment('nearby-stores');
    }
}
