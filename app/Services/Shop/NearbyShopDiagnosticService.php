<?php

namespace App\Services\Shop;

use App\Models\Shop;
use App\Services\Geo\DistanceService;

class NearbyShopDiagnosticService
{
    public function __construct(
        private NearbyShopService $nearbyShops,
        private DistanceService $distance
    ) {
    }

    public function analyze(?float $latitude, ?float $longitude, ?float $radiusKm = null): array
    {
        $radiusKm = $radiusKm ?? $this->nearbyShops->defaultSearchRadius();

        $nearbyIds = collect();
        if ($latitude !== null && $longitude !== null) {
            $nearbyIds = $this->nearbyShops
                ->find($latitude, $longitude, $radiusKm)
                ->pluck('shop.id')
                ->map(fn ($id) => (int) $id);
        }

        $shops = Shop::query()
            ->with(['config:shop_id,maintenance_mode,active_ecommerce,pending_verification'])
            ->withCount([
                'inventories as active_inventories_count' => function ($q) {
                    $q->where('active', 1)->where('available_from', '<=', now());
                },
            ])
            ->orderBy('name')
            ->get();

        $rows = $shops->map(function (Shop $shop) use ($latitude, $longitude, $radiusKm, $nearbyIds) {
            return $this->analyzeShop(
                $shop,
                $latitude,
                $longitude,
                $radiusKm,
                $nearbyIds->contains((int) $shop->id)
            );
        });

        if ($latitude !== null && $longitude !== null) {
            $rows = $rows->sortBy(fn ($row) => $row['distance_km'] ?? 999999)->values();
        }

        return [
            'radius_km' => $radiusKm,
            'default_shop_radius_km' => (float) config('hyperlocal.default_shop_service_radius_km', 5),
            'shops' => $rows,
            'summary' => [
                'total' => $rows->count(),
                'showing_nearby' => $rows->where('shows_in_nearby', true)->count(),
                'with_location' => $rows->where('has_location', true)->count(),
                'active_scope' => $rows->where('passes_active_scope', true)->count(),
                'with_inventory' => $rows->where('has_active_inventory', true)->count(),
            ],
        ];
    }

    protected function analyzeShop(
        Shop $shop,
        ?float $latitude,
        ?float $longitude,
        float $radiusKm,
        bool $showsInNearby
    ): array {
        $address = $shop->storeAddress();
        $hasLocation = $address && $address->latitude && $address->longitude;
        $shopRadius = (float) ($shop->service_radius_km ?: config('hyperlocal.default_shop_service_radius_km', 5));
        $distanceKm = null;
        $withinBuyerRadius = null;
        $withinShopRadius = null;

        if ($hasLocation && $latitude !== null && $longitude !== null) {
            $distanceKm = $this->distance->distanceKm(
                $latitude,
                $longitude,
                (float) $address->latitude,
                (float) $address->longitude
            );
            $withinBuyerRadius = $distanceKm <= $radiusKm;
            $withinShopRadius = $distanceKm <= min($shopRadius, $radiusKm);
        }

        $issues = $this->collectIssues($shop, $hasLocation, $distanceKm, $radiusKm, $shopRadius);

        return [
            'id' => $shop->id,
            'name' => $shop->name,
            'slug' => $shop->slug,
            'active' => (bool) $shop->active,
            'verified' => $shop->isVerified(),
            'has_location' => $hasLocation,
            'latitude' => $hasLocation ? (float) $address->latitude : null,
            'longitude' => $hasLocation ? (float) $address->longitude : null,
            'address_line' => $hasLocation ? $address->address_line_1 : null,
            'city' => $hasLocation ? $address->city : null,
            'active_inventories_count' => (int) $shop->active_inventories_count,
            'has_active_inventory' => (int) $shop->active_inventories_count > 0,
            'service_radius_km' => $shopRadius,
            'config_live' => $shop->config && ! $shop->config->maintenance_mode,
            'config_ecommerce' => $shop->config && (bool) $shop->config->active_ecommerce,
            'passes_active_scope' => Shop::query()->active()->where('shops.id', $shop->id)->exists(),
            'distance_km' => $distanceKm,
            'within_buyer_radius' => $withinBuyerRadius,
            'within_shop_radius' => $withinShopRadius,
            'shows_in_nearby' => $showsInNearby,
            'issues' => $issues,
        ];
    }

    protected function collectIssues(
        Shop $shop,
        bool $hasLocation,
        ?float $distanceKm,
        float $radiusKm,
        float $shopRadius
    ): array {
        $issues = [];

        if (! $shop->active) {
            $issues[] = 'Shop is inactive (active = 0)';
        }

        if (! $hasLocation) {
            $issues[] = 'No store location (latitude/longitude missing on primary address)';
        }

        if ((int) $shop->active_inventories_count === 0) {
            $issues[] = 'No active products/inventory listed';
        }

        if ($shop->config && $shop->config->maintenance_mode) {
            $issues[] = 'Shop is in maintenance mode';
        }

        if ($shop->config && ! $shop->config->active_ecommerce) {
            $issues[] = 'E-commerce is disabled for this shop';
        }

        if (vendor_get_paid_directly() && ! $shop->paymentMethods()->exists()) {
            $issues[] = 'No payment method configured';
        }

        if (is_subscription_enabled()) {
            if (! $shop->current_billing_plan) {
                $issues[] = 'No subscription plan selected';
            }
        }

        if ($distanceKm !== null) {
            if ($distanceKm > $radiusKm) {
                $issues[] = sprintf('Outside buyer search radius (%.1f km > %.1f km)', $distanceKm, $radiusKm);
            } elseif ($distanceKm > $shopRadius) {
                $issues[] = sprintf('Outside shop service radius (%.1f km > %.1f km)', $distanceKm, $shopRadius);
            }
        }

        if (empty($issues) && ! Shop::query()->active()->where('shops.id', $shop->id)->exists()) {
            $issues[] = 'Does not pass active shop scope (check config, address, payment, or subscription)';
        }

        return $issues;
    }
}
