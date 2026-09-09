<?php

namespace App\Services\Shop;

use App\Models\Shop;
use App\Services\Geo\DistanceService;
use Illuminate\Support\Collection;

class NearbyShopService
{
    public function __construct(private DistanceService $distance)
    {
    }

    /**
     * Find shops near a buyer location, sorted by distance (nearest first, farthest last).
     * No radius cutoff — every shop with a resolvable location is included.
     */
    public function find(float $latitude, float $longitude): Collection
    {
        $shops = Shop::query()
            ->approved()
            ->active()
            ->when(config('hyperlocal.require_inventory_for_nearby', false), function ($query) {
                $query->whereHas('inventories', function ($q) {
                    $q->where('active', 1);
                });
            })
            ->withCount([
                'inventories as active_inventories_count' => function ($q) {
                    $q->where('active', 1);
                },
            ])
            ->with([
                'logoImage',
                'config',
                'owner:id,name',
                'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                'primaryAddress',
                'addresses',
            ])
            ->get();

        return $shops
            ->map(function ($shop) use ($latitude, $longitude) {
                $address = $shop->storeAddress();

                if (! $address || ! $address->latitude || ! $address->longitude) {
                    return null;
                }

                $distanceKm = $this->distance->distanceKm(
                    $latitude,
                    $longitude,
                    (float) $address->latitude,
                    (float) $address->longitude
                );

                $shopRadius = (float) ($shop->service_radius_km ?: config('hyperlocal.default_shop_service_radius_km', 5));

                return [
                    'shop' => $shop,
                    'distance_km' => $distanceKm,
                    // Browsing has no cutoff (the shop still shows either way), but this
                    // tells the UI whether it's actually within the shop's own delivery
                    // radius — checkout blocks it otherwise, so cards should warn early.
                    'deliverable' => $distanceKm <= $shopRadius,
                ];
            })
            ->filter()
            ->sortBy('distance_km')
            ->values();
    }

    /**
     * Check if a shop can deliver to buyer coordinates.
     */
    public function isShopDeliverableTo(Shop $shop, float $latitude, float $longitude): bool
    {
        $address = $shop->storeAddress();

        if (! $address || ! $address->latitude || ! $address->longitude) {
            return false;
        }

        $distance = $this->distance->distanceKm(
            $latitude,
            $longitude,
            (float) $address->latitude,
            (float) $address->longitude
        );

        $shopRadius = (float) ($shop->service_radius_km ?: config('hyperlocal.default_shop_service_radius_km', 5));

        return $distance <= $shopRadius;
    }
}
