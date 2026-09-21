<?php

namespace Incevio\Package\Affiliate\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use Illuminate\Http\Request;
use Incevio\Package\Affiliate\Models\AffiliateLink;
use Incevio\Package\Affiliate\Services\AffiliateAttributionService;

/**
 * Customer-app affiliate click tracking.
 * Affiliate login/dashboard stays web-only — this API only attributes referrals.
 */
class AttributionController extends Controller
{
    public function __construct(protected AffiliateAttributionService $attribution)
    {
    }

    /**
     * Track an affiliate short-code / slug click and return the product for deep-link navigation.
     *
     * Mobile apps should call this when opening an /a/{code} or ?ref= link, then open listing/{slug}.
     * Pass the same code again on addToCart / checkout as `ref` or `affiliate_code` as a fallback.
     */
    public function track(Request $request, string $code)
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $link = AffiliateLink::query()
            ->where('slug', $code)
            ->with(['inventory.shop', 'inventory.product', 'inventory.images', 'inventory.image'])
            ->first();

        if (! $link || ! $link->inventory) {
            return response()->json(['message' => trans('api.404')], 404);
        }

        $link->increment('visitor_count');
        $this->attribution->remember($link, $request);

        $inventory = $link->inventory;

        return response()->json([
            'data' => [
                'affiliate_code' => $link->slug,
                'affiliate_id' => (int) $link->affiliate_id,
                'affiliate_link_id' => (int) $link->id,
                'inventory_id' => (int) $inventory->id,
                'product_slug' => $inventory->slug,
                'attribution_days' => $this->attribution->attributionDays(),
                'clicked_at' => now()->toIso8601String(),
                'listing' => (new ItemResource($inventory))->resolve($request),
            ],
        ]);
    }

    /**
     * Return the current last-click attribution for this customer/device (if still valid).
     */
    public function current(Request $request)
    {
        abort_unless(is_incevio_package_loaded('affiliate'), 404);

        $this->attribution->captureFromRequest($request);
        $click = $this->attribution->current($request);

        return response()->json([
            'data' => $click,
        ]);
    }
}
