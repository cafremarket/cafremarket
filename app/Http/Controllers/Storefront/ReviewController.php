<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Shop;
use App\Services\Review\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Write (or edit) a product/store review directly from the product/shop page - the
 * customer does not need to be looking at a specific order; eligibility is proven by
 * any past delivered purchase (see ReviewEligibilityService).
 */
class ReviewController extends Controller
{
    public function storeProductReview(Request $request, $slug, ReviewService $reviews)
    {
        $item = Inventory::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'rating' => 'required|numeric|between:1,5',
            'comment' => 'nullable|string|min:10|max:250',
        ]);

        try {
            $reviews->createProductReview(Auth::guard('customer')->user(), $item, $data);
        } catch (ValidationException $e) {
            return back()->with('warning', $e->getMessage())->withInput();
        }

        return back()->with('success', trans('theme.notify.your_feedback_saved'));
    }

    public function storeShopReview(Request $request, $slug, ReviewService $reviews)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'rating' => 'required|numeric|between:1,5',
            'comment' => 'nullable|string|min:10|max:250',
        ]);

        try {
            $reviews->createStoreReview(Auth::guard('customer')->user(), $shop, $data);
        } catch (ValidationException $e) {
            return back()->with('warning', $e->getMessage())->withInput();
        }

        return back()->with('success', trans('theme.notify.your_feedback_saved'));
    }
}
