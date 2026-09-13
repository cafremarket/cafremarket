<?php

namespace App\Common;

use App\Models\Review;
use App\Models\ReviewSummary;
use Illuminate\Support\Facades\Auth;

/**
 * Attach this Trait to a Shop or Inventory model for easier read/writes on Reviews.
 * Replaces the old Feedbackable trait for store/product reviews.
 */
trait Reviewable
{
    /**
     * Return collection of reviews related to the reviewable model.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable')->orderBy('created_at', 'desc');
    }

    /**
     * Return the last 10 reviews related to the reviewable model.
     */
    public function latestReviews()
    {
        return $this->morphMany(Review::class, 'reviewable')
            ->latest()->orderBy('created_at', 'desc')->limit(10);
    }

    public function hasReviews()
    {
        return (bool) $this->reviews()->count();
    }

    public function reviewSummary()
    {
        return $this->morphOne(ReviewSummary::class, 'reviewable');
    }

    public function getRatingsAttribute()
    {
        return optional($this->reviewSummary)->rating;
    }

    public function getRatingsCountAttribute()
    {
        return optional($this->reviewSummary)->count;
    }

    public function rating()
    {
        $rating = $this->ratings;
        $dec = ((int) $rating == $rating) ? 0 : 1;

        return ceil($rating) ? number_format($rating, $dec) : null;
    }

    public function sumReviews()
    {
        return $this->reviews()->sum('rating');
    }

    public function userAverageReview()
    {
        return $this->reviews()
            ->where('customer_id', Auth::guard('customer')->id())
            ->avg('rating');
    }

    public function ratingPercent($max = 5)
    {
        $quantity = $this->reviews()->count();
        $total = $this->sumReviews();

        return ($quantity * $max) > 0 ? $total / (($quantity * $max) / 100) : 0;
    }

    public function getAverageReviewAttribute()
    {
        return $this->ratings;
    }

    /**
     * Deletes all the Reviews of this model.
     */
    public function flushReviews()
    {
        return $this->reviews()->delete();
    }
}
