<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Incevio\Package\Affiliate\Models\Affiliate;
use Incevio\Package\Affiliate\Models\AffiliateLink;

class FrontController extends Controller
{
  /**
   * Handle the visit to an affiliate link.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function visit(string $affiliate_username, string $slug)
  {
    $affiliate = Affiliate::where('username', $affiliate_username)->firstOrFail();

    $affiliateLink = $affiliate->affiliateLinks()
      ->where('slug', $slug)
      ->firstOrFail();

    $affiliateLink->increment('visitor_count');

    Session::put('affiliate_marketer_id', $affiliateLink->affiliate_id);

    return redirect()->route('show.product', ['slug' => $affiliateLink->inventory->slug]);
  }
}
