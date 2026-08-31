<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_online' => 'nullable|boolean',
        ]);

        $rider = Auth::guard('delivery_boy-api')->user();

        $rider->current_latitude = $request->latitude;
        $rider->current_longitude = $request->longitude;
        $rider->last_location_at = now();

        if ($request->has('is_online')) {
            $rider->is_online = $request->boolean('is_online');
        }

        $rider->save();

        return response()->json([
            'message' => trans('api.location_updated'),
            'is_online' => (bool) $rider->is_online,
            'last_location_at' => optional($rider->last_location_at)->toIso8601String(),
        ]);
    }
}
