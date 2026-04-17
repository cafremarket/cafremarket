<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use App\Http\Controllers\Controller;
use Incevio\Package\Affiliate\Models\AffiliateCommission;

class CommissionController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $commissions = AffiliateCommission::all();

        return view('affiliate::admin.affiliate_commissions', compact('commissions'));
    }

    /**
     * Release the commission and mark it as paid.
     *
     * @param AffiliateCommission $commission The commission to be released.
     * @return \Illuminate\Http\RedirectResponse The redirect response with a success message.
     */
    public function releaseCommission(AffiliateCommission $commission)
    {
        $commission->markAsPaid();

        return back()->with(['success' => trans('packages.affiliate.commission_is_released')]);
    }
}
