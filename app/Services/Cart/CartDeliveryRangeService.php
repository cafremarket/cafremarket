<?php

namespace App\Services\Cart;

use App\Models\Address;
use App\Services\Geo\DistanceService;
use App\Services\Hyperlocal\BuyerLocationService;
use App\Services\Hyperlocal\HyperlocalCatalogService;
use Illuminate\Support\Collection;

class CartDeliveryRangeService
{
    /**
     * Flag each cart as in/out of the shop service radius for the buyer location.
     *
     * @param  Collection|iterable  $carts
     */
    public function annotate($carts): void
    {
        $catalog = app(HyperlocalCatalogService::class);
        $buyer = app(BuyerLocationService::class);
        $buyer->ensureDeliveryLocation();

        foreach ($carts as $cart) {
            $cart->out_of_range = false;
            $cart->needs_delivery_location = false;
            $cart->delivery_distance_km = null;
            $cart->service_radius_km = null;

            if ($cart->is_digital || ! $catalog->isEnabled() || ! $cart->shop) {
                continue;
            }

            $lat = $buyer->latitude();
            $lng = $buyer->longitude();

            // Prefer explicit ship-to address coordinates when present.
            $shipTo = $cart->relationLoaded('shippingAddress')
                ? $cart->shippingAddress
                : ($cart->ship_to ? Address::find($cart->ship_to) : null);
            if ($shipTo && $shipTo->latitude && $shipTo->longitude) {
                $lat = (float) $shipTo->latitude;
                $lng = (float) $shipTo->longitude;
            }

            $shop = $cart->shop;
            $store = $shop->storeAddress();
            $radius = (float) ($shop->service_radius_km ?: config('hyperlocal.default_shop_service_radius_km', 5));
            $cart->service_radius_km = $radius;

            if (! $lat || ! $lng) {
                $cart->needs_delivery_location = true;

                continue;
            }

            if (! $store || ! $store->latitude || ! $store->longitude) {
                $cart->out_of_range = true;

                continue;
            }

            $distance = app(DistanceService::class)->distanceKm(
                (float) $store->latitude,
                (float) $store->longitude,
                (float) $lat,
                (float) $lng
            );
            $cart->delivery_distance_km = round($distance, 1);
            $cart->out_of_range = $distance > $radius;
        }
    }

    /**
     * Whether checkout should be blocked for this cart.
     */
    public function isBlocked($cart): bool
    {
        $this->annotate(collect([$cart]));

        return ! empty($cart->out_of_range) || ! empty($cart->needs_delivery_location);
    }
}
