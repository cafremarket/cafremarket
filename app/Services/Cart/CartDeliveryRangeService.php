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

            // Delivery radius is informational only — it must never block cart
            // or checkout, so out_of_range always stays false. Distance is
            // still computed (when available) purely for display.
            if (! $lat || ! $lng || ! $store || ! $store->latitude || ! $store->longitude) {
                continue;
            }

            $distance = app(DistanceService::class)->distanceKm(
                (float) $store->latitude,
                (float) $store->longitude,
                (float) $lat,
                (float) $lng
            );
            $cart->delivery_distance_km = round($distance, 1);
        }

        // Annotations are for API/UI checks only — never persist to carts table.
        foreach ($carts as $cart) {
            foreach (\App\Models\Cart::DELIVERY_RANGE_RUNTIME_ATTRIBUTES as $attribute) {
                if (array_key_exists($attribute, $cart->getAttributes())) {
                    $cart->syncOriginalAttribute($attribute);
                }
            }
        }
    }

    /**
     * Whether checkout should be blocked for this cart.
     */
    public function isBlocked($cart): bool
    {
        $this->annotate(collect([$cart]));

        // Radius is informational only and must not block checkout.
        return false;
    }
}
