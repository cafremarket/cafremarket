<?php

namespace App\Services\Review;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;

/**
 * Determines whether a customer is allowed to write (or edit) a product/store review.
 *
 * Reviews are product/store based, not order based: a customer gets at most one review
 * per product and one per shop, no matter how many times they've purchased it. Eligibility
 * is "has this customer ever had a delivered order containing this product / from this
 * shop" - it stays true even after they've already reviewed, since writing again just
 * edits their existing review (see ReviewService).
 */
class ReviewEligibilityService
{
    /**
     * Find a delivered order-item proving this customer purchased this product. When
     * $order is given, the search is constrained to that specific order (the existing
     * "leave feedback after this order" flow); otherwise the most recent qualifying
     * order-item across all the customer's orders is used (writing directly from the
     * product page). The returned OrderItem is purely informational (for the review's
     * optional order_id) - it does not gate re-reviewing.
     */
    public function canReviewProduct(Customer $customer, Inventory $inventory, ?Order $order = null): ?OrderItem
    {
        return OrderItem::query()
            ->where('order_items.inventory_id', $inventory->id)
            ->when($order, fn ($query) => $query->where('order_items.order_id', $order->id))
            ->whereHas('order', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)
                    ->where('order_status_id', Order::STATUS_DELIVERED);
            })
            ->latest('order_items.created_at')
            ->first();
    }

    /**
     * Find a delivered order proving this customer purchased from this shop. When $order
     * is given, the search is constrained to that specific order; otherwise the most
     * recent qualifying order across all the customer's orders with this shop is used.
     * Purely informational (for the review's optional order_id) - it does not gate
     * re-reviewing.
     */
    public function canReviewStore(Customer $customer, Shop $shop, ?Order $order = null): ?Order
    {
        return Order::query()
            ->where('shop_id', $shop->id)
            ->where('customer_id', $customer->id)
            ->where('order_status_id', Order::STATUS_DELIVERED)
            ->when($order, fn ($query) => $query->where('id', $order->id))
            ->latest('created_at')
            ->first();
    }
}
