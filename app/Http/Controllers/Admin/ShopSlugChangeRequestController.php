<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopSlugChangeRequest;
use App\Services\Shop\ShopSlugChangeService;
use Illuminate\Http\Request;

class ShopSlugChangeRequestController extends Controller
{
    public function index()
    {
        $requests = ShopSlugChangeRequest::query()
            ->with(['shop.logo', 'shop.owner', 'requester'])
            ->where('status', ShopSlugChangeRequest::STATUS_PENDING)
            ->latest()
            ->paginate(20);

        return view('admin.shop.slug_change_requests.index', compact('requests'));
    }

    public function show(ShopSlugChangeRequest $slugChangeRequest)
    {
        $slugChangeRequest->load(['shop.owner', 'shop.logo', 'requester', 'reviewer']);

        return view('admin.shop.slug_change_requests.show', compact('slugChangeRequest'));
    }

    public function approve(Request $request, ShopSlugChangeRequest $slugChangeRequest, ShopSlugChangeService $service)
    {
        if (config('app.demo') == true && $slugChangeRequest->shop_id <= config('system.demo.shops', 2)) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $this->authorize('update', $slugChangeRequest->shop);

        try {
            $service->approve($slugChangeRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.vendor.shop.slugChangeRequests')
            ->with('success', trans('messages.slug_change_request_approved'));
    }

    public function reject(Request $request, ShopSlugChangeRequest $slugChangeRequest, ShopSlugChangeService $service)
    {
        if (config('app.demo') == true && $slugChangeRequest->shop_id <= config('system.demo.shops', 2)) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $this->authorize('update', $slugChangeRequest->shop);

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $service->reject($slugChangeRequest, $request->input('rejection_reason'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.vendor.shop.slugChangeRequests')
            ->with('success', trans('messages.slug_change_request_rejected'));
    }
}
