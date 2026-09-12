<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopLightResource;
use App\Http\Controllers\Api\Concerns\CachesApiResponses;
use App\Services\Cache\CatalogCache;
use App\Services\Shop\NearbyShopService;
use Illuminate\Http\Request;

class NearbyShopController extends Controller
{
    use CachesApiResponses;

    public function index(Request $request, NearbyShopService $nearbyShopService)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;

        return $this->rememberApi('nearby:'.CatalogCache::geoKey($lat, $lng), function () use ($nearbyShopService, $request, $lat, $lng) {
            $results = $nearbyShopService->find($lat, $lng);

            return [
                'data' => $results->map(function ($row) use ($request) {
                    $address = $row['shop']->storeAddress();

                        return array_merge(
                            (new ShopLightResource($row['shop']))->resolve($request),
                        [
                            'distance_km' => $row['distance_km'],
                            'deliverable' => $row['deliverable'],
                            'latitude' => $address?->latitude ? (float) $address->latitude : null,
                            'longitude' => $address?->longitude ? (float) $address->longitude : null,
                        ]
                    );
                })->values(),
            ];
        }, null, 'geo');
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
