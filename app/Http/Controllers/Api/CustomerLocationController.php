<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Geo\GeocodeService;
use App\Services\Hyperlocal\BuyerLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerLocationController extends Controller
{
    public function show(BuyerLocationService $buyerLocation)
    {
        $customer = Auth::guard('api')->user();
        $buyerLocation->syncFromSavedAddress($customer);

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
}
