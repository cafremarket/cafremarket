<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use App\Http\Controllers\Controller;
use Incevio\Package\Affiliate\Models\AffiliateLink;
use Incevio\Package\Affiliate\Services\AffiliateAttributionService;

class FrontController extends Controller
{
    public function visitShort(string $code)
    {
        $affiliateLink = AffiliateLink::where('slug', $code)->firstOrFail();

        return $this->redirectFromLink($affiliateLink);
    }

    /**
     * Handle the visit to an affiliate link.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function visit(string $affiliate, string $slug)
    {
        $affiliateLink = AffiliateLink::where('slug', $slug)->firstOrFail();

        return $this->redirectFromLink($affiliateLink);
    }

    protected function redirectFromLink(AffiliateLink $affiliateLink)
    {
        abort_unless($affiliateLink->inventory, 404);

        $affiliateLink->increment('visitor_count');

        app(AffiliateAttributionService::class)->remember($affiliateLink);

        // Customer app deep-link: return JSON when requested instead of web redirect.
        if (request()->expectsJson() || request()->wantsJson()) {
            $inventory = $affiliateLink->inventory;

            return response()->json([
                'data' => [
                    'affiliate_code' => $affiliateLink->slug,
                    'product_slug' => $inventory->slug,
                    'inventory_id' => (int) $inventory->id,
                    'redirect_url' => storefront_product_url($inventory),
                ],
            ]);
        }

        return redirect()->to(storefront_product_url($affiliateLink->inventory));
    }
}
