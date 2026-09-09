<?php

namespace App\Services\Delivery;

use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Services\FCMService;
use Illuminate\Support\Collection;

class DeliveryDispatchService
{
    /**
     * Assign a shop-owned delivery boy to an order.
     */
    public function assignShopRider(Order $order, DeliveryBoy $rider): Order
    {
        if ($rider->shop_id !== $order->shop_id) {
            throw new \InvalidArgumentException(trans('app.invalid_delivery_boy_for_shop'));
        }

        return $this->assignRider($order, $rider);
    }

    /**
     * Get active shop riders for a shop, available to assign to an order.
     *
     * Not filtered by is_online: nothing in the delivery app currently lets a
     * rider toggle that flag, so filtering on it would always return empty.
     */
    public function getAvailableShopRiders(int $shopId): Collection
    {
        return DeliveryBoy::query()
            ->where('shop_id', $shopId)
            ->where('status', true)
            ->get();
    }

    protected function assignRider(Order $order, DeliveryBoy $rider): Order
    {
        $order->delivery_boy_id = $rider->id;
        $order->fulfillment_method = Order::FULFILLMENT_METHOD_DELIVERY_BOY;
        $order->order_status_id = Order::STATUS_AWAITING_DELIVERY;

        // (Re)assigning starts the delivery leg fresh: a previous rider's "reached"
        // state/OTP must not carry over to whoever is assigned now — the new rider
        // hasn't reached the customer yet, and the old rider must lose access to
        // this order entirely (their app only lists orders matching their own id).
        $order->reached_at = null;
        $order->otp = null;

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
}
