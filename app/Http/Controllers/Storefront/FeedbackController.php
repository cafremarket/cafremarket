<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\OrderDetailRequest;
use App\Http\Requests\Validations\OrderFeedbackCreateRequest;
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

    /**
     * Save the customer's overall feedback for an order. One order, one feedback.
     *
     * @param  App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function save_order_feedback(OrderFeedbackCreateRequest $request, Order $order)
    {
        if ($order->orderFeedback()->exists()) {
            return back()->with('warning', trans('theme.order_feedback_already_given'));
        }

        if (! $order->canGiveOrderFeedback()) {
            return back()->with('warning', trans('theme.order_feedback_not_allowed'));
        }

        $order->orderFeedback()->create([
            'shop_id' => $order->shop_id,
            'customer_id' => $order->customer_id,
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);

        return back()->with('success', trans('theme.order_feedback_saved'));
    }
}
