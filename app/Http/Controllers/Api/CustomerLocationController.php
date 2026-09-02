<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Customer;
use App\Services\Hyperlocal\BuyerLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerLocationController extends Controller
{
    public function show(BuyerLocationService $buyerLocation)
    {
        $customer = Auth::guard('api')->user();
        $buyerLocation->ensureDeliveryLocation($customer);

        return response()->json($buyerLocation->toArray());
    }

    public function store(Request $request, BuyerLocationService $buyerLocation)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address_text' => 'nullable|string|max:500',
        ]);

        $buyerLocation->save(
            (float) $request->latitude,
            (float) $request->longitude,
            $request->address_text,
            $request
        );

        return response()->json(array_merge(
            ['message' => trans('theme.location_saved')],
            $buyerLocation->toArray()
        ));
    }

    public function useAddress(Address $address, BuyerLocationService $buyerLocation)
    {
        $customer = Auth::guard('api')->user();

        if (! $customer instanceof Customer
            || $address->addressable_id != $customer->id
            || $address->addressable_type != Customer::class) {
            abort(403);
        }

        if (! $buyerLocation->applyAddressAsLocation($address, $customer)) {
            return response()->json([
                'message' => trans('theme.address_geocode_failed'),
            ], 422);
        }

        return response()->json(array_merge(
            ['message' => trans('theme.location_saved')],
            $buyerLocation->toArray()
        ));
    }
}
