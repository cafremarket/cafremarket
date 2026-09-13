<?php

namespace App\Services\Review;

use App\Models\Review;
use App\Models\ReviewDeleteRequest;
use App\Models\ReviewSummary;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ReviewDeleteRequestService
{
    public function hasPendingRequest(int $reviewId): bool
    {
        return ReviewDeleteRequest::query()
            ->where('review_id', $reviewId)
            ->where('status', ReviewDeleteRequest::STATUS_PENDING)
            ->exists();
    }

    public function submitRequest(Review $review, string $reason, ?User $user = null): ReviewDeleteRequest
    {
        if ($this->hasPendingRequest($review->id)) {
            throw new \RuntimeException(trans('messages.review_delete_request_pending'));
        }

        return ReviewDeleteRequest::create([
            'review_id' => $review->id,
            'shop_id' => $review->shop_id,
            'requested_by' => $user?->id ?? Auth::id(),
            'reason' => $reason,
            'status' => ReviewDeleteRequest::STATUS_PENDING,
        ]);
    }

    public function approve(ReviewDeleteRequest $deleteRequest, ?User $reviewer = null): void
    {
        if (! $deleteRequest->isPending()) {
            throw new \RuntimeException(trans('messages.review_delete_request_not_pending'));
        }

        $review = $deleteRequest->review;

        $this->deleteReview($review);

        $deleteRequest->update([
            'status' => ReviewDeleteRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewer?->id ?? Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(ReviewDeleteRequest $deleteRequest, string $reason, ?User $reviewer = null): void
    {
        if (! $deleteRequest->isPending()) {
            throw new \RuntimeException(trans('messages.review_delete_request_not_pending'));
        }

        $deleteRequest->update([
            'status' => ReviewDeleteRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer?->id ?? Auth::id(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Directly delete a review (admin-only action, no request needed) and recompute
     * its reviewable's rating summary.
     */
    public function deleteReview(Review $review): void
    {
        $reviewable = $review->reviewable;

        $review->delete();

        if ($reviewable) {
            ReviewSummary::recomputeFor($reviewable);
        }
    }
}
