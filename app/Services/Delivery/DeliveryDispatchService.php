<?php

namespace App\Services\Delivery;

use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Models\Shop;
use App\Services\FCMService;
use App\Services\Geo\DistanceService;
use Illuminate\Support\Collection;

class DeliveryDispatchService
{
    public const MODE_SHOP = 'shop';
    public const MODE_SYSTEM = 'system';

    public function __construct(private DistanceService $distance)
    {
    }

    /**
     * Assign a shop-owned delivery boy to an order.
     */
    public function assignShopRider(Order $order, DeliveryBoy $rider): Order
    {
        if ($rider->type !== DeliveryBoy::TYPE_SHOP || $rider->shop_id !== $order->shop_id) {
            throw new \InvalidArgumentException(trans('app.invalid_delivery_boy_for_shop'));
        }

        return $this->assignRider($order, $rider, self::MODE_SHOP);
    }

    /**
     * Assign a platform delivery boy to an order.
     */
    public function assignPlatformRider(Order $order, DeliveryBoy $rider): Order
    {
        if ($rider->type !== DeliveryBoy::TYPE_PLATFORM) {
            throw new \InvalidArgumentException(trans('app.invalid_platform_rider'));
        }

        return $this->assignRider($order, $rider, self::MODE_SYSTEM);
    }

    /**
     * Find online platform riders near a shop.
     */
    public function findNearbyPlatformRiders(Shop $shop, ?float $radiusKm = null): Collection
    {
        $shopAddress = $shop->storeAddress();

        if (! $shopAddress || ! $shopAddress->latitude || ! $shopAddress->longitude) {
            return collect();
        }

        $radiusKm = $radiusKm ?? $this->maxDispatchRadius();
        $lat = (float) $shopAddress->latitude;
        $lng = (float) $shopAddress->longitude;

        return DeliveryBoy::query()
            ->where('type', DeliveryBoy::TYPE_PLATFORM)
            ->where('is_online', true)
            ->where('status', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get()
            ->map(function ($rider) use ($lat, $lng) {
                $rider->distance_km = $this->distance->distanceKm(
                    $lat,
                    $lng,
                    (float) $rider->current_latitude,
                    (float) $rider->current_longitude
                );

                return $rider;
            })
            ->filter(fn ($rider) => $rider->distance_km <= $radiusKm)
            ->sortBy('distance_km')
            ->values();
    }

    /**
     * Get online shop riders for a shop.
     */
    public function getAvailableShopRiders(int $shopId): Collection
    {
        return DeliveryBoy::query()
            ->where('type', DeliveryBoy::TYPE_SHOP)
            ->where('shop_id', $shopId)
            ->where('is_online', true)
            ->where('status', true)
            ->get();
    }

    /**
     * Request platform delivery: notify nearest riders or auto-assign first.
     */
    public function requestPlatformDelivery(Order $order, ?int $riderId = null): Order
    {
        $shop = $order->shop;

        if (! in_array($shop->delivery_capability, ['system_only', 'both'], true)) {
            throw new \InvalidArgumentException(trans('app.shop_does_not_support_platform_delivery'));
        }

        if ($riderId) {
            $rider = DeliveryBoy::findOrFail($riderId);

            return $this->assignPlatformRider($order, $rider);
        }

        $riders = $this->findNearbyPlatformRiders($shop);

        if ($riders->isEmpty()) {
            throw new \RuntimeException(trans('app.no_platform_riders_available'));
        }

        $rider = $riders->first();

        return $this->assignPlatformRider($order, $rider);
    }

    protected function assignRider(Order $order, DeliveryBoy $rider, string $mode): Order
    {
        $order->delivery_boy_id = $rider->id;
        $order->delivery_mode = $mode;
        $order->delivery_assigned_at = now();
        $order->order_status_id = Order::STATUS_AWAITING_DELIVERY;
        $order->save();

        $token = $rider->fcm_token;

        if ($token) {
            FCMService::send($token, [
                'title' => trans('notifications.order_assigned.subject', ['order' => $order->order_number]),
                'body' => trans('notifications.order_assigned.message'),
            ], 'delivery');
        }

        return $order->fresh();
    }

    public function maxDispatchRadius(): float
    {
        return (float) (config('system_settings.max_delivery_assignment_radius_km')
            ?? config('hyperlocal.max_delivery_assignment_radius_km', 15));
    }
}
