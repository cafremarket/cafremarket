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
 * Cart charge = sum of each product's calculated shipping charge.
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
     * @return array{amount: float, distance_km: float|null, items: array<int, array>, label: string}
     */
    public function calculateForCart(Cart $cart, ?float $destLat = null, ?float $destLng = null): array
    {
        $cart->loadMissing(['inventories', 'shop.config', 'shippingAddress', 'shipTo']);

        [$destLat, $destLng] = $this->resolveDestination($cart, $destLat, $destLng);
        $shop = $cart->shop;
        $config = optional($shop)->config;
        $distanceKm = $this->distanceFromShop($shop, $destLat, $destLng);

        $itemAmounts = [];
        $sum = 0.0;

        foreach ($cart->inventories as $item) {
            $unitCharge = $this->calculateForItem($item, $config, $distanceKm);
            $qty = max(1, (int) ($item->pivot->quantity ?? 1));
            // Charge once per cart line (product), not multiplied by qty —
            // matches prior per-item rate semantics while summing every line.
            $charge = round($unitCharge, 6);
            $sum += $charge;

            $title = $item->pivot->item_description
                ?? $item->title
                ?? ('#'.$item->id);

            $itemAmounts[] = [
                'inventory_id' => (int) $item->id,
                'title' => strip_tags((string) $title),
                'quantity' => $qty,
                'unit_amount' => $unitCharge,
                'amount' => $charge,
                'shop_id' => $shop?->id,
                'shop_name' => $shop?->name,
            ];
        }

        if ($cart->inventories->isEmpty()) {
            $sum = 0.0;
            $itemAmounts = [];
        }

        return [
            'amount' => round($sum, 6),
            'distance_km' => $distanceKm,
            'items' => $itemAmounts,
            'label' => $this->labelForAmount($sum, $distanceKm),
        ];
    }

    /**
     * API / UI friendly breakdown rows for a cart (products + handling).
     *
     * @return array<int, array{inventory_id: ?int, title: string, quantity: int, amount: string, amount_raw: string, shop_name: ?string}>
     */
    public function breakdownForCart(Cart $cart, ?float $destLat = null, ?float $destLng = null): array
    {
        if ($cart->is_digital || $cart->isPickup()) {
            return [];
        }

        $decimal = config('system_settings.decimals', 2);
        $result = $this->calculateForCart($cart, $destLat, $destLng);
        $lines = [];

        foreach ($result['items'] as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $lines[] = [
                'inventory_id' => $row['inventory_id'] ?? null,
                'title' => $row['title'] ?? '',
                'quantity' => (int) ($row['quantity'] ?? 1),
                'amount' => get_formated_currency($amount, $decimal),
                'amount_raw' => strval(round($amount, 2)),
                'shop_name' => $row['shop_name'] ?? null,
            ];
        }

        $handling = (float) ($cart->handling ?? 0);
        if ($handling > 0) {
            $lines[] = [
                'inventory_id' => null,
                'title' => trans('theme.handling') ?: 'Handling',
                'quantity' => 1,
                'amount' => get_formated_currency($handling, $decimal),
                'amount_raw' => strval(round($handling, 2)),
                'shop_name' => optional($cart->shop)->name,
            ];
        }

        return $lines;
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
            'delivery_takes' => null,
            'distance_km' => $result['distance_km'],
            'items' => $result['items'],
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

        return trans('app.shipping') ?: 'Shipping';
    }
}
