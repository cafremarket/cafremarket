<?php

namespace App\Http\Middleware;

use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Vendor API: every route-bound record that belongs to a shop must belong to the caller's shop.
 * Controllers take `{warehouse}`, `{order}`, `{tax}`, ... straight from the URL, so without this
 * any vendor could read or change another shop's records by id.
 */
class EnsureVendorOwnsBoundRecords
{
    /** Shared catalog: readable by every vendor, changeable only by the owning shop. */
    private const SHARED_CATALOG = [Product::class, Manufacturer::class];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('vendor_api')->user();

        if (! $user instanceof User || $user->isFromPlatform()) {
            return $next($request);
        }

        $shopId = (int) $user->merchantId();
        $readOnly = $request->isMethod('GET') || $request->isMethod('HEAD');

        foreach (optional($request->route())->parameters() ?? [] as $parameter) {
            if (! $parameter instanceof Model) {
                continue;
            }

            if ($readOnly && in_array($parameter::class, self::SHARED_CATALOG, true)) {
                continue;
            }

            if ($parameter instanceof Shop) {
                $owned = (int) $parameter->getKey() === $shopId;
            } else {
                $attributes = $parameter->getAttributes();
                // shop_id NULL = platform-wide record: readable, not writable.
                $owned = ! array_key_exists('shop_id', $attributes)
                    || ($attributes['shop_id'] === null ? $readOnly : (int) $attributes['shop_id'] === $shopId);
            }

            if (! $owned) {
                return response()->json(['message' => trans('responses.unauthorized')], 403);
            }
        }

        return $next($request);
    }
}
