<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\Review\ReviewDeleteRequestService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::query()
            ->with(['reviewable', 'customer', 'shop'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('shop_id'), fn ($q) => $q->where('shop_id', $request->input('shop_id')))
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', $request->input('rating')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function show(Review $review)
    {
        $this->authorize('view', $review);

        $review->load(['reviewable', 'customer', 'shop', 'order', 'replier', 'deleteRequests']);

        return view('admin.reviews.show', compact('review'));
    }

    public function destroy(Review $review, ReviewDeleteRequestService $service)
    {
        $this->authorize('delete', $review);

        $service->deleteReview($review);

        return redirect()
            ->route('admin.support.review.index')
            ->with('success', trans('messages.review_deleted') ?? 'Review deleted.');
    }
}
