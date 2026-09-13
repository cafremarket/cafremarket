<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReviewDeleteRequest;
use App\Services\Review\ReviewDeleteRequestService;
use Illuminate\Http\Request;

class ReviewDeleteRequestController extends Controller
{
    public function index()
    {
        $requests = ReviewDeleteRequest::query()
            ->with(['review.reviewable', 'review.customer', 'shop', 'requester'])
            ->where('status', ReviewDeleteRequest::STATUS_PENDING)
            ->latest()
            ->paginate(20);

        return view('admin.reviews.delete_requests.index', compact('requests'));
    }

    public function show(ReviewDeleteRequest $deleteRequest)
    {
        $this->authorize('view', $deleteRequest);

        $deleteRequest->load(['review.reviewable', 'review.customer', 'review.order', 'shop', 'requester', 'reviewer']);

        return view('admin.reviews.delete_requests.show', compact('deleteRequest'));
    }

    public function approve(Request $request, ReviewDeleteRequest $deleteRequest, ReviewDeleteRequestService $service)
    {
        $this->authorize('approve', $deleteRequest);

        try {
            $service->approve($deleteRequest, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.support.review.deleteRequests')
            ->with('success', trans('messages.review_delete_request_approved') ?? 'Review deleted.');
    }

    public function reject(Request $request, ReviewDeleteRequest $deleteRequest, ReviewDeleteRequestService $service)
    {
        $this->authorize('reject', $deleteRequest);

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $service->reject($deleteRequest, $request->input('rejection_reason'), $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.support.review.deleteRequests')
            ->with('success', trans('messages.review_delete_request_rejected') ?? 'Delete request rejected.');
    }
}
