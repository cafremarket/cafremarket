<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Hyperlocal\BuyerLocationService;
use App\Services\Shop\NearbyShopDiagnosticService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NearbyShopDiagnosticController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (config('app.debug')) {
                return $next($request);
            }

            $user = Auth::guard('web')->user();

            if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return $next($request);
            }

            abort(404);
        });
    }

    public function index(
        Request $request,
        BuyerLocationService $buyerLocation,
        NearbyShopDiagnosticService $diagnostic
    ) {
        $latitude = $request->filled('lat')
            ? (float) $request->get('lat')
            : $buyerLocation->latitude();
        $longitude = $request->filled('lng')
            ? (float) $request->get('lng')
            : $buyerLocation->longitude();

        if ($request->filled('lat') && $request->filled('lng')) {
            $buyerLocation->save($latitude, $longitude, $request->get('address_text'), $request);
        }

        $report = $diagnostic->analyze($latitude, $longitude);

        return view('test.nearby_stores', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'addressText' => $buyerLocation->addressText(),
            'report' => $report,
            'hyperlocalEnabled' => (bool) config('hyperlocal.enabled', true),
        ]);
    }
}
