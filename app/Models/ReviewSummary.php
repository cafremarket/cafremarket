<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewSummary extends Model
{
    protected $table = 'review_summaries';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'reviewable_id',
        'reviewable_type',
        'rating',
        'count',
    ];

    public function reviewable()
    {
        return $this->morphTo();
    }

    /**
     * Recompute and persist the rating/count summary for a single reviewable model
     * (Shop or Inventory). Used after a review is created, replied to, or deleted, so
     * ratings stay accurate without waiting for the daily incevio:evaluate-review-ratings
     * cron.
     */
    public static function recomputeFor($reviewable): self
    {
        $stats = $reviewable->reviews()
            ->selectRaw('avg(rating) as avg_rating, count(*) as review_count')
            ->first();

        return static::updateOrCreate(
            ['reviewable_id' => $reviewable->id, 'reviewable_type' => get_class($reviewable)],
            ['rating' => $stats->avg_rating ?? 0, 'count' => $stats->review_count ?? 0]
        );
    }
}
