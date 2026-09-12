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
     * Approved active shops used for browsing and nearby lists.
     */
    public function approvedShops(): Collection
    {
        return Shop::query()
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
    }

    /**
     * All approved shops without distance (guest / no-address browse).
     */
    public function allApproved(): Collection
    {
        return $this->approvedShops()
            ->map(function ($shop) {
                return [
                    'shop' => $shop,
                    'distance_km' => null,
                    'deliverable' => true,
                ];
            })
            ->values();
    }

    /**
     * Find shops near a buyer location, sorted by distance (nearest first, farthest last).
     * No radius cutoff — every shop with a resolvable location is included.
     */
    public function find(float $latitude, float $longitude): Collection
    {
        return $this->approvedShops()
            ->map(function ($shop) use ($latitude, $longitude) {
                $address = $shop->storeAddress();

                if (! $address || ! $address->latitude || ! $address->longitude) {
                    return [
                        'shop' => $shop,
                        'distance_km' => null,
                        'deliverable' => true,
                    ];
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
                    'deliverable' => $distanceKm <= $shopRadius,
                ];
            })
            ->sortBy(function ($row) {
                return $row['distance_km'] === null ? PHP_FLOAT_MAX : $row['distance_km'];
            })
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
