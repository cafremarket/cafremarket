<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewDeleteRequest;
use App\Services\Review\ReviewDeleteRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['reviewable', 'customer', 'attachments'])
            ->where('shop_id', Auth::user()->merchantId())
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->boolean('unreplied'), fn ($q) => $q->whereNull('reply'))
            ->latest()
            ->paginate(config('mobile_app.view_listing_per_page', 8));

        return ReviewResource::collection($reviews);
    }

    public function show(Request $request, Review $review)
    {
        $this->authorizeShop($review);

        return new ReviewResource($review->load(['reviewable', 'customer', 'attachments']));
    }

    public function reply(Request $request, Review $review)
    {
        $this->authorizeShop($review);

        $request->validate([
            'reply' => 'required|string|max:1000',
        ]);

        $review->update([
            'reply' => $request->input('reply'),
            'replied_by' => Auth::id(),
            'replied_at' => now(),
        ]);

        return response()->json(['message' => trans('api.review_reply_saved') ?? 'Your reply has been posted.']);
    }

    public function deleteReply(Request $request, Review $review)
    {
        $this->authorizeShop($review);

        $review->update(['reply' => null, 'replied_by' => null, 'replied_at' => null]);

        return response()->json(['message' => trans('api.review_reply_removed') ?? 'Reply removed.']);
    }

    public function requestDelete(Request $request, Review $review, ReviewDeleteRequestService $service)
    {
        $this->authorizeShop($review);

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $service->submitRequest($review, $request->input('reason'), Auth::user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.review_delete_requested') ?? 'Deletion request sent to admin.']);
    }

    public function deleteRequests(Request $request)
    {
        $requests = ReviewDeleteRequest::with('review.reviewable')
            ->where('shop_id', Auth::user()->merchantId())
            ->latest()
            ->paginate(config('mobile_app.view_listing_per_page', 8));

        return response()->json($requests);
    }

    protected function authorizeShop(Review $review): void
    {
        if ((int) $review->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }
    }
}
