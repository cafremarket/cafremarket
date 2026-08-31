<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopLightResource;
use App\Services\Shop\NearbyShopService;
use Illuminate\Http\Request;

class NearbyShopController extends Controller
{
    public function index(Request $request, NearbyShopService $nearbyShopService)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:100',
        ]);

        $results = $nearbyShopService->find(
            (float) $request->lat,
            (float) $request->lng,
            $request->filled('radius_km') ? (float) $request->radius_km : null
        );

        return response()->json([
            'data' => $results->map(function ($row) use ($request) {
                $address = $row['shop']->storeAddress();

                return array_merge(
                    (new ShopLightResource($row['shop']))->toArray($request),
                    [
                        'distance_km' => $row['distance_km'],
                        'deliverable' => $row['deliverable'],
                        'latitude' => $address?->latitude ? (float) $address->latitude : null,
                        'longitude' => $address?->longitude ? (float) $address->longitude : null,
                    ]
                );
            })->values(),
            'radius_km' => $request->filled('radius_km')
                ? (float) $request->radius_km
                : $nearbyShopService->defaultSearchRadius(),
        ]);
    }

    public function map(Request $request, NearbyShopService $nearbyShopService)
    {
        $response = $this->index($request, $nearbyShopService);
        $payload = $response->getData(true);
        $shops = collect($payload['data'] ?? []);

        if ($shops->isEmpty()) {
            return response()->json(array_merge($payload, [
                'bounds' => null,
                'center' => ['lat' => (float) $request->lat, 'lng' => (float) $request->lng],
            ]));
        }

        $lats = $shops->pluck('latitude')->filter()->push((float) $request->lat);
        $lngs = $shops->pluck('longitude')->filter()->push((float) $request->lng);

        return response()->json(array_merge($payload, [
            'center' => ['lat' => (float) $request->lat, 'lng' => (float) $request->lng],
            'bounds' => [
                'north' => $lats->max(),
                'south' => $lats->min(),
                'east' => $lngs->max(),
                'west' => $lngs->min(),
            ],
        ]));
    }
}
