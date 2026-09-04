<?php

namespace App\Services\Shipping;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Config;
use App\Models\Inventory;
use App\Models\Shop;
use App\Services\Geo\DistanceService;
use Illuminate\Support\Collection;

/**
 * Location-based shipping: free | fixed | km.
 * Cart charge = max(per-item calculated charges) for the same shop cart.
 */
class ShippingCalculator
{
    public const TYPE_FREE = 'free';

    public const TYPE_FIXED = 'fixed';

    public const TYPE_KM = 'km';

    public const TYPE_INHERIT = 'inherit';

    public function __construct(private DistanceService $distance)
    {
    }

    /**
     * Apply calculated shipping onto the cart model (mutates, does not save).
     */
    public function applyToCart(Cart $cart, ?float $destLat = null, ?float $destLng = null): Cart
    {
        if ($cart->is_digital || $cart->isPickup()) {
            $cart->shipping = 0;
            $cart->shipping_rate_id = null;

            return $cart;
        }

        $result = $this->calculateForCart($cart, $destLat, $destLng);
        $cart->shipping = round((float) $result['amount'], config('system_settings.decimals', 2));
        $cart->shipping_rate_id = null;

        return $cart;
    }

    /**
     * @return array{amount: float, distance_km: float|null, items: array, label: string}
     */
    public function calculateForCart(Cart $cart, ?float $destLat = null, ?float $destLng = null): array
    {
        $cart->loadMissing(['inventories', 'shop.config', 'shippingAddress', 'shipTo']);

        [$destLat, $destLng] = $this->resolveDestination($cart, $destLat, $destLng);
        $shop = $cart->shop;
        $config = optional($shop)->config;
        $distanceKm = $this->distanceFromShop($shop, $destLat, $destLng);

        $itemAmounts = [];
        $max = 0.0;

        foreach ($cart->inventories as $item) {
            $charge = $this->calculateForItem($item, $config, $distanceKm);
            $itemAmounts[$item->id] = $charge;
            if ($charge > $max) {
                $max = $charge;
            }
        }

        // Empty cart edge case
        if ($cart->inventories->isEmpty()) {
            $max = 0.0;
        }

        return [
            'amount' => round($max, 6),
            'distance_km' => $distanceKm,
            'items' => $itemAmounts,
            'label' => $this->labelForAmount($max, $distanceKm),
        ];
    }

    /**
     * Single inventory / PDP estimate.
     */
    public function calculateForItem(Inventory $item, ?Config $shopConfig, ?float $distanceKm): float
    {
        $resolved = $this->resolveItemSettings($item, $shopConfig);

        return match ($resolved['type']) {
            self::TYPE_FREE => 0.0,
            self::TYPE_FIXED => max(0.0, (float) ($resolved['fixed_rate'] ?? 0)),
            self::TYPE_KM => $this->kmCharge(
                $distanceKm,
                (float) ($resolved['per_km_rate'] ?? 0),
                (float) ($resolved['base_fee'] ?? 0)
            ),
            default => max(0.0, (float) ($resolved['fixed_rate'] ?? 0)),
        };
    }

