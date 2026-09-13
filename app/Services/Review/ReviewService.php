<?php

namespace App\Services\Review;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Review;
use App\Models\ReviewSummary;
use App\Models\Shop;
use Illuminate\Validation\ValidationException;

/**
 * Creates or edits a customer's review for a product/shop. Reviews are product/store
 * based: a customer has at most one review per reviewable, so writing again from any
 * entry point (post-order feedback screen, or directly from the product/shop page)
 * edits their existing review rather than creating a new one.
 */
class ReviewService
{
    public function __construct(private ReviewEligibilityService $eligibility)
    {
    }

    /**
     * @param  array{rating: int, comment?: string|null, images?: array}  $data
     */
    public function createProductReview(Customer $customer, Inventory $inventory, array $data, ?Order $order = null): Review
    {
        $orderItem = $this->eligibility->canReviewProduct($customer, $inventory, $order);

        if (! $orderItem) {
            throw ValidationException::withMessages([
                'rating' => trans('theme.validation.not_eligible_for_review') ?? 'You can only review products you have purchased and received.',
            ]);
        }

        $review = $this->findOrNewIncludingTrashed($customer, Inventory::class, $inventory->id);

        $review->fill([
            'type' => Review::TYPE_PRODUCT,
            'shop_id' => $inventory->shop_id,
            'order_id' => $orderItem->order_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        if ($review->trashed()) {
            $review->deleted_at = null;
        }

        $review->save();

        if (! empty($data['images'])) {
            $review->saveAttachments($data['images']);
        }

        ReviewSummary::recomputeFor($inventory);

        return $review;
    }

    /**
     * @param  array{rating: int, comment?: string|null, images?: array}  $data
     */
    public function createStoreReview(Customer $customer, Shop $shop, array $data, ?Order $order = null): Review
    {
        $eligibleOrder = $this->eligibility->canReviewStore($customer, $shop, $order);

        if (! $eligibleOrder) {
            throw ValidationException::withMessages([
                'rating' => trans('theme.validation.not_eligible_for_review') ?? 'You can only review stores you have purchased from.',
            ]);
        }

        $review = $this->findOrNewIncludingTrashed($customer, Shop::class, $shop->id);

        $review->fill([
            'type' => Review::TYPE_STORE,
            'shop_id' => $shop->id,
            'order_id' => $eligibleOrder->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        if ($review->trashed()) {
            $review->deleted_at = null;
        }

        $review->save();

        if (! empty($data['images'])) {
            $review->saveAttachments($data['images']);
        }

        ReviewSummary::recomputeFor($shop);

        return $review;
    }

    /**
     * Find the customer's existing review for this reviewable (including a previously
     * soft-deleted one, so re-writing after an admin-approved deletion revives it
     * instead of hitting the unique(customer_id, reviewable_type, reviewable_id)
     * constraint), or a fresh unsaved instance if none exists yet.
     */
    private function findOrNewIncludingTrashed(Customer $customer, string $reviewableType, int $reviewableId): Review
    {
        return Review::withTrashed()->firstOrNew([
            'customer_id' => $customer->id,
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $reviewableId,
        ]);
    }
}
