<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Http\Requests\Validations\ProductReviewCreateRequest;
use App\Http\Requests\Validations\StoreReviewCreateRequest;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Review;
use App\Models\Shop;
use App\Services\Review\ReviewService;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    /**
     * Show feedback form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function feedback_form(OrderDetailRequest $request, Order $order)
    {
        $order->load([
            'shop' => function ($q) {
                return $q->with([
                    'reviewSummary:rating,count,reviewable_id,reviewable_type',
                    'image:path,imageable_id,imageable_type',
                ]);
            },
            'inventories' => function ($q) {
                return $q->with([
                    'reviewSummary:rating,count,reviewable_id,reviewable_type',
                    'image:path,imageable_id,imageable_type',
                ]);
            },
        ]);

        // Reviews are product/store based, not order based - look up whatever the
        // customer has already written for this shop/these products (if anything) so
        // the form can show it instead of a blank "write a review" box.
        $storeReview = Review::where('customer_id', $order->customer_id)
            ->where('reviewable_type', Shop::class)
            ->where('reviewable_id', $order->shop_id)
            ->first();

        $productReviews = Review::where('customer_id', $order->customer_id)
            ->where('reviewable_type', Inventory::class)
            ->whereIn('reviewable_id', $order->inventories->pluck('id'))
            ->get()
            ->keyBy('reviewable_id');

        return view('theme::feedback_form', compact('order', 'storeReview', 'productReviews'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function save_shop_feedbacks(StoreReviewCreateRequest $request, Order $order, ReviewService $reviews)
    {
        try {
            $reviews->createStoreReview($order->customer, $order->shop, $request->all(), $order);
        } catch (ValidationException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', trans('theme.notify.your_feedback_saved'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function save_product_feedbacks(ProductReviewCreateRequest $request, Order $order, ReviewService $reviews)
    {
        $inputs = $request->input('items');

        foreach ($order->inventories as $inventory) {
            if (! isset($inputs[$inventory->id])) {
                continue;
            }

            try {
                $reviews->createProductReview($order->customer, $inventory, $inputs[$inventory->id], $order);
            } catch (ValidationException $e) {
                continue;
            }
        }

        return back()->with('success', trans('theme.notify.your_feedback_saved'));
    }
}