    /**
     * @return array{type: string, fixed_rate: ?float, per_km_rate: ?float, base_fee: ?float}
     */
    public function resolveItemSettings(Inventory $item, ?Config $shopConfig): array
    {
        $type = strtolower(trim((string) ($item->shipping_type ?? '')));

        // Legacy free_shipping flag
        if ($type === '' || $type === self::TYPE_INHERIT) {
            if ($item->free_shipping) {
                return [
                    'type' => self::TYPE_FREE,
                    'fixed_rate' => 0,
                    'per_km_rate' => 0,
                    'base_fee' => 0,
                ];
            }

            return [
                'type' => $this->normalizeShopType($shopConfig?->shipping_type),
                'fixed_rate' => $shopConfig?->shipping_fixed_rate,
                'per_km_rate' => $shopConfig?->shipping_per_km_rate,
                'base_fee' => $shopConfig?->shipping_base_fee ?? 0,
            ];
        }

        if ($type === self::TYPE_FREE || $item->free_shipping) {
            return [
                'type' => self::TYPE_FREE,
                'fixed_rate' => 0,
                'per_km_rate' => 0,
                'base_fee' => 0,
            ];
        }

        return [
            'type' => in_array($type, [self::TYPE_FIXED, self::TYPE_KM], true) ? $type : self::TYPE_FIXED,
            'fixed_rate' => $item->shipping_fixed_rate ?? $shopConfig?->shipping_fixed_rate,
            'per_km_rate' => $item->shipping_per_km_rate ?? $shopConfig?->shipping_per_km_rate,
            'base_fee' => $item->shipping_base_fee ?? $shopConfig?->shipping_base_fee ?? 0,
        ];
    }

    public function kmCharge(?float $distanceKm, float $perKm, float $baseFee = 0): float
    {
        if ($distanceKm === null) {
            // No destination/shop coords yet — charge base only (or 0).
            return max(0.0, $baseFee);
        }

        $km = max(0.0, $distanceKm);

        return max(0.0, $baseFee + ($km * max(0.0, $perKm)));
    }

    public function distanceFromShop(?Shop $shop, ?float $destLat, ?float $destLng): ?float
    {
        if (! $shop || $destLat === null || $destLng === null) {
            return null;
        }

        $store = $shop->storeAddress();
        if (! $store || ! $store->latitude || ! $store->longitude) {
            return null;
        }

        return $this->distance->distanceKm(
            (float) $store->latitude,
            (float) $store->longitude,
            (float) $destLat,
            (float) $destLng
        );
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    public function resolveDestination(Cart $cart, ?float $destLat = null, ?float $destLng = null): array
    {
        if ($destLat !== null && $destLng !== null) {
            return [$destLat, $destLng];
        }

        $address = null;
        if ($cart->relationLoaded('shippingAddress') && $cart->shippingAddress) {
            $address = $cart->shippingAddress;
        } elseif ($cart->relationLoaded('shipTo') && $cart->shipTo) {
            $address = $cart->shipTo;
        } elseif ($cart->ship_to) {
            $address = Address::find($cart->ship_to);
        }

        if ($address && $address->latitude && $address->longitude) {
            return [(float) $address->latitude, (float) $address->longitude];
        }

        // Buyer selected delivery location (header / location picker)
        $buyer = app(\App\Services\Hyperlocal\BuyerLocationService::class);
        $buyer->ensureDeliveryLocation();
        if ($buyer->hasLocation()) {
            return [$buyer->latitude(), $buyer->longitude()];
        }

        return [null, null];
    }

    public function shippingOptionsPayload(Cart $cart, ?float $destLat = null, ?float $destLng = null): Collection
    {
        $result = $this->calculateForCart($cart, $destLat, $destLng);

        return collect([(object) [
            'id' => 'location',
            'name' => $result['label'],
            'shipping_zone_id' => null,
            'carrier_id' => null,
            'carrier_name' => trans('app.shipping') ?? 'Shipping',
            'rate' => $result['amount'],
            'delivery_takes' => $result['distance_km'] !== null
                ? round($result['distance_km'], 1).' km'
                : null,
            'distance_km' => $result['distance_km'],
        ]]);
    }

    protected function normalizeShopType(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, [self::TYPE_FREE, self::TYPE_FIXED, self::TYPE_KM], true)
            ? $type
            : self::TYPE_FIXED;
    }

    protected function labelForAmount(float $amount, ?float $distanceKm): string
    {
        if ($amount <= 0) {
            return trans('theme.free_shipping') ?: 'Free shipping';
        }

        if ($distanceKm !== null) {
            return (trans('app.shipping') ?: 'Shipping').' ('.round($distanceKm, 1).' km)';
        }

        return trans('app.shipping') ?: 'Shipping';
    }
}
