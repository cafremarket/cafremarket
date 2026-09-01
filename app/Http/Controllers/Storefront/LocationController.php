<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Geo\GeocodeService;
use App\Services\Hyperlocal\BuyerLocationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function store(Request $request, BuyerLocationService $buyerLocation)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address_text' => 'nullable|string|max:500',
        ]);

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $addressText = trim((string) $request->address_text);

        if ($addressText === '' || preg_match('/^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?$/', $addressText)) {
            $addressText = app(GeocodeService::class)->reverseGeocode($latitude, $longitude) ?? '';
        }

        if ($addressText === '') {
            return response()->json([
                'message' => trans('theme.address_lookup_failed'),
            ], 422);
        }

        $buyerLocation->save(
            $latitude,
            $longitude,
            $addressText,
            $request
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => trans('theme.location_saved'),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address_text' => $addressText,
            ]);
        }

        return redirect()->back()->with('success', trans('theme.location_saved'));
    }

    public function reverseGeocode(Request $request, GeocodeService $geocoder)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        return response()->json([
            'address_text' => $geocoder->reverseGeocode(
                (float) $request->latitude,
                (float) $request->longitude
            ),
            'details' => $geocoder->reverseGeocodeDetails(
                (float) $request->latitude,
                (float) $request->longitude
            ),
        ]);
    }

    public function searchAddress(Request $request, GeocodeService $geocoder)
    {
        $request->validate([
            'query' => 'required|string|min:3|max:200',
        ]);

        return response()->json([
            'results' => $geocoder->searchAddresses($request->query('query')),
        ]);
    }
}
