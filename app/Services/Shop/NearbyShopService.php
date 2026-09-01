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
     * Find shops near a buyer location, sorted by distance.
     */
    public function find(float $latitude, float $longitude, ?float $radiusKm = null): Collection
    {
        $radiusKm = $radiusKm ?? $this->defaultSearchRadius();

        $distanceSql = $this->distance->haversineSql('addresses.latitude', 'addresses.longitude', $latitude, $longitude);

        $shops = Shop::query()
            ->select('shops.*')
            ->selectRaw("{$distanceSql} AS distance_km")
            ->join('addresses', function ($join) {
                $join->on('addresses.addressable_id', '=', 'shops.id')
                    ->where('addresses.addressable_type', '=', Shop::class);
            })
            ->whereNotNull('addresses.latitude')
            ->whereNotNull('addresses.longitude')
            ->approved()
            ->active()
            ->when(config('hyperlocal.require_inventory_for_nearby', false), function ($query) {
                $query->whereHas('inventories', function ($q) {
                    $q->where('active', 1)->where('available_from', '<=', now());
                });
            })
            ->withCount([
                'inventories as active_inventories_count' => function ($q) {
                    $q->where('active', 1)->where('available_from', '<=', now());
                },
            ])
            ->havingRaw('distance_km <= ?', [$radiusKm])
            ->with(['logoImage', 'config', 'owner:id,name', 'avgFeedback:rating,count,feedbackable_id,feedbackable_type'])
            ->orderBy('distance_km')
            ->get()
            ->filter(function ($shop) use ($latitude, $longitude) {
                $shopRadius = (float) ($shop->service_radius_km ?: config('hyperlocal.default_shop_service_radius_km', 5));
                $distance = (float) ($shop->distance_km ?? 999);

                return $distance <= min($shopRadius, $radiusKm);
            })
            ->values();

        return $shops->map(function ($shop) use ($latitude, $longitude) {
            $deliverable = $this->isShopDeliverableTo($shop, $latitude, $longitude);

            return [
                'shop' => $shop,
                'distance_km' => round((float) $shop->distance_km, 2),
                'deliverable' => $deliverable,
            ];
        })->filter(fn ($row) => $row['deliverable']);
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

    public function defaultSearchRadius(): float
    {
        return (float) (config('system_settings.default_buyer_search_radius_km')
            ?? config('hyperlocal.default_buyer_search_radius_km', 10));
    }
}
