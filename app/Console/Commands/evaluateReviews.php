<?php

namespace App\Console\Commands;

use App\Common\Reviewable;
use App\Services\ClassFinder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class evaluateReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'incevio:evaluate-review-ratings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate review rating summaries for models like Shops and Inventories';

    public function handle()
    {
        // Select only classes that use the Reviewable trait
        $models = array_filter(
            ClassFinder::getClassesInNamespace('App\Models'),
            function ($className) {
                $traits = class_uses($className);

                return isset($traits[Reviewable::class]);
            }
        );

        foreach ($models as $model) {
            $now = Carbon::now();

            $model::withCount([
                'reviews',
                'reviews as tempAvgRatings' => function ($q2) {
                    $q2->select(DB::raw('avg(rating)'));
                },
            ])->chunkById(5, function ($items) use ($now, $model) {
                DB::beginTransaction();

                foreach ($items as $item) {
                    if (! $item->tempAvgRatings) {
                        continue;
                    }

                    DB::table('review_summaries')->updateOrInsert(
                        ['reviewable_id' => $item->id, 'reviewable_type' => $model],
                        [
                            'rating' => $item->tempAvgRatings,
                            'count' => $item->reviews_count,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }

                DB::commit();
            });
        }
    }
}
