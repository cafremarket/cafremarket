<?php

namespace App\Services\Orders;

use App\Models\Inventory;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Refund/return window of each order item: the seller's period (0 = none,
 * max Inventory::REFUND_DAYS_MAX days) counted from delivery. The period is the
 * one saved on the order item at checkout, falling back to the listing's current one.
 */
class RefundWindow
{
    public const NO_REFUND = 'no_refund';

    public const NOT_DELIVERED = 'not_delivered';

    public const OPEN = 'open';

    public const CLOSED = 'closed';

    /** When the customer received the order, or null if not delivered yet. */
    public static function deliveredAt(Order $order): ?Carbon
    {
        if ($order->delivered_confirmed_at) {
            return Carbon::parse($order->delivered_confirmed_at);
        }

        if ((int) $order->order_status_id === Order::STATUS_DELIVERED || $order->goods_received) {
            return Carbon::parse($order->delivery_date ?: $order->updated_at);
        }

        return null;
    }

    /**
     * @param  Inventory  $item  an order inventory with its order_items pivot loaded
     * @return array{days: int, status: string, deadline: ?Carbon, allowed: bool, label: string}
     */
    public static function forItem(Order $order, Inventory $item, ?Carbon $now = null): array
    {
        $now = $now ?? now();
        $days = static::daysFor($item);
        $deliveredAt = static::deliveredAt($order);
        $deadline = $deliveredAt && $days > 0 ? $deliveredAt->copy()->addDays($days)->endOfDay() : null;

        if ($days <= 0) {
            $status = static::NO_REFUND;
        } elseif (! $deliveredAt) {
            $status = static::NOT_DELIVERED;
        } else {
            $status = $now->lte($deadline) ? static::OPEN : static::CLOSED;
        }

        return [
            'days' => $days,
            'status' => $status,
            'deadline' => $deadline,
            // Before delivery the usual cancel/refund flow applies; after delivery only while open.
            'allowed' => in_array($status, [static::OPEN, static::NOT_DELIVERED], true),
            'label' => static::label($status, $days, $deadline),
        ];
    }

    /**
     * Window per order item, keyed by inventory id.
     *
     * @return Collection<int, array>
     */
    public static function forOrder(Order $order): Collection
    {
        $order->loadMissing('inventories');

        return $order->inventories->mapWithKeys(fn (Inventory $item) => [
            $item->id => static::forItem($order, $item) + ['product_id' => (int) $item->product_id],
        ]);
    }

    /**
     * Why a customer may not ask for a refund/return on a received order, or null when allowed.
     * Items not delivered are always allowed ("did not receive goods" disputes are never blocked).
     */
    public static function customerRequestError(Order $order, bool $orderReceived, $productId = null): ?string
    {
        if (! $orderReceived || ! static::deliveredAt($order)) {
            return null;
        }

        $windows = static::forOrder($order);

        if ($productId) {
            $windows = $windows->where('product_id', (int) $productId);
        }

        if ($windows->isEmpty() || $windows->contains('allowed', true)) {
            return null;
        }

        $window = $windows->first();

        return $window['status'] === static::NO_REFUND
            ? trans('refund_period.error_no_refund')
            : trans('refund_period.error_closed', ['date' => $window['deadline']->format('d/m/Y')]);
    }

    public static function daysFor(Inventory $item): int
    {
        $snapshot = $item->pivot->refund_days ?? null;
        $days = $snapshot !== null ? $snapshot : ($item->refund_days ?? Inventory::REFUND_DAYS_DEFAULT);

        return max(0, min(Inventory::REFUND_DAYS_MAX, (int) $days));
    }

    public static function label(string $status, int $days, ?Carbon $deadline): string
    {
        return match ($status) {
            static::NO_REFUND => trans('refund_period.no_refund'),
            static::NOT_DELIVERED => trans_choice('refund_period.after_delivery', $days, ['count' => $days]),
            static::OPEN => trans('refund_period.open_until', ['date' => $deadline->format('d/m/Y')]),
            default => trans('refund_period.closed_on', ['date' => $deadline->format('d/m/Y')]),
        };
    }
}
