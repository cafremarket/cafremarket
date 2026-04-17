<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Datatables;
use App\Http\Controllers\Controller;
use Incevio\Package\Affiliate\Models\Affiliate;

class AffiliateController extends Controller
{
    /**
     * Display the index page of the affiliate backend.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('affiliate::admin.index');
    }

    public function show(Affiliate $affiliate)
    {
        return view('affiliate::admin.show', compact('affiliate'));
    }

    /**
     * Get all affiliates and format them for DataTables.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAffiliates()
    {
        $affiliates = Affiliate::all();

        return Datatables::of($affiliates)
            ->editColumn('checkbox', function ($affiliate) {
                return view('affiliate::partials._checkbox', compact('affiliate'));
            })
            ->editColumn('name', function ($affiliate) {
                return view('affiliate::partials._name', compact('affiliate'));
            })
            ->editColumn('email', function ($affiliate) {
                return view('affiliate::partials._email', compact('affiliate'));
            })
            ->editColumn('option', function ($affiliate) {
                return view('affiliate::partials._options', compact('affiliate'));
            })
            ->rawColumns(['checkbox', 'name', 'email', 'option'])
            ->make(true);
    }

    /**
     * Get the affiliate links for a specific affiliate.
     *
     * @param Affiliate $affiliate The affiliate instance.
     * @return \Illuminate\View\View The view instance.
     */
    public function getAffiliateLinks(Affiliate $affiliate)
    {
        $links = $affiliate->affiliateLinks()->get();

        return view('affiliate::admin.links', compact('links'));
    }

    /**
     * Display the form for creating a new affiliate.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('affiliate::admin.create');
    }

    /**
     * Store a newly created affiliate in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $affiliate = Affiliate::create($request->all());

        return redirect()->route('admin.affiliate.index')->with('success', trans('packages.affiliate.affiliate_created'));
    }

    /**
     * Display the form for editing the specified affiliate.
     *
     * @param Affiliate $affiliate The affiliate to be edited
     * @return \Illuminate\View\View The view for editing the affiliate
     */
    public function edit(Affiliate $affiliate)
    {
        return view('affiliate::admin.edit', compact('affiliate'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param Affiliate $affiliate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Affiliate $affiliate, Request $request)
    {
        if (config('app.demo') == true && $affiliate->id <= config('system.demo.affiliates')) {
            return response()->json(['message' => trans('messages.demo_restriction')], 400);
        }

        $affiliate->update($request->all());

        return redirect()->route('admin.affiliate.index')->with('success', trans('packages.affiliate.affiliate_updated'));
    }

    /**
     * Delete an affiliate.
     *
     * @param int $id The ID of the affiliate to delete.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Affiliate $affiliate)
    {
        if (config('app.demo') == true && $affiliate->id <= config('system.demo.affiliates')) {
            return response()->json(['message' => trans('messages.demo_restriction')], 400);
        }

        $affiliate->delete();

        return redirect()->route('admin.affiliate.index')->with('success', trans('packages.affiliate.affiliate_deleted'));
    }
}
