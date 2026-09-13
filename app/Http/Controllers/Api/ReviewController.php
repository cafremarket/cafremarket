<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\ProductReviewCreateRequest;
use App\Http\Requests\Validations\StoreReviewCreateRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Shop;
use App\Services\Review\ReviewEligibilityService;
use App\Services\Review\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewEligibilityService $eligibility,
        private ReviewService $reviews
    ) {
    }

    /**
     * Public, paginated list of a shop's reviews.
     */
    public function show_shop_reviews(Request $request, $slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();

        return ReviewResource::collection(
            $shop->reviews()->with(['customer', 'attachments'])->paginate(config('mobile_app.view_listing_per_page', 8))
        );
    }

    /**
     * Public, paginated list of a product's reviews.
     */
    public function show_item_reviews(Request $request, $slug)
    {
        $item = Inventory::where('slug', $slug)->firstOrFail();

        return ReviewResource::collection(
            $item->reviews()->with(['customer', 'attachments'])->paginate(config('mobile_app.view_listing_per_page', 8))
        );
    }

    /**
     * Whether the logged in customer can currently write a store review - used to
     * show/hide the "Write a Review" CTA on the shop page.
     */
    public function shop_review_eligibility(Request $request, $slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        $order = $this->eligibility->canReviewStore(Auth::guard('api')->user(), $shop);

        return response()->json([
            'can_review' => (bool) $order,
            'order_id' => optional($order)->id,
        ]);
    }

    /**
     * Whether the logged in customer can currently write a product review - used to
     * show/hide the "Write a Review" CTA on the product page.
     */
    public function product_review_eligibility(Request $request, $slug)
    {
        $item = Inventory::where('slug', $slug)->firstOrFail();
        $orderItem = $this->eligibility->canReviewProduct(Auth::guard('api')->user(), $item);

        return response()->json([
            'can_review' => (bool) $orderItem,
            'order_id' => optional($orderItem)->order_id,
        ]);
    }

    /**
     * Write a store review directly from the shop page (any past qualifying purchase,
     * not limited to a specific order).
     */
    public function store_shop_review(Request $request, $slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'rating' => 'required|numeric|between:1,5',
            'comment' => 'nullable|string|min:10|max:250',
        ]);

        try {
            $this->reviews->createStoreReview(Auth::guard('api')->user(), $shop, $data);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => trans('api.your_review_saved') ?? 'Your review has been saved.'], 200);
    }

    /**
     * Write a product review directly from the product page (any past qualifying
     * purchase, not limited to a specific order).
     */
    public function store_product_review(Request $request, $slug)
    {
        $item = Inventory::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'rating' => 'required|numeric|between:1,5',
            'comment' => 'nullable|string|min:10|max:250',
        ]);

        try {
            $this->reviews->createProductReview(Auth::guard('api')->user(), $item, $data);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => trans('api.your_review_saved') ?? 'Your review has been saved.'], 200);
    }

    /**
     * Write a store review from the post-delivery "leave feedback" order flow.
     */
    public function save_shop_review(StoreReviewCreateRequest $request, Order $order)
    {
        try {
            $this->reviews->createStoreReview($order->customer, $order->shop, $request->all(), $order);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => trans('api.your_feedback_saved')], 200);
    }

    /**
     * Write product reviews for every reviewable item on the order from the
     * post-delivery "leave feedback" order flow.
     */
    public function save_product_review(ProductReviewCreateRequest $request, Order $order)
    {
        $inputs = $request->input('items');

        foreach ($order->inventories as $inventory) {
            if (! isset($inputs[$inventory->id])) {
                continue;
            }

            try {
                $this->reviews->createProductReview($order->customer, $inventory, $inputs[$inventory->id], $order);
            } catch (ValidationException $e) {
                continue;
            }
        }

        return response()->json(['message' => trans('api.your_feedback_saved')], 200);
    }
}
