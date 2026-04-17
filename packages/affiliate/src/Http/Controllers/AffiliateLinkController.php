<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Incevio\Package\Affiliate\Models\AffiliateLink;

class AffiliateLinkController extends Controller
{
    /**
     * Display a listing of the affiliate links.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $links = AffiliateLink::with('inventory', 'inventory.shop')->mine()->get();

        return view('affiliate::backend.index', compact('links'));
    }

    /**
     * Display the commissions for a specific affiliate link.
     *
     * @param AffiliateLink $link The affiliate link for which to display commissions.
     * @return \Illuminate\View\View The view displaying the commissions.
     */
    public function showLinkCommissions(AffiliateLink $link)
    {
        $commissions = $link->commissions()->get();

        return view('affiliate::backend.showLinkCommission', compact('commissions'));
    }

    /**
     * Create a new affiliate link.
     *
     * @param Inventory $inventory
     * @return \Illuminate\View\View
     */
    public function create(Inventory $inventory)
    {
        return view('affiliate::backend.create', compact('inventory'));
    }

    /**
     * Store a newly created affiliate link in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param Inventory $inventory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Inventory $inventory)
    {
        $request->user()->affiliateLinks()->create([
            'inventory_id' => $inventory->id,
            'slug' => $request->slug,
        ]);

        return redirect()->back()
            ->with('success', trans('packages.affiliate.link_created_successfully'));
    }

    /**
     * Display the form for editing the specified affiliate link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param int $id The ID of the affiliate link to edit.
     * @return \Illuminate\View\View The view for editing the affiliate link.
     */
    public function edit(AffiliateLink $link)
    {
        return view('affiliate::backend.edit', compact('link'));
    }

    /**
     * Update the specified affiliate link in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param AffiliateLink $link
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, AffiliateLink $link)
    {
        $link->update([
            'slug' => $request->slug,
        ]);

        return redirect()->back()
            ->with('success', trans('packages.affiliate.link_updated_successfully'));
    }

    /**
     * Delete an affiliate link.
     *
     * @param AffiliateLink $link
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AffiliateLink $link)
    {
        $link->delete();

        return redirect()->route('affiliate.link.index')
            ->with('success', trans('packages.affiliate.link_deleted_successfully'));
    }
}
