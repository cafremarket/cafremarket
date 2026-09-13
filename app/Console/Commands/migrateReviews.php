<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use App\Models\Inventory;
use App\Models\Review;
use App\Models\ReviewSummary;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off backfill: copies existing Shop/Inventory Feedback rows into the new
 * `reviews` table so live storefront reviews are not lost when the review module is
 * rebuilt. DeliveryBoy feedback is left untouched on the old `feedbacks` table.
 *
 * Reviews are product/store based, not order based: a customer has at most one review
 * per reviewable. The old `feedbacks` table had no such constraint, so where a customer
 * left multiple old feedback rows for the same shop/product, the most recent one wins
 * (rows are processed oldest-first, so later upserts overwrite earlier ones). An
 * order_id is attached best-effort (via the old orders.feedback_id / order_items.
 * feedback_id links) purely for display - it is informational only.
 *
 * Safe to re-run.
 */
class migrateReviews extends Command
{
    protected $signature = 'incevio:migrate-reviews';

    protected $description = 'Backfill the new reviews table from the legacy feedbacks table (Shop/Inventory only)';

    public function handle()
    {
        $migrated = 0;
        $skipped = 0;

        Feedback::whereIn('feedbackable_type', [Shop::class, Inventory::class])
            ->oldest('created_at')
            ->chunkById(200, function ($feedbacks) use (&$migrated, &$skipped) {
                foreach ($feedbacks as $feedback) {
                    $isStore = $feedback->feedbackable_type === Shop::class;

                    $shopId = $isStore
                        ? $feedback->feedbackable_id
                        : Inventory::withTrashed()->whereKey($feedback->feedbackable_id)->value('shop_id');

                    if (! $shopId) {
                        $skipped++;

                        continue;
                    }

                    $orderId = $isStore
                        ? DB::table('orders')->where('feedback_id', $feedback->id)->value('id')
                        : DB::table('order_items')->where('feedback_id', $feedback->id)->value('order_id');

                    Review::updateOrCreate(
                        [
                            'customer_id' => $feedback->customer_id,
                            'reviewable_type' => $feedback->feedbackable_type,
                            'reviewable_id' => $feedback->feedbackable_id,
                        ],
                        [
                            'type' => $isStore ? Review::TYPE_STORE : Review::TYPE_PRODUCT,
                            'shop_id' => $shopId,
                            'order_id' => $orderId,
                            'rating' => $feedback->rating,
                            'comment' => $feedback->comment,
                            'approved' => $feedback->approved,
                            'spam' => $feedback->spam,
                            'created_at' => $feedback->created_at,
                            'updated_at' => $feedback->updated_at,
                        ]
                    );

                    $migrated++;
                }
            });

        $this->info("Migrated: {$migrated}, skipped (no linkable shop): {$skipped}");

        $this->call('incevio:evaluate-review-ratings');

        $newTotal = ReviewSummary::sum('count');
        $this->info("New review_summaries total review count: {$newTotal}");
    }
}
