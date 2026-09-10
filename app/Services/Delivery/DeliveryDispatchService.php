<?php

namespace App\Services\Delivery;

use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Services\FCMService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryDispatchService
{
    /**
     * Assign a shop-owned delivery boy to an order.
     *
     * Re-fetches the order under a row lock and re-checks isDelivered()
     * against that locked read rather than trusting the caller's (possibly
     * stale) $order instance — otherwise a request that started before a
     * concurrent delivery confirmation lands can still overwrite it: it
     * passes its own (now stale) isDelivered() check, then saves over the
     * confirmed state a moment later.
     */
    public function assignShopRider(Order $order, DeliveryBoy $rider): Order
    {
        return DB::transaction(function () use ($order, $rider) {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isDelivered()) {
                throw new \InvalidArgumentException(trans('app.order_already_delivered'));
            }

            if ($rider->shop_id !== $locked->shop_id) {
                throw new \InvalidArgumentException(trans('app.invalid_delivery_boy_for_shop'));
            }

            return $this->assignRider($locked, $rider);
        });
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
