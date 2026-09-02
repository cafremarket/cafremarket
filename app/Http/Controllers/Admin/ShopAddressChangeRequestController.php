<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopAddressChangeRequest;
use App\Services\Shop\ShopAddressChangeService;
use Illuminate\Http\Request;

class ShopAddressChangeRequestController extends Controller
{
    public function index()
    {
        $requests = ShopAddressChangeRequest::query()
            ->with(['shop.logo', 'shop.owner', 'requester'])
            ->where('status', ShopAddressChangeRequest::STATUS_PENDING)
            ->latest()
            ->paginate(20);

        return view('admin.shop.address_change_requests.index', compact('requests'));
    }

    public function show(ShopAddressChangeRequest $addressChangeRequest)
    {
        $addressChangeRequest->load(['shop.owner', 'shop.logo', 'requester', 'reviewer']);

        $previousAddress = $addressChangeRequest->previousAddressModel();
        $requestedAddress = $addressChangeRequest->requestedAddressModel();

        return view('admin.shop.address_change_requests.show', compact(
            'addressChangeRequest',
            'previousAddress',
            'requestedAddress',
        ));
    }

    public function approve(Request $request, ShopAddressChangeRequest $addressChangeRequest, ShopAddressChangeService $service)
    {
        if (config('app.demo') == true && $addressChangeRequest->shop_id <= config('system.demo.shops', 2)) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $this->authorize('update', $addressChangeRequest->shop);

        try {
            $service->approve($addressChangeRequest);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.vendor.shop.addressChangeRequests')
            ->with('success', trans('messages.address_change_request_approved'));
    }

    public function reject(Request $request, ShopAddressChangeRequest $addressChangeRequest, ShopAddressChangeService $service)
    {
        if (config('app.demo') == true && $addressChangeRequest->shop_id <= config('system.demo.shops', 2)) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $this->authorize('update', $addressChangeRequest->shop);

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $service->reject($addressChangeRequest, $request->input('rejection_reason'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.vendor.shop.addressChangeRequests')
            ->with('success', trans('messages.address_change_request_rejected'));
    }
}
