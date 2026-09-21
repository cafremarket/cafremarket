<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
        $affiliates = Affiliate::query()->latest('id');

        return Datatables::of($affiliates)
            ->addColumn('checkbox', function ($affiliate) {
                return view('affiliate::partials._checkbox', compact('affiliate'))->render();
            })
            ->editColumn('name', function ($affiliate) {
                return view('affiliate::partials._name', compact('affiliate'))->render();
            })
            ->editColumn('email', function ($affiliate) {
                return view('affiliate::partials._email', compact('affiliate'))->render();
            })
            ->editColumn('phone', function ($affiliate) {
                return $affiliate->phone ?: '—';
            })
            ->addColumn('status', function ($affiliate) {
                return view('affiliate::partials._status', compact('affiliate'))->render();
            })
            ->addColumn('option', function ($affiliate) {
                return view('affiliate::partials._options', compact('affiliate'))->render();
            })
            ->rawColumns(['checkbox', 'name', 'email', 'status', 'option'])
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
        $links = $affiliate->affiliateLinks()->with('inventory')->get();

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
        $data = $this->validatedAffiliate($request);

        Affiliate::create($data);

        return redirect()->route('admin.affiliate.index')
            ->with('success', trans('packages.affiliate.affiliate_created'));
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
    public function update(Request $request, Affiliate $affiliate)
    {
        if (config('app.demo') == true && $affiliate->id <= config('system.demo.affiliates', 0)) {
            return response()->json(['message' => trans('messages.demo_restriction')], 400);
        }

        $data = $this->validatedAffiliate($request, $affiliate);

        $affiliate->update($data);

        return redirect()->route('admin.affiliate.index')
            ->with('success', trans('packages.affiliate.affiliate_updated'));
    }

    /**
     * Delete an affiliate.
     *
     * @param Affiliate $affiliate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Affiliate $affiliate)
    {
        if (config('app.demo') == true && $affiliate->id <= config('system.demo.affiliates', 0)) {
            return response()->json(['message' => trans('messages.demo_restriction')], 400);
        }

        $affiliate->delete();

        return redirect()->route('admin.affiliate.index')
            ->with('success', trans('packages.affiliate.affiliate_deleted'));
    }

    /**
     * Mass delete affiliates.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function massDestroy(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['message' => trans('messages.failed')], 422);
        }

        if (config('app.demo') == true) {
            $demoLimit = (int) config('system.demo.affiliates', 0);
            $ids = $ids->reject(fn ($id) => $id <= $demoLimit)->values();
        }

        if ($ids->isNotEmpty()) {
            Affiliate::whereIn('id', $ids)->delete();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => trans('packages.affiliate.affiliate_deleted')]);
        }

        return redirect()->route('admin.affiliate.index')
            ->with('success', trans('packages.affiliate.affiliate_deleted'));
    }

    /**
     * Validate affiliate create/update payload.
     */
    protected function validatedAffiliate(Request $request, ?Affiliate $affiliate = null): array
    {
        $emailRule = 'required|email|max:255|unique:affiliates,email';
        if ($affiliate) {
            $emailRule .= ','.$affiliate->id;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'phone' => 'nullable|string|max:50',
            'active' => 'nullable|boolean',
        ];

        if (! $affiliate) {
            $rules['password'] = 'required|string|min:6|confirmed';
        }

        $data = $request->validate($rules);

        $data['active'] = $request->boolean('active');

        if (! $affiliate) {
            $data['password'] = $request->input('password');
        } else {
            unset($data['password']);
        }

        if (! Schema::hasColumn('affiliates', 'active')) {
            unset($data['active']);
        }

        return $data;
    }
}
