<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryBoy\DeliveryBoyFeedbackCreateRequest;
use App\Http\Requests\Validations\ProductFeedbackCreateRequest;
use App\Http\Requests\Validations\ShopFeedbackCreateRequest;
use App\Http\Resources\FeedbackResource;
use App\Http\Resources\ReviewResource;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Shop;
use App\Services\Review\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    /**
     * [show_shop_feedbacks description]
     *
     * @param  Request  $request  [description]
     * @param  [type]  $slug    [description]
     * @return [type]           [description]
     */
    public function show_shop_feedbacks(Request $request, $slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();

        // Prefer rebuilt reviews table; fall back to legacy feedbacks if empty.
        $reviews = $shop->reviews()->with(['customer', 'attachments'])
            ->paginate(config('mobile_app.view_listing_per_page', 8));

        if ($reviews->total() > 0) {
            return ReviewResource::collection($reviews);
        }

        return FeedbackResource::collection($shop->feedbacks()->paginate());
    }

    /**
     * [show_item_feedbacks description]
     *
     * @param  Request  $request  [description]
     * @param  [type]  $slug    [description]
     * @return [type]           [description]
     */
    public function show_item_feedbacks(Request $request, $slug)
    {
        $item = Inventory::where('slug', $slug)->firstOrFail();

        $reviews = $item->reviews()->with(['customer', 'attachments'])
            ->paginate(config('mobile_app.view_listing_per_page', 8));

        if ($reviews->total() > 0) {
            return ReviewResource::collection($reviews);
        }

        return FeedbackResource::collection($item->feedbacks()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save_shop_feedbacks(ShopFeedbackCreateRequest $request, Order $order, ReviewService $reviews)
    {
        try {
            $reviews->createStoreReview($order->customer, $order->shop, $request->all(), $order);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => trans('api.your_feedback_saved')], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save_delivery_boy_feedbacks(DeliveryBoyFeedbackCreateRequest $request, Order $order)
    {
        if ($order->delivery_boy_feedback_id) {
            return response()->json([
                'message' => trans('api.you_already_gave_feedback'),
            ], 200);
        }

        $feedback = $order->deliveryBoy->feedbacks()->create($request->all());

        $order->delivery_boy_feedback_given($feedback->id);

        return response()->json(['message' => trans('api.your_feedback_saved')], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save_product_feedbacks(ProductFeedbackCreateRequest $request, Order $order, ReviewService $reviews)
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

        return response()->json(['message' => trans('api.your_feedback_saved')], 200);
    }
}
