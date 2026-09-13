<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\Review\ReviewDeleteRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('index', Review::class);

        $shopId = Auth::user()->merchantId();

        $base = Review::with('reviewable', 'customer')
            ->where('shop_id', $shopId)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderByDesc('created_at');

        $product = (clone $base)->product()->get();
        $store = (clone $base)->store()->get();

        return view('merchant.review.index', compact('product', 'store'));
    }

    public function show(Review $review)
    {
        $this->authorize('view', $review);
        $this->authorizeShop($review);

        $review->load(['reviewable', 'customer', 'order', 'deleteRequests' => function ($q) {
            $q->latest();
        }]);

        return view('merchant.review.show', compact('review'));
    }

    public function reply(Request $request, Review $review)
    {
        $this->authorize('reply', $review);
        $this->authorizeShop($review);

        $request->validate([
            'reply' => 'required|string|max:1000',
        ]);

        $review->update([
            'reply' => $request->input('reply'),
            'replied_by' => Auth::id(),
            'replied_at' => now(),
        ]);

        return back()->with('success', trans('theme.notify.review_reply_saved') ?? 'Your reply has been posted.');
    }

    public function requestDelete(Request $request, Review $review, ReviewDeleteRequestService $service)
    {
        $this->authorize('requestDelete', $review);
        $this->authorizeShop($review);

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $service->submitRequest($review, $request->input('reason'), Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', trans('theme.notify.review_delete_requested') ?? 'Deletion request sent to admin.');
    }

    protected function authorizeShop(Review $review): void
    {
        if ((int) $review->shop_id !== (int) Auth::user()->merchantId()) {
            abort(403);
        }
    }
}
